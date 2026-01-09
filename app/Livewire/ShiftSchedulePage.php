<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ShiftSchedulePage extends Component
{
    public function render()
    {
        // Fetch upcoming schedules for the user (from Today onwards)
        // Limit to next 30 days for clarity
        $schedules = Schedule::with('shift')
            ->where('user_id', Auth::id())
            ->where('date', '>=', Carbon::today())
            ->where('date', '<=', Carbon::today()->addDays(30))
            ->orderBy('date', 'asc')
            ->get();

        return view('livewire.shift-schedule-page', [
            'schedules' => $schedules
        ])->layout('layouts.app');
    }
}
