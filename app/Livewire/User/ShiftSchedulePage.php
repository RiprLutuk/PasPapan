<?php

namespace App\Livewire\User;

use App\Models\Schedule;
use App\Models\ShiftSwapRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

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

        $pendingSwapScheduleIds = ShiftSwapRequest::query()
            ->where('user_id', Auth::id())
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->pluck('schedule_id')
            ->all();

        return view('livewire.user.shift-schedule-page', [
            'schedules' => $schedules,
            'pendingSwapScheduleIds' => $pendingSwapScheduleIds,
        ])->layout('layouts.app');
    }
}
