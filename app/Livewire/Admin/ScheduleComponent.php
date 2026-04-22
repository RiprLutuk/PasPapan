<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ScheduleComponent extends Component
{

    public $month;
    public $year;
    public $selectedUser = null;
    public $users = [];
    public $shifts = [];

    // Modal State
    public $showModal = false;
    public $selectedDate = null;
    public $selectedShiftId = null;
    public $selectedIsOff = false;

    public function boot(): void
    {
        Gate::authorize('manageSchedules');
    }

    public function mount()
    {
        $this->month = (int) date('m');
        $this->year = (int) date('Y');
        $this->users = \App\Models\User::orderBy('name')->get();
        $this->shifts = \App\Models\Shift::all();

        // Auto-select first user if available
        if ($this->users->count() > 0) {
            $this->selectedUser = $this->users->first()->id;
        }
    }

    public function updatedMonth($value): void
    {
        $month = (int) $value;

        if ($month < 1 || $month > 12) {
            $month = (int) date('m');
        }

        $this->month = $month;
        $this->showModal = false;
    }

    public function updatedYear($value): void
    {
        $year = (int) $value;

        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $this->year = $year;
        $this->showModal = false;
    }

    public function openModal($date)
    {
        if (!$this->selectedUser) return;

        $this->selectedDate = $date;
        $this->showModal = true;

        // Fetch existing schedule
        $schedule = \App\Models\Schedule::where('user_id', $this->selectedUser)
            ->where('date', $date)
            ->first();

        if ($schedule) {
            $this->selectedShiftId = $schedule->shift_id;
            $this->selectedIsOff = $schedule->is_off;
        } else {
            $this->selectedShiftId = null;
            $this->selectedIsOff = false;
        }
    }

    public function saveSchedule()
    {
        if (!$this->selectedUser || !$this->selectedDate) return;

        if ($this->selectedShiftId) {
            \App\Models\Schedule::updateOrCreate(
                [
                    'user_id' => $this->selectedUser,
                    'date' => $this->selectedDate,
                ],
                [
                    'shift_id' => $this->selectedShiftId,
                    'is_off' => $this->selectedIsOff,
                ]
            );
        } else {
            // If No shift selected, Delete any existing schedule (Revert to Auto)
            \App\Models\Schedule::where('user_id', $this->selectedUser)
                ->where('date', $this->selectedDate)
                ->delete();
        }

        $this->showModal = false;
        $this->js("Swal.fire({icon: 'success', title: 'Schedule Updated', timer: 1500, showConfirmButton: false})");
    }

    public function render()
    {
        // Ensure we have valid month/year, fallback to current
        $year = (int) ((filter_var($this->year, FILTER_VALIDATE_INT) !== false) ? $this->year : date('Y'));
        $month = (int) ((filter_var($this->month, FILTER_VALIDATE_INT) !== false && $this->month >= 1 && $this->month <= 12) ? $this->month : date('m'));

        try {
            $date = \Carbon\Carbon::createFromDate($year, $month, 1);
        } catch (\Exception $e) {
            $date = \Carbon\Carbon::now()->startOfMonth();
            $this->year = $date->year;
            $this->month = $date->format('m');
        }
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $startGrid = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $endGrid = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);

        $dates = [];
        $current = $startGrid->copy();
        while ($current <= $endGrid) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        $schedules = [];
        if ($this->selectedUser) {
            $schedules = \App\Models\Schedule::where('user_id', $this->selectedUser)
                ->whereBetween('date', [$startGrid->toDateString(), $endGrid->toDateString()])
                ->with('shift')
                ->get()
                ->keyBy(fn($item) => $item->date->toDateString());
        }

        return view('livewire.admin.schedule-component', [
            'calendar' => $dates,
            'schedules' => $schedules,
            'currentMonth' => $date->month,
        ])->layout('layouts.app');
    }
}
