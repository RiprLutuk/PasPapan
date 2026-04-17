<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HomeAttendanceStatus extends Component
{
    public $hasCheckedIn = false;
    public $hasCheckedOut = false;
    public $attendance = null;

    public $approvedAbsence = null;
    public $requiresFaceEnrollment = false;
    public $overtime = null;
    public bool $hasApprovedOvertime = false;

    public function mount()
    {
        $this->checkAttendanceStatus();
    }

    public function checkAttendanceStatus()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        $attendanceLocked = \App\Helpers\Editions::attendanceLocked();
        $faceVerificationRequired = !$attendanceLocked && filter_var(
            Setting::getValue('attendance.require_face_verification', true),
            FILTER_VALIDATE_BOOLEAN
        );

        // Check for mandatory face enrollment (Open Core Logic)
        $service = app(\App\Contracts\AttendanceServiceInterface::class);
        $shouldRequireFaceEnrollment = !$attendanceLocked && (
            filter_var(
                Setting::getValue('attendance.require_face_enrollment', false),
                FILTER_VALIDATE_BOOLEAN
            ) || $service->shouldEnforceFaceEnrollment() || $faceVerificationRequired
        );

        if ($shouldRequireFaceEnrollment && !$user->hasFaceRegistered()) {
            $this->requiresFaceEnrollment = true;
        }
        
        $this->attendance = Attendance::with(['shift', 'barcode'])
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($this->attendance) {
            $this->hasCheckedIn = !is_null($this->attendance->time_in);
            $this->hasCheckedOut = !is_null($this->attendance->time_out);

            // Check for approved absence
            if (in_array($this->attendance->status, ['sick', 'excused', 'permission', 'leave']) &&
                $this->attendance->approval_status === Attendance::STATUS_APPROVED
            ) {
                $this->approvedAbsence = $this->attendance;
            }
        }

        // Only approved overtime should affect the home attendance state.
        $approvedOvertime = Overtime::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('status', 'approved')
            ->latest('updated_at')
            ->first();

        $this->overtime = $approvedOvertime;
        $this->hasApprovedOvertime = $approvedOvertime !== null;
    }

    public function render()
    {
        return view('livewire.home-attendance-status');
    }
}
