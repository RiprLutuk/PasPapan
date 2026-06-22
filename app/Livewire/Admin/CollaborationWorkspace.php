<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ScopesCompanySelection;
use App\Livewire\Concerns\ValidatesCompanyId;
use App\Models\ChatThread;
use App\Models\CloudFile;
use App\Models\OnlineMeeting;
use App\Models\Project;
use App\Models\User;
use App\Support\CollaborationRealtime;
use App\Support\CollaborationWorkspaceService;
use App\Support\Contracts\ScopesCompanies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CollaborationWorkspace extends Component
{
    use InteractsWithBanner;
    use ScopesCompanySelection;
    use ValidatesCompanyId;
    use WithFileUploads;

    private const TABS = ['threads', 'files', 'meetings'];

    private const DEFAULT_TAB = 'threads';

    private const THREAD_TYPES = [
        ChatThread::TYPE_GROUP,
        ChatThread::TYPE_PROJECT,
        ChatThread::TYPE_PERSONAL,
    ];

    private const FILE_VISIBILITIES = [
        CloudFile::VISIBILITY_PRIVATE,
        CloudFile::VISIBILITY_COMPANY,
        CloudFile::VISIBILITY_PROJECT,
        CloudFile::VISIBILITY_THREAD,
    ];

    protected CollaborationWorkspaceService $collaboration;

    #[Url(history: true)]
    public string $activeTab = 'threads';

    public string $search = '';

    public string $threadCompanyId = '';

    public string $threadProjectId = '';

    public string $threadType = ChatThread::TYPE_GROUP;

    public string $threadTitle = '';

    /** @var list<string> */
    public array $threadMemberIds = [];

    public string $messageThreadId = '';

    public string $messageBody = '';

    public string $fileCompanyId = '';

    public string $fileProjectId = '';

    public string $fileThreadId = '';

    public string $fileOriginalName = '';

    public string $filePath = '';

    public string $fileMimeType = '';

    public string $fileSize = '0';

    public string $fileVisibility = CloudFile::VISIBILITY_PRIVATE;

    public ?TemporaryUploadedFile $uploadedFile = null;

    public string $meetingCompanyId = '';

    public string $meetingProjectId = '';

    public string $meetingThreadId = '';

    public string $meetingTitle = '';

    public string $meetingProvider = 'external';

    public string $meetingUrl = '';

    public string $meetingStartsAt = '';

    public string $meetingEndsAt = '';

    public string $meetingNotes = '';

    public function boot(CollaborationWorkspaceService $collaboration): void
    {
        Gate::authorize('viewCollaborationWorkspace');

        $this->collaboration = $collaboration;
    }

    public function mount(): void
    {
        $this->normalizeActiveTab();

        $companyId = $this->defaultCompanyId();

        if ($companyId === null) {
            return;
        }

        $this->threadCompanyId = $companyId;
        $this->fileCompanyId = $companyId;
        $this->meetingCompanyId = $companyId;
    }

    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
    }

    public function updatedThreadCompanyId(): void
    {
        $this->reset(['threadProjectId', 'threadMemberIds', 'messageThreadId']);
    }

    public function updatedFileCompanyId(): void
    {
        $this->reset(['fileProjectId', 'fileThreadId']);
    }

    public function updatedMeetingCompanyId(): void
    {
        $this->reset(['meetingProjectId', 'meetingThreadId']);
    }

    public function createThread(): void
    {
        Gate::authorize('manageCollaborationWorkspace');

        $validated = $this->validate([
            'threadCompanyId' => $this->companyIdRules(),
            'threadProjectId' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('company_id', (int) $this->threadCompanyId),
            ],
            'threadType' => ['required', Rule::in(self::THREAD_TYPES)],
            'threadTitle' => ['required', 'string', 'max:180'],
            'threadMemberIds' => ['array', 'max:20'],
            'threadMemberIds.*' => ['string', Rule::exists('users', 'id')],
        ]);

        $thread = $this->collaboration->createThread(auth()->user(), [
            'company_id' => (int) $validated['threadCompanyId'],
            'project_id' => $validated['threadProjectId'] ?: null,
            'type' => $validated['threadType'],
            'title' => $validated['threadTitle'],
        ], $validated['threadMemberIds']);

        $this->messageThreadId = (string) $thread->id;
        $this->reset(['threadProjectId', 'threadTitle', 'threadMemberIds']);
        $this->threadType = ChatThread::TYPE_GROUP;
        $this->banner(__('Conversation created.'));
    }

    public function postMessage(): void
    {
        Gate::authorize('manageCollaborationWorkspace');

        $validated = $this->validate([
            'messageThreadId' => ['required', 'integer', Rule::exists('chat_threads', 'id')],
            'messageBody' => ['required', 'string', 'max:5000'],
        ]);

        $thread = ChatThread::query()->findOrFail((int) $validated['messageThreadId']);
        $this->collaboration->postMessage(auth()->user(), $thread, $validated['messageBody']);

        $this->reset('messageBody');
        $this->banner(__('Message posted.'));
    }

    public function registerFile(): void
    {
        Gate::authorize('manageCollaborationWorkspace');

        $validated = $this->validate([
            'fileCompanyId' => $this->companyIdRules(),
            'fileProjectId' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('company_id', (int) $this->fileCompanyId),
            ],
            'fileThreadId' => [
                'nullable',
                'integer',
                Rule::exists('chat_threads', 'id')->where('company_id', (int) $this->fileCompanyId),
            ],
            'fileOriginalName' => ['required_without:uploadedFile', 'nullable', 'string', 'max:255'],
            'filePath' => ['required_without:uploadedFile', 'nullable', 'string', 'max:1000', 'not_regex:/\\.\\./'],
            'fileMimeType' => ['nullable', 'string', 'max:160'],
            'fileSize' => ['nullable', 'integer', 'min:0'],
            'fileVisibility' => ['required', Rule::in(self::FILE_VISIBILITIES)],
            'uploadedFile' => ['nullable', 'file', 'max:12288', 'mimes:csv,doc,docx,jpeg,jpg,pdf,png,txt,webp,xls,xlsx'],
        ]);

        $path = $validated['filePath'] ? ltrim($validated['filePath'], '/') : null;
        $originalName = $validated['fileOriginalName'];
        $mimeType = $validated['fileMimeType'] ?: null;
        $size = (int) ($validated['fileSize'] ?: 0);

        if ($this->uploadedFile) {
            $originalName = basename($this->uploadedFile->getClientOriginalName());
            $mimeType = $this->uploadedFile->getMimeType();
            $size = $this->uploadedFile->getSize();
            $path = $this->uploadedFile->store(
                'collaboration/'.(int) $validated['fileCompanyId'].'/'.Str::uuid(),
                'local',
            );
        }

        $this->collaboration->registerFile(auth()->user(), [
            'company_id' => (int) $validated['fileCompanyId'],
            'project_id' => $validated['fileProjectId'] ?: null,
            'chat_thread_id' => $validated['fileThreadId'] ?: null,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'visibility' => $validated['fileVisibility'],
        ]);

        $this->reset(['fileProjectId', 'fileThreadId', 'fileOriginalName', 'filePath', 'fileMimeType', 'fileSize', 'uploadedFile']);
        $this->fileSize = '0';
        $this->fileVisibility = CloudFile::VISIBILITY_PRIVATE;
        $this->banner(__('File metadata registered.'));
    }

    public function scheduleMeeting(): void
    {
        Gate::authorize('manageCollaborationWorkspace');

        $validated = $this->validate([
            'meetingCompanyId' => $this->companyIdRules(),
            'meetingProjectId' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('company_id', (int) $this->meetingCompanyId),
            ],
            'meetingThreadId' => [
                'nullable',
                'integer',
                Rule::exists('chat_threads', 'id')->where('company_id', (int) $this->meetingCompanyId),
            ],
            'meetingTitle' => ['required', 'string', 'max:180'],
            'meetingProvider' => ['required', 'string', 'max:40'],
            'meetingUrl' => ['nullable', 'url', 'max:1000'],
            'meetingStartsAt' => ['nullable', 'date'],
            'meetingEndsAt' => ['nullable', 'date', 'after_or_equal:meetingStartsAt'],
            'meetingNotes' => ['nullable', 'string', 'max:1500'],
        ]);

        $this->collaboration->scheduleMeeting(auth()->user(), [
            'company_id' => (int) $validated['meetingCompanyId'],
            'project_id' => $validated['meetingProjectId'] ?: null,
            'chat_thread_id' => $validated['meetingThreadId'] ?: null,
            'title' => $validated['meetingTitle'],
            'provider' => $validated['meetingProvider'],
            'meeting_url' => $validated['meetingUrl'] ?: null,
            'starts_at' => $validated['meetingStartsAt'] ?: null,
            'ends_at' => $validated['meetingEndsAt'] ?: null,
            'notes' => $validated['meetingNotes'] ?: null,
            'status' => OnlineMeeting::STATUS_SCHEDULED,
        ]);

        $this->reset(['meetingProjectId', 'meetingThreadId', 'meetingTitle', 'meetingUrl', 'meetingStartsAt', 'meetingEndsAt', 'meetingNotes']);
        $this->meetingProvider = 'external';
        $this->banner(__('Meeting scheduled.'));
    }

    public function render()
    {
        $user = auth()->user();
        $companyIds = $this->scopedCompanyIds($user);
        $companies = $this->companyOptions($companyIds);

        $projects = Project::query()
            ->whereIn('company_id', $companyIds)
            ->orderBy('name')
            ->get(['id', 'company_id', 'name']);

        $users = User::query()
            ->where('group', '!=', 'superadmin')
            ->where(fn (Builder $query) => $query->whereNull('company_id')->orWhereIn('company_id', $companyIds))
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'email']);

        $threads = ChatThread::query()
            ->with(['company:id,name', 'project:id,name', 'members:id,name', 'messages' => fn ($query) => $query->latest()->limit(3)])
            ->withCount('messages')
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $nested): void {
                $nested
                    ->where('title', 'like', '%'.$this->search.'%')
                    ->orWhereHas('messages', fn (Builder $messageQuery) => $messageQuery->where('body', 'like', '%'.$this->search.'%'));
            }))
            ->latest()
            ->get();

        $files = CloudFile::query()
            ->with(['company:id,name', 'project:id,name', 'owner:id,name'])
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where('original_name', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();

        $meetings = OnlineMeeting::query()
            ->with(['company:id,name', 'project:id,name', 'host:id,name'])
            ->whereIn('company_id', $companyIds)
            ->when($this->search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->orderByRaw('starts_at is null, starts_at asc')
            ->latest('id')
            ->get();

        $threadCompanyId = $this->scopedCompanyId($companyIds, $this->threadCompanyId);
        $fileCompanyId = $this->scopedCompanyId($companyIds, $this->fileCompanyId);
        $meetingCompanyId = $this->scopedCompanyId($companyIds, $this->meetingCompanyId);

        return view('livewire.admin.collaboration-workspace', [
            'companies' => $companies,
            'projects' => $projects,
            'users' => $users,
            'threads' => $threads,
            'files' => $files,
            'meetings' => $meetings,
            'summary' => $this->collaboration->summary($user),
            'threadTypes' => self::THREAD_TYPES,
            'fileVisibilities' => self::FILE_VISIBILITIES,
            'threadProjectOptions' => $this->filterByCompany($projects, $threadCompanyId),
            'threadMemberOptions' => $this->filterByCompany($users, $threadCompanyId),
            'fileProjectOptions' => $this->filterByCompany($projects, $fileCompanyId),
            'fileThreadOptions' => $this->filterByCompany($threads, $fileCompanyId),
            'meetingProjectOptions' => $this->filterByCompany($projects, $meetingCompanyId),
            'meetingThreadOptions' => $this->filterByCompany($threads, $meetingCompanyId),
            'canManage' => $user->can('manageCollaborationWorkspace'),
            'realtimeEnabled' => CollaborationRealtime::enabled(),
            'realtimePollInterval' => CollaborationRealtime::pollInterval(),
        ]);
    }

    protected function companyScopeService(): ScopesCompanies
    {
        return $this->collaboration;
    }

    private function filterByCompany($items, ?int $companyId)
    {
        if ($companyId === null) {
            return $items;
        }

        return $items->filter(fn ($item): bool => $item->company_id === null || (int) $item->company_id === $companyId)->values();
    }
}
