<?php

namespace App\Livewire\Admin\MasterData;

use App\Models\Division;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class DivisionComponent extends Component
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
                Rule::unique('divisions', 'name')->ignore($this->selectedId),
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
        if (Auth::user()->isNotAdmin) {
            return abort(403);
        }
        $this->validate();
        Division::create(['name' => trim($this->name)]);
        $this->creating = false;
        $this->name = null;
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->resetErrorBag();
        $this->creating = false;
        $this->editing = true;
        $division = Division::query()->findOrFail($id);
        $this->name = $division->name;
        $this->selectedId = $id;
    }

    public function update()
    {
        if (Auth::user()->isNotAdmin) {
            return abort(403);
        }
        $this->validate();
        $division = Division::query()->findOrFail($this->selectedId);
        $division->update(['name' => trim($this->name)]);
        $this->editing = false;
        $this->name = null;
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
        if (Auth::user()->isNotAdmin) {
            return abort(403);
        }
        $division = Division::query()->findOrFail($this->selectedId);
        $division->delete();
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
        $divisions = Division::query()
            ->when(
                filled($this->search),
                fn ($query) => $query->where('name', 'like', '%' . trim($this->search) . '%')
            )
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.master-data.division', ['divisions' => $divisions]);
    }
}
