<?php

namespace App\Livewire\Admin\MasterData;

use App\Livewire\Forms\ShiftForm;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftComponent extends Component
{
    use InteractsWithBanner, WithPagination;

    public ShiftForm $form;

    public ?string $deleteName = null;

    public bool $showFormModal = false;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public string $search = '';

    public string $typeFilter = 'all';

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all'],
    ];

    public function showCreating()
    {
        $this->form->resetErrorBag();
        $this->form->reset();
        $this->editing = false;
        $this->showFormModal = true;
    }

    public function create()
    {
        $this->form->store();
        $this->closeFormModal();
        $this->banner(__('Created successfully.'));
    }

    public function edit($id)
    {
        $this->form->resetErrorBag();
        $this->editing = true;
        $this->showFormModal = true;
        /** @var Shift $shift */
        $shift = Shift::query()->findOrFail($id);
        $this->form->setShift($shift);
    }

    public function update()
    {
        $this->form->update();
        $this->closeFormModal();
        $this->banner(__('Updated successfully.'));
    }

    public function confirmDeletion($id)
    {
        $shift = Shift::query()->findOrFail($id);
        $this->deleteName = $shift->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $shift->id;
    }

    public function delete()
    {
        $shift = Shift::query()->findOrFail($this->selectedId);
        $this->form->setShift($shift)->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
        $this->banner(__('Deleted successfully.'));
    }

    public function closeFormModal()
    {
        $this->showFormModal = false;
        $this->editing = false;
        $this->form->resetErrorBag();
        $this->form->reset();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->typeFilter = 'all';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $shifts = $this->buildShiftQuery()->paginate($this->perPage);

        return view('livewire.admin.master-data.shift', [
            'shifts' => $shifts,
            'stats' => $this->overviewStats(),
            'hasFilters' => $this->hasActiveFilters(),
        ]);
    }

    protected function buildShiftQuery(): Builder
    {
        return Shift::query()
            ->when(
                filled($this->search),
                fn (Builder $query) => $query->where('name', 'like', '%'.trim($this->search).'%')
            )
            ->when(
                $this->typeFilter === 'daytime',
                fn (Builder $query) => $query->whereNotNull('end_time')->whereColumn('end_time', '>', 'start_time')
            )
            ->when(
                $this->typeFilter === 'overnight',
                fn (Builder $query) => $query->whereNotNull('end_time')->whereColumn('end_time', '<=', 'start_time')
            )
            ->when(
                $this->typeFilter === 'open-ended',
                fn (Builder $query) => $query->whereNull('end_time')
            )
            ->orderBy('start_time')
            ->orderBy('name');
    }

    protected function overviewStats(): array
    {
        return [
            'total' => Shift::query()->count(),
            'daytime' => Shift::query()->whereNotNull('end_time')->whereColumn('end_time', '>', 'start_time')->count(),
            'overnight' => Shift::query()->whereNotNull('end_time')->whereColumn('end_time', '<=', 'start_time')->count(),
            'open-ended' => Shift::query()->whereNull('end_time')->count(),
        ];
    }

    protected function hasActiveFilters(): bool
    {
        return filled($this->search) || $this->typeFilter !== 'all';
    }
}
