<?php

namespace App\Support;

use App\Events\CollaborationWorkspaceUpdated;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\CloudFile;
use App\Models\Company;
use App\Models\OnlineMeeting;
use App\Models\Project;
use App\Models\User;
use App\Support\Concerns\ScopesCompaniesByActor;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollaborationWorkspaceService implements ScopesCompanies
{
    use ScopesCompaniesByActor;

    public function __construct(
        private readonly AttachmentPathValidator $attachmentPathValidator,
    ) {}

    public function canAccessCompany(User $actor, int|string|null $companyId): bool
    {
        if ($companyId === null || $companyId === '') {
            return $actor->isSuperadmin;
        }

        return $actor->isSuperadmin
            || (int) $actor->company_id === (int) $companyId;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $memberIds
     */
    public function createThread(User $actor, array $data, array $memberIds = []): ChatThread
    {
        $companyId = $data['company_id'] ?? null;
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertProjectScope($data['project_id'] ?? null, $companyId);
        $this->assertMemberScope($actor, $memberIds, $companyId);

        return DB::transaction(function () use ($actor, $data, $memberIds): ChatThread {
            $thread = ChatThread::query()->create([
                ...$data,
                'created_by' => $actor->id,
            ]);

            $members = collect([$actor->id, ...$memberIds])
                ->filter()
                ->unique()
                ->mapWithKeys(fn (string $userId): array => [
                    $userId => ['role' => $userId === $actor->id ? 'owner' : 'member'],
                ])
                ->all();

            $thread->members()->syncWithoutDetaching($members);

            return $thread->fresh(['members']);
        });
    }

    /**
     * @param  array<string, mixed>|null  $fileData
     */
    public function postMessage(User $actor, ChatThread $thread, string $body, ?array $fileData = null): ChatMessage
    {
        $this->assertThreadAccess($actor, $thread);

        $message = DB::transaction(function () use ($actor, $thread, $body, $fileData): ChatMessage {
            $file = null;

            if ($fileData !== null) {
                abort_unless(
                    isset($fileData['path']) && is_string($fileData['path']) && $this->attachmentPathValidator->isSafeRelativePath($fileData['path']),
                    422,
                    'File path must be a safe relative path.',
                );

                $file = CloudFile::query()->create([
                    ...$fileData,
                    'company_id' => $thread->company_id,
                    'project_id' => $thread->project_id,
                    'chat_thread_id' => $thread->id,
                    'owner_id' => $actor->id,
                    'disk' => $fileData['disk'] ?? 'local',
                    'visibility' => CloudFile::VISIBILITY_THREAD,
                ]);
            }

            $message = $thread->messages()->create([
                'user_id' => $actor->id,
                'body' => $body,
                'metadata' => $file ? ['cloud_file_id' => $file->id] : null,
            ]);

            $thread->members()->syncWithoutDetaching([
                $actor->id => [
                    'role' => 'member',
                    'last_read_at' => now(),
                ],
            ]);

            if ($file) {
                $this->broadcastWorkspaceUpdate($thread->company_id, 'file.created', 'file', $file->id);
            }

            return $message;
        });

        $this->broadcastWorkspaceUpdate($thread->company_id, 'message.created', 'message', $message->id);

        return $message;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerFile(User $actor, array $data): CloudFile
    {
        $companyId = $data['company_id'] ?? null;
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertProjectScope($data['project_id'] ?? null, $companyId);
        $this->assertThreadReference($actor, $data['chat_thread_id'] ?? null, $companyId);

        abort_unless(
            isset($data['path']) && is_string($data['path']) && $this->attachmentPathValidator->isSafeRelativePath($data['path']),
            422,
            'File path must be a safe relative path.',
        );

        $file = CloudFile::query()->create([
            ...$data,
            'owner_id' => $actor->id,
            'disk' => $data['disk'] ?? config('filesystems.default', 'local'),
            'visibility' => $data['visibility'] ?? CloudFile::VISIBILITY_PRIVATE,
        ]);

        $this->broadcastWorkspaceUpdate($file->company_id, 'file.created', 'file', $file->id);

        return $file;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleMeeting(User $actor, array $data): OnlineMeeting
    {
        $companyId = $data['company_id'] ?? null;
        $this->assertCompanyAccess($actor, $companyId);
        $this->assertProjectScope($data['project_id'] ?? null, $companyId);
        $this->assertThreadReference($actor, $data['chat_thread_id'] ?? null, $companyId);

        $meeting = OnlineMeeting::query()->create([
            ...$data,
            'host_id' => $actor->id,
            'status' => $data['status'] ?? OnlineMeeting::STATUS_SCHEDULED,
        ]);

        $this->broadcastWorkspaceUpdate($meeting->company_id, 'meeting.created', 'meeting', $meeting->id);

        return $meeting;
    }

    /**
     * @return array{threads:int,messages:int,files:int,meetings:int}
     */
    public function summary(User $actor): array
    {
        $companyIds = $this->scopeCompanies(Company::query(), $actor)->pluck('id')->all();

        return [
            'threads' => ChatThread::query()->whereIn('company_id', $companyIds)->count(),
            'messages' => ChatMessage::query()
                ->whereHas('thread', fn (Builder $query) => $query->whereIn('company_id', $companyIds))
                ->count(),
            'files' => CloudFile::query()->whereIn('company_id', $companyIds)->count(),
            'meetings' => OnlineMeeting::query()->whereIn('company_id', $companyIds)->count(),
        ];
    }

    private function assertCompanyAccess(User $actor, int|string|null $companyId): void
    {
        abort_unless($this->canAccessCompany($actor, $companyId), 403);
    }

    private function assertProjectScope(mixed $projectId, int|string|null $companyId): void
    {
        if ($projectId === null || $projectId === '') {
            return;
        }

        abort_unless(
            Project::query()
                ->whereKey($projectId)
                ->when($companyId !== null && $companyId !== '', fn (Builder $query) => $query->where('company_id', (int) $companyId))
                ->exists(),
            422,
            'Selected project does not belong to the selected company.',
        );
    }

    /**
     * @param  list<string>  $memberIds
     */
    private function assertMemberScope(User $actor, array $memberIds, int|string|null $companyId): void
    {
        if ($memberIds === []) {
            return;
        }

        $allowedCompanyId = $companyId === null || $companyId === '' ? $actor->company_id : (int) $companyId;

        $validCount = User::query()
            ->whereIn('id', $memberIds)
            ->where(fn (Builder $query) => $query
                ->whereNull('company_id')
                ->when($allowedCompanyId !== null, fn (Builder $query) => $query->orWhere('company_id', $allowedCompanyId)))
            ->count();

        abort_unless($validCount === count(array_unique($memberIds)), 422, 'Selected members must belong to the selected company.');
    }

    private function assertThreadAccess(User $actor, ChatThread $thread): void
    {
        $thread->loadMissing('members:id');

        abort_unless($this->canAccessCompany($actor, $thread->company_id), 403);

        if ($actor->can('manageCollaborationWorkspace')) {
            return;
        }

        abort_unless($thread->members->contains('id', $actor->id), 403);
    }

    private function assertThreadReference(User $actor, mixed $threadId, int|string|null $companyId): void
    {
        if ($threadId === null || $threadId === '') {
            return;
        }

        $thread = ChatThread::query()->findOrFail($threadId);

        abort_unless($this->canAccessCompany($actor, $thread->company_id), 403);

        if ($companyId !== null && $companyId !== '') {
            abort_unless((int) $thread->company_id === (int) $companyId, 422, 'Selected thread does not belong to the selected company.');
        }
    }

    private function broadcastWorkspaceUpdate(int|string|null $companyId, string $action, string $resource, int|string|null $resourceId): void
    {
        if ($companyId === null || $companyId === '' || ! CollaborationRealtime::enabled()) {
            return;
        }

        try {
            CollaborationWorkspaceUpdated::dispatch((int) $companyId, $action, $resource, $resourceId);
        } catch (Throwable $exception) {
            Log::warning('Collaboration realtime broadcast failed; falling back to polling.', [
                'company_id' => $companyId,
                'action' => $action,
                'resource' => $resource,
                'resource_id' => $resourceId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
