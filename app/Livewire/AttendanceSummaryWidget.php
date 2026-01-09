<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSummaryWidget extends Component
{
    protected $listeners = ['attendance-recorded' => '$refresh'];

    public function render()
    {
        $user = Auth::user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $absentCount = $attendances->where('status', 'absent')->count(); // Or calculate based on work days
        
        // Simple calculation for remaining leave if using annual quota
        // Assuming annual quota is stored in settings or user model, for now simplified
        $leaveUsed = $attendances->whereIn('status', ['excused', 'sick'])->count();

        return view('livewire.attendance-summary-widget', [
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'leaveUsed' => $leaveUsed,
            'monthName' => Carbon::now()->translatedFormat('F'),
        ]);
    }
}
