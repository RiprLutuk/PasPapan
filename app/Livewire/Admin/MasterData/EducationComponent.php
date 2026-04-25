<?php

namespace App\Livewire\Admin\MasterData;

use App\Models\Education;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class EducationComponent extends Component
{
    use InteractsWithBanner, WithPagination;

    public ?string $name = null;

    public ?string $deleteName = null;

    public bool $creating = false;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public string $search = '';

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('educations', 'name')->ignore($this->selectedId),
            ],
        ];
    }

    public function showCreating()
    {
        $this->resetErrorBag();
        $this->name = null;
        $this->selectedId = null;
        $this->editing = false;
        $this->creating = true;
    }

    public function create()
    {
        Gate::authorize('manageMasterData');
        $this->validate();
        Education::create(['name' => trim($this->name)]);
        $this->creating = false;
        $this->name = null;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->resetErrorBag();
        $this->creating = false;
        $this->editing = true;
        $education = Education::query()->findOrFail($id);
        $this->name = $education->name;
        $this->selectedId = $id;
    }

    public function update()
    {
        Gate::authorize('manageMasterData');
        $this->validate();
        $education = Education::query()->findOrFail($this->selectedId);
        $education->update(['name' => trim($this->name)]);
        $this->editing = false;
        $this->name = null;
        $this->selectedId = null;
        $this->banner(__('Updated successfully.'));
    }

    public function confirmDeletion($id)
    {
        $education = Education::query()->findOrFail($id);
        $this->deleteName = $education->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $education->id;
    }

    public function delete()
    {
        Gate::authorize('manageMasterData');
        $education = Education::query()->findOrFail($this->selectedId);
        $education->delete();
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

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $educations = Education::query()
            ->when(
                filled($this->search),
                fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%')
            )
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.master-data.education', ['educations' => $educations]);
    }
}
