<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyBranch;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskChecklistItem;
use App\Models\ProjectVisitEvidence;
use App\Models\SalesOpportunity;
use App\Models\User;
use App\Support\Concerns\ScopesCompaniesByActor;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Support\Facades\DB;

class OperationalWorkspaceService implements ScopesCompanies
{
    use ScopesCompaniesByActor;

    public function canAccessCompany(User $actor, Company|int $company): bool
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $actor->isSuperadmin
            || (int) $actor->company_id === (int) $companyId;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBranch(User $actor, array $data): CompanyBranch
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);

        return CompanyBranch::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClient(User $actor, array $data): Client
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);

        return Client::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProject(User $actor, array $data): Project
    {
        $this->assertCompanyAccess($actor, (int) $data['company_id']);
        $this->assertBelongsToCompany(Client::class, $data['client_id'] ?? null, (int) $data['company_id']);
        $this->assertBelongsToCompany(CompanyBranch::class, $data['branch_id'] ?? null, (int) $data['company_id']);
        $this->assertUserScope($actor, $data['manager_id'] ?? null, (int) $data['company_id']);

        return Project::query()->create($data);
    }

    /**
     * @param  array<int, string>  $checklistItems
     * @param  array<string, mixed>  $data
     */
    public function createTask(User $actor, Project $project, array $data, array $checklistItems = []): ProjectTask
    {
        $this->assertCompanyAccess($actor, $project->company_id);
        $this->assertUserScope($actor, $data['assigned_to'] ?? null, (int) $project->company_id);

        return DB::transaction(function () use ($project, $data, $checklistItems): ProjectTask {
            $task = ProjectTask::query()->create([
                ...$data,
                'project_id' => $project->id,
                'company_id' => $project->company_id,
                'completed_at' => ($data['status'] ?? ProjectTask::STATUS_TODO) === ProjectTask::STATUS_DONE ? now() : null,
            ]);

            foreach (array_values(array_filter($checklistItems)) as $index => $title) {
                ProjectTaskChecklistItem::query()->create([
                    'project_task_id' => $task->id,
                    'title' => $title,
                    'sort_order' => $index + 1,
                ]);
            }

            return $task->fresh(['checklistItems']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTask(User $actor, ProjectTask $task, array $data): ProjectTask
    {
        $this->assertCompanyAccess($actor, $task->company_id);
        $this->assertUserScope($actor, $data['assigned_to'] ?? null, (int) $task->company_id);

        $task->forceFill([
            ...$data,
            'completed_at' => ($data['status'] ?? $task->status) === ProjectTask::STATUS_DONE
                ? ($task->completed_at ?? now())
                : null,
        ])->save();

        return $task->fresh();
    }

    public function toggleChecklistItem(User $actor, ProjectTaskChecklistItem $item): ProjectTaskChecklistItem
    {
        $item->loadMissing('task');
        $this->assertCompanyAccess($actor, $item->task->company_id);

        $isDone = ! $item->is_done;
        $item->forceFill([
            'is_done' => $isDone,
            'completed_at' => $isDone ? now() : null,
        ])->save();

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordVisitEvidence(User $actor, ProjectTask $task, array $data): ProjectVisitEvidence
    {
        $task->loadMissing('project');

        abort_unless($task->assigned_to === null || (string) $task->assigned_to === (string) $actor->id || $actor->can('manageOperationsWorkspace'), 403);
        $this->assertCompanyAccess($actor, $task->company_id);

        return ProjectVisitEvidence::query()->create([
            'company_id' => $task->company_id,
            'project_id' => $task->project_id,
            'project_task_id' => $task->id,
            'user_id' => $actor->id,
            'visited_at' => $data['visited_at'] ?? now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'accuracy_meters' => $data['accuracy_meters'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'photo_disk' => $data['photo_disk'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'photo_original_name' => $data['photo_original_name'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * @return array<int, array{pipeline: float, invoiced: float, paid: float, outstanding: float, estimated_margin: float}>
     */
    public function projectFinancialSummaries(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $invoiceRows = Invoice::query()
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COALESCE(SUM(grand_total), 0) as invoiced_total, COALESCE(SUM(CASE WHEN status = ? THEN grand_total ELSE 0 END), 0) as paid_total', [Invoice::STATUS_PAID])
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $pipelineRows = SalesOpportunity::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('stage', [SalesOpportunity::STAGE_LEAD, SalesOpportunity::STAGE_QUALIFIED, SalesOpportunity::STAGE_PROPOSAL])
            ->selectRaw('project_id, COALESCE(SUM(expected_value * probability / 100), 0) as weighted_pipeline')
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        return collect($projectIds)
            ->mapWithKeys(function (int $projectId) use ($invoiceRows, $pipelineRows): array {
                $invoiced = round((float) ($invoiceRows->get($projectId)?->invoiced_total ?? 0), 2);
                $paid = round((float) ($invoiceRows->get($projectId)?->paid_total ?? 0), 2);
                $pipeline = round((float) ($pipelineRows->get($projectId)?->weighted_pipeline ?? 0), 2);

                return [
                    $projectId => [
                        'pipeline' => $pipeline,
                        'invoiced' => $invoiced,
                        'paid' => $paid,
                        'outstanding' => round($invoiced - $paid, 2),
                        'estimated_margin' => round($paid + $pipeline - $invoiced, 2),
                    ],
                ];
            })
            ->all();
    }

    private function assertCompanyAccess(User $actor, int $companyId): void
    {
        abort_unless($this->canAccessCompany($actor, $companyId), 403);
    }

    private function assertBelongsToCompany(string $modelClass, mixed $id, int $companyId): void
    {
        if ($id === null || $id === '') {
            return;
        }

        abort_unless(
            $modelClass::query()->whereKey($id)->where('company_id', $companyId)->exists(),
            422,
            'Selected record does not belong to the selected company.',
        );
    }

    private function assertUserScope(User $actor, mixed $userId, int $companyId): void
    {
        if ($userId === null || $userId === '') {
            return;
        }

        $user = User::query()->findOrFail($userId);

        abort_unless(
            $user->company_id === null || (int) $user->company_id === $companyId,
            422,
            'Selected user does not belong to the selected company.',
        );

        abort_unless($actor->isSuperadmin || $actor->company_id === null || (int) $actor->company_id === $companyId, 403);
    }
}
