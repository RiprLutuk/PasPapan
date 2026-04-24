<?php

namespace App\Livewire\Admin;

use App\Models\Holiday;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class HolidayManager extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $recurringFilter = 'all';

    public string $monthFilter = 'all';

    public $showModal = false;

    public $editMode = false;

    public $holidayId = null;

    public $date = '';

    public $name = '';

    public $description = '';

    public $is_recurring = false;

    protected $rules = [
        'date' => 'required|date',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'is_recurring' => 'boolean',
    ];

    public function boot(): void
    {
        Gate::authorize('manageHolidays');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRecurringFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMonthFilter(): void
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->authorize('create', Holiday::class);
        $this->reset(['holidayId', 'date', 'name', 'description', 'is_recurring']);
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $holiday = Holiday::findOrFail($id);
        $this->authorize('update', $holiday);
        $this->holidayId = $holiday->id;
        $this->date = $holiday->date->format('Y-m-d');
        $this->name = $holiday->name;
        $this->description = $holiday->description;
        $this->is_recurring = $holiday->is_recurring;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'date' => $this->date,
            'name' => $this->name,
            'description' => $this->description,
            'is_recurring' => $this->is_recurring,
        ];

        if ($this->editMode) {
            $holiday = Holiday::findOrFail($this->holidayId);
            $this->authorize('update', $holiday);
            $holiday->update($data);
            session()->flash('success', __('Holiday updated successfully.'));
        } else {
            $this->authorize('create', Holiday::class);
            Holiday::create($data);
            session()->flash('success', __('Holiday created successfully.'));
        }

        $this->showModal = false;
        $this->reset(['holidayId', 'date', 'name', 'description', 'is_recurring']);
    }

    public function delete($id)
    {
        $holiday = Holiday::findOrFail($id);
        $this->authorize('delete', $holiday);
        $holiday->delete();
        session()->flash('success', __('Holiday deleted successfully.'));
    }

    public function render()
    {
        return view('livewire.admin.holiday-manager', [
            'holidays' => Holiday::query()
                ->when($this->search, function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('description', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->recurringFilter !== 'all', function ($query) {
                    $query->where('is_recurring', $this->recurringFilter === 'recurring');
                })
                ->when($this->monthFilter !== 'all', function ($query) {
                    $query->whereMonth('date', (int) $this->monthFilter);
                })
                ->orderBy('date', 'desc')
                ->paginate(10),
        ]);
    }
}
