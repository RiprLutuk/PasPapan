<?php

namespace App\Livewire\Admin\MasterData;

use App\Models\JobTitle;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class JobTitleComponent extends Component
{
    use InteractsWithBanner, WithPagination;

    public $name;
    public $job_level_id;
    public $division_id;
    
    public $deleteName = null;
    public $creating = false;
    public $editing = false;
    public $confirmingDeletion = false;
    public $selectedId = null;
    public string $search = '';
    public string $divisionFilter = 'all';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'divisionFilter' => ['except' => 'all'],
    ];

    protected $rules = [
        'name' => ['required', 'string', 'max:255'], // Unique validation needs more complex logic if scoping by division, but simple global unique is fine for now or scope later.
        'job_level_id' => ['required', 'exists:job_levels,id'],
        'division_id' => ['nullable', 'exists:divisions,id'],
    ];

    public function showCreating()
    {
        $this->resetErrorBag();
        $this->name = null;
        $this->job_level_id = null;
        $this->division_id = null;
        $this->selectedId = null;
        $this->editing = false;
        $this->creating = true;
    }

    public function create()
    {
        Gate::authorize('manageMasterData');
        $this->validate();
        JobTitle::create([
            'name' => trim($this->name),
            'job_level_id' => $this->job_level_id,
            'division_id' => $this->division_id,
        ]);
        $this->creating = false;
        $this->name = null;
        $this->job_level_id = null;
        $this->division_id = null;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->resetErrorBag();
        $this->creating = false;
        $this->editing = true;
        $jobTitle = JobTitle::query()->findOrFail($id);
        $this->name = $jobTitle->name;
        $this->job_level_id = $jobTitle->job_level_id;
        $this->division_id = $jobTitle->division_id;
        $this->selectedId = $id;
    }

    public function update()
    {
        Gate::authorize('manageMasterData');
        $this->validate();
        $jobTitle = JobTitle::query()->findOrFail($this->selectedId);
        $jobTitle->update([
            'name' => trim($this->name),
            'job_level_id' => $this->job_level_id,
            'division_id' => $this->division_id,
        ]);
        $this->editing = false;
        $this->name = null;
        $this->job_level_id = null;
        $this->division_id = null;
        $this->selectedId = null;
        $this->banner(__('Updated successfully.'));
    }

    public function confirmDeletion($id, $name)
    {
        $this->deleteName = $name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete()
    {
        Gate::authorize('manageMasterData');
        $jobTitle = JobTitle::query()->findOrFail($this->selectedId);
        $jobTitle->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
        $this->banner(__('Deleted successfully.'));
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDivisionFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $jobTitles = JobTitle::query()
            ->with(['jobLevel', 'division'])
            ->when(
                filled($this->search),
                fn ($query) => $query->where(function ($subQuery) {
                    $subQuery
                        ->where('name', 'like', '%' . trim($this->search) . '%')
                        ->orWhereHas('division', fn ($divisionQuery) => $divisionQuery->where('name', 'like', '%' . trim($this->search) . '%'))
                        ->orWhereHas('jobLevel', fn ($levelQuery) => $levelQuery->where('name', 'like', '%' . trim($this->search) . '%'));
                })
            )
            ->when(
                $this->divisionFilter !== 'all',
                fn ($query) => $query->where('division_id', $this->divisionFilter)
            )
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.master-data.job-title', [
            'jobTitles' => $jobTitles,
            'jobLevels' => \App\Models\JobLevel::orderBy('rank')->get(),
            'divisions' => \App\Models\Division::all(),
        ]);
    }
}
