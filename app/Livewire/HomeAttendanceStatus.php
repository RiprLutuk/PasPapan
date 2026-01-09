<?php

namespace App\Livewire;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HomeAttendanceStatus extends Component
{
    public $hasCheckedIn = false;
    public $hasCheckedOut = false;
    public $attendance = null;

    public function mount()
    {
        $this->checkAttendanceStatus();
    }

    public function checkAttendanceStatus()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        
        $this->attendance = Attendance::with(['shift', 'barcode'])
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($this->attendance) {
            $this->hasCheckedIn = !is_null($this->attendance->time_in);
            $this->hasCheckedOut = !is_null($this->attendance->time_out);
        }
    }

    public function render()
    {
        return view('livewire.home-attendance-status');
    }
}
