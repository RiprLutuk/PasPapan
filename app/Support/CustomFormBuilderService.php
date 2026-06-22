<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CustomFormSubmission;
use App\Models\CustomFormTemplate;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CustomFormSubmittedForReview;
use App\Notifications\ProjectTaskAssignedFromForm;
use App\Support\Concerns\ScopesCompaniesByActor;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomFormBuilderService implements ScopesCompanies
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
    public function createTemplate(User $actor, array $data): CustomFormTemplate
    {
        $companyId = (int) $data['company_id'];
        $this->assertCompanyAccess($actor, $companyId);
        $automation = $this->normalizeAutomation($data, $companyId);

        return CustomFormTemplate::query()->create([
            'company_id' => $companyId,
            'title' => $data['title'],
            'category' => Str::slug((string) ($data['category'] ?? 'general'), '_') ?: 'general',
            'description' => $data['description'] ?? null,
            'fields' => $this->normalizeFields((string) $data['field_lines']),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'metadata' => [
                ...($data['metadata'] ?? []),
                'automation' => $automation,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(User $actor, CustomFormTemplate $template, array $payload): CustomFormSubmission
    {
        abort_unless($template->is_active, 403);
        $this->assertCompanyAccess($actor, $template->company_id);

        return DB::transaction(function () use ($actor, $template, $payload): CustomFormSubmission {
            $normalizedPayload = $this->normalizePayload($template, $payload);

            $submission = CustomFormSubmission::query()->create([
                'custom_form_template_id' => $template->id,
                'company_id' => $template->company_id,
                'submitted_by' => $actor->id,
                'status' => CustomFormSubmission::STATUS_SUBMITTED,
                'payload' => $normalizedPayload,
            ]);

            $this->runAutomation($actor, $template, $submission);
            $this->notifyReviewers($actor, $submission->fresh(['template', 'submitter']));

            return $submission->fresh(['template', 'submitter']);
        });
    }

    public function markReviewed(User $actor, CustomFormSubmission $submission): string
    {
        abort_unless($actor->can('viewCustomForms'), 403);
        $this->assertCompanyAccess($actor, $submission->company_id);

        $submission->forceFill([
            'status' => CustomFormSubmission::STATUS_REVIEWED,
            'metadata' => [
                ...($submission->metadata ?? []),
                'reviewed_by' => $actor->id,
                'reviewed_at' => now()->toIso8601String(),
            ],
        ])->save();

        return __('Custom form submission marked as reviewed.');
    }

    /**
     * @return list<array{key: string, label: string, type: string, required: bool, options: list<string>}>
     */
    public function normalizeFields(string $fieldLines): array
    {
        $fields = collect(preg_split('/\r\n|\r|\n/', $fieldLines))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(function (string $line, int $index): array {
                $parts = array_pad(array_map('trim', explode('|', $line)), 4, '');
                $label = $parts[0] !== '' ? $parts[0] : 'Field '.($index + 1);
                $type = in_array($parts[1], $this->fieldTypes(), true) ? $parts[1] : CustomFormTemplate::TYPE_TEXT;
                $required = in_array(Str::lower($parts[2]), ['required', 'yes', 'true', '1', 'wajib'], true);
                $options = $type === CustomFormTemplate::TYPE_SELECT
                    ? collect(explode(',', $parts[3]))->map(fn (string $option): string => trim($option))->filter()->values()->all()
                    : [];

                if ($type === CustomFormTemplate::TYPE_SELECT && $options === []) {
                    throw ValidationException::withMessages([
                        'fieldLines' => __('Select fields must define options.'),
                    ]);
                }

                return [
                    'key' => Str::slug($label, '_') ?: 'field_'.$index,
                    'label' => $label,
                    'type' => $type,
                    'required' => $required,
                    'options' => $options,
                ];
            })
            ->values()
            ->all();

        if ($fields === []) {
            throw ValidationException::withMessages([
                'fieldLines' => __('Define at least one form field.'),
            ]);
        }

        return $fields;
    }

    /**
     * @return list<string>
     */
    public function fieldTypes(): array
    {
        return [
            CustomFormTemplate::TYPE_TEXT,
            CustomFormTemplate::TYPE_TEXTAREA,
            CustomFormTemplate::TYPE_NUMBER,
            CustomFormTemplate::TYPE_DATE,
            CustomFormTemplate::TYPE_SELECT,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(CustomFormTemplate $template, array $payload): array
    {
        $normalized = [];
        $errors = [];

        foreach ($template->fields as $field) {
            $key = (string) $field['key'];
            $value = $payload[$key] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                $errors[$key] = __(':field is required.', ['field' => $field['label']]);

                continue;
            }

            if (($field['type'] ?? null) === CustomFormTemplate::TYPE_SELECT && $value !== null && $value !== '' && ! in_array($value, $field['options'] ?? [], true)) {
                $errors[$key] = __('Invalid option for :field.', ['field' => $field['label']]);

                continue;
            }

            $normalized[$key] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function normalizeAutomation(array $data, int $companyId): ?array
    {
        if (! (bool) ($data['automation_enabled'] ?? false)) {
            return null;
        }

        $projectId = $data['automation_project_id'] ?? null;
        if ($projectId === null || $projectId === '') {
            throw ValidationException::withMessages([
                'automationProjectId' => __('Choose a project for form automation.'),
            ]);
        }

        $this->assertBelongsToCompany(Project::class, $projectId, $companyId);

        return [
            'type' => 'project_task',
            'project_id' => (int) $projectId,
            'task_title' => trim((string) ($data['automation_task_title'] ?? '')) ?: null,
            'priority' => in_array($data['automation_task_priority'] ?? ProjectTask::PRIORITY_NORMAL, [ProjectTask::PRIORITY_LOW, ProjectTask::PRIORITY_NORMAL, ProjectTask::PRIORITY_HIGH], true)
                ? $data['automation_task_priority']
                : ProjectTask::PRIORITY_NORMAL,
        ];
    }

    private function runAutomation(User $actor, CustomFormTemplate $template, CustomFormSubmission $submission): void
    {
        $automation = $template->metadata['automation'] ?? null;

        if (! is_array($automation) || ($automation['type'] ?? null) !== 'project_task') {
            return;
        }

        $project = Project::query()
            ->whereKey($automation['project_id'] ?? null)
            ->where('company_id', $template->company_id)
            ->first();

        if (! $project) {
            return;
        }

        $task = ProjectTask::query()->create([
            'project_id' => $project->id,
            'company_id' => $project->company_id,
            'assigned_to' => $actor->id,
            'title' => $automation['task_title'] ?: __('Review form submission: :title', ['title' => $template->title]),
            'status' => ProjectTask::STATUS_TODO,
            'priority' => $automation['priority'] ?? ProjectTask::PRIORITY_NORMAL,
            'description' => $this->automationTaskDescription($template, $submission),
            'metadata' => [
                'source' => 'custom_form_submission',
                'custom_form_template_id' => $template->id,
                'custom_form_submission_id' => $submission->id,
            ],
        ]);

        $submission->forceFill([
            'metadata' => [
                ...($submission->metadata ?? []),
                'automation_task_id' => $task->id,
            ],
        ])->save();

        $actor->notify(new ProjectTaskAssignedFromForm($task, $submission->fresh(['template'])));
    }

    private function notifyReviewers(User $submitter, CustomFormSubmission $submission): void
    {
        $recipients = $this->reviewersForCompany($submission->company_id)
            ->reject(fn (User $user): bool => (string) $user->id === (string) $submitter->id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CustomFormSubmittedForReview($submission));
    }

    /**
     * @return Collection<int, User>
     */
    private function reviewersForCompany(int $companyId): Collection
    {
        return User::query()
            ->with('roles')
            ->whereIn('group', ['admin', 'superadmin'])
            ->where(fn (Builder $query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))
            ->get()
            ->reject(fn (User $user): bool => $user->isDemo)
            ->filter(fn (User $user): bool => $user->can('viewCustomForms') || $user->can('manageOperationsWorkspace'))
            ->unique('id')
            ->values();
    }

    private function automationTaskDescription(CustomFormTemplate $template, CustomFormSubmission $submission): string
    {
        $lines = collect($template->fields)
            ->map(function (array $field) use ($submission): string {
                $key = (string) $field['key'];
                $value = $submission->payload[$key] ?? '-';

                return ($field['label'] ?? $key).': '.(is_array($value) ? json_encode($value) : ($value ?: '-'));
            })
            ->implode("\n");

        return __('Generated from custom form submission.')."\n\n".$lines;
    }

    private function assertBelongsToCompany(string $modelClass, mixed $id, int $companyId): void
    {
        if ($id === null || $id === '') {
            return;
        }

        if (! $modelClass::query()->whereKey($id)->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages([
                'selected_record' => __('Selected record does not belong to the selected company.'),
            ]);
        }
    }

    private function assertCompanyAccess(User $actor, int $companyId): void
    {
        abort_unless($this->canAccessCompany($actor, $companyId), 403);
    }
}
