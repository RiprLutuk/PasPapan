<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EmployeeComponent extends Component
{
    use InteractsWithBanner, WithFileUploads, WithPagination;

    public UserForm $form;

    public $deleteName = null;

    public $creating = false;

    public $editing = false;

    public $confirmingDeletion = false;

    public $confirmingDeletionReview = false;

    public $selectedId = null;

    public $showDetail = null;

    public ?string $deletionReviewAction = null;

    public ?string $deletionReviewEmployeeName = null;

    public ?string $deletionReviewReason = null;

    public string $deletionReviewNotes = '';

    // filter
    public ?string $division = null;

    public ?string $jobTitle = null;

    public ?string $education = null;

    public ?string $employmentStatus = null;

    public ?string $search = null;

    public function boot(): void
    {
        Gate::authorize('viewEmployees');
    }

    public function show($id)
    {
        $this->form->setUser(User::find($id));
        $this->showDetail = true;
    }

    public function showCreating()
    {
        Gate::authorize('manageUserRecord', [null, 'user']);
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->creating = true;
        $this->form->password = 'password';
        $this->form->employment_status = User::EMPLOYMENT_STATUS_ACTIVE;
    }

    public function create()
    {
        Gate::authorize('manageUserRecord', [null, 'user']);
        $this->form->store();
        $this->creating = false;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->editing = true;
        /** @var User $user */
        $user = User::findOrFail($id);
        Gate::authorize('manageUserRecord', [$user, 'user']);
        $this->form->setUser($user);
    }

    public function update()
    {
        Gate::authorize('manageUserRecord', [$this->form->user, 'user']);
        $this->form->update();
        $this->editing = false;
        $this->banner(__('Updated successfully.'));
    }

    public function deleteProfilePhoto()
    {
        $this->form->deleteProfilePhoto();
    }

    public function confirmDeletion($id)
    {
        $user = User::findOrFail($id);
        Gate::authorize('manageUserRecord', [$user, 'user']);
        $this->deleteName = $user->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $user->id;
    }

    public function delete()
    {
        $user = User::findOrFail($this->selectedId);
        Gate::authorize('manageUserRecord', [$user, 'user']);
        $this->form->setUser($user)->delete();
        $this->confirmingDeletion = false;
        $this->banner(__('Deleted successfully.'));
    }

    public function confirmDeletionApproval($id): void
    {
        Gate::authorize('approveEmployeeAccountDeletion');

        $user = User::findOrFail($id);

        if (! $user->hasPendingAccountDeletionRequest()) {
            abort(404);
        }

        $this->selectedId = $user->id;
        $this->deletionReviewAction = 'approve';
        $this->deletionReviewEmployeeName = $user->name;
        $this->deletionReviewReason = $user->account_deletion_reason;
        $this->deletionReviewNotes = '';
        $this->confirmingDeletionReview = true;
    }

    public function confirmDeletionRejection($id): void
    {
        Gate::authorize('approveEmployeeAccountDeletion');

        $user = User::findOrFail($id);

        if (! $user->hasPendingAccountDeletionRequest()) {
            abort(404);
        }

        $this->selectedId = $user->id;
        $this->deletionReviewAction = 'reject';
        $this->deletionReviewEmployeeName = $user->name;
        $this->deletionReviewReason = $user->account_deletion_reason;
        $this->deletionReviewNotes = '';
        $this->confirmingDeletionReview = true;
    }

    public function approveDeletionRequest(): void
    {
        Gate::authorize('approveEmployeeAccountDeletion');

        $user = User::findOrFail($this->selectedId);
        $user->approveAccountDeletion(auth()->user(), $this->deletionReviewNotes);

        $this->resetDeletionReviewState();
        $this->banner(__('Account deletion request approved.'));
    }

    public function rejectDeletionRequest(): void
    {
        Gate::authorize('approveEmployeeAccountDeletion');

        $this->validate([
            'deletionReviewNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::findOrFail($this->selectedId);
        $user->rejectAccountDeletion(auth()->user(), $this->deletionReviewNotes);

        $this->resetDeletionReviewState();
        $this->banner(__('Account deletion request rejected.'));
    }

    protected function resetDeletionReviewState(): void
    {
        $this->confirmingDeletionReview = false;
        $this->selectedId = null;
        $this->deletionReviewAction = null;
        $this->deletionReviewEmployeeName = null;
        $this->deletionReviewReason = null;
        $this->deletionReviewNotes = '';
    }

    public function updated($property, $value)
    {
        if ($property === 'form.job_title_id' && $value) {
            $jobTitle = \App\Models\JobTitle::find($value);
            if ($jobTitle && $jobTitle->division_id) {
                $this->form->division_id = $jobTitle->division_id;
            }
            $this->form->manager_id = null;
        }

        if ($property === 'form.division_id') {
            $this->form->job_title_id = null;
            $this->form->manager_id = null;
        }

        if ($property === 'form.provinsi_kode') {
            $this->form->kabupaten_kode = null;
            $this->form->kecamatan_kode = null;
            $this->form->kelurahan_kode = null;
        }
        if ($property === 'form.kabupaten_kode') {
            $this->form->kecamatan_kode = null;
            $this->form->kelurahan_kode = null;
        }
        if ($property === 'form.kecamatan_kode') {
            $this->form->kelurahan_kode = null;
        }
    }

    public function render()
    {
        $employeeQuery = User::where('group', 'user')
            ->managedBy(auth()->user())
            ->when($this->search, function (Builder $q) {
                $q->where(function ($subQ) {
                    $subQ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nip', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->division, fn (Builder $q) => $q->where('division_id', $this->division))
            ->when($this->jobTitle, fn (Builder $q) => $q->where('job_title_id', $this->jobTitle))
            ->when($this->education, fn (Builder $q) => $q->where('education_id', $this->education))
            ->when($this->employmentStatus, fn (Builder $q) => $q->where('employment_status', $this->employmentStatus));

        $users = (clone $employeeQuery)
            ->with(['division', 'jobTitle', 'education', 'directManager'])
            ->orderBy('name')
            ->paginate(20);

        $statusCounts = (clone $employeeQuery)
            ->selectRaw('employment_status, count(*) as aggregate_count')
            ->whereIn('employment_status', [
                User::EMPLOYMENT_STATUS_ACTIVE,
                User::EMPLOYMENT_STATUS_RESIGNED,
                User::EMPLOYMENT_STATUS_DELETION_REQUESTED,
            ])
            ->groupBy('employment_status')
            ->pluck('aggregate_count', 'employment_status');

        $statusSummary = [
            'active' => (int) ($statusCounts[User::EMPLOYMENT_STATUS_ACTIVE] ?? 0),
            'resigned' => (int) ($statusCounts[User::EMPLOYMENT_STATUS_RESIGNED] ?? 0),
            'pending_deletion' => (int) ($statusCounts[User::EMPLOYMENT_STATUS_DELETION_REQUESTED] ?? 0),
        ];

        $availableJobTitles = \App\Models\JobTitle::query()
            ->when($this->form->division_id, function ($q) {
                $q->where('division_id', $this->form->division_id)
                    ->orWhereNull('division_id'); // Include global titles if any
            })
            ->get();

        $provinces = \App\Models\Wilayah::whereRaw('LENGTH(kode) = 2')->orderBy('nama')->get();
        $regencies = $this->form->provinsi_kode ? \App\Models\Wilayah::where('kode', 'like', $this->form->provinsi_kode.'.%')->whereRaw('LENGTH(kode) = 5')->orderBy('nama')->get() : collect();
        $districts = $this->form->kabupaten_kode ? \App\Models\Wilayah::where('kode', 'like', $this->form->kabupaten_kode.'.%')->whereRaw('LENGTH(kode) = 8')->orderBy('nama')->get() : collect();
        $villages = $this->form->kecamatan_kode ? \App\Models\Wilayah::where('kode', 'like', $this->form->kecamatan_kode.'.%')->whereRaw('LENGTH(kode) = 13')->orderBy('nama')->get() : collect();

        $managerOptions = collect();

        if ($this->creating || $this->editing) {
            $currentEmployeeId = $this->form->user?->id;

            $managerOptions = User::query()
                ->where('group', 'user')
                ->managedBy(auth()->user())
                ->when($currentEmployeeId, fn (Builder $q) => $q->where('id', '!=', $currentEmployeeId))
                ->with(['division', 'jobTitle'])
                ->orderBy('name')
                ->get(['id', 'name', 'division_id', 'job_title_id', 'employment_status'])
                ->map(function (User $manager) {
                    $details = collect([
                        $manager->jobTitle?->name,
                        $manager->division?->name,
                    ])->filter()->implode(' / ');

                    return [
                        'id' => $manager->id,
                        'name' => $details ? "{$manager->name} - {$details}" : $manager->name,
                    ];
                })
                ->values();
        }

        return view('livewire.admin.employees', [
            'users' => $users,
            'availableJobTitles' => $availableJobTitles,
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
            'villages' => $villages,
            'statusSummary' => $statusSummary,
            'employmentStatuses' => User::employmentStatuses(),
            'manualEmploymentStatuses' => User::manuallyManagedEmploymentStatuses(),
            'managerOptions' => $managerOptions,
            'canManageEmployees' => Gate::allows('manageUserRecord', [null, 'user']),
            'canManageEmployeeStatuses' => Gate::allows('manageEmployeeStatuses'),
            'canApproveDeletionRequests' => Gate::allows('approveEmployeeAccountDeletion'),
        ]);
    }
}
