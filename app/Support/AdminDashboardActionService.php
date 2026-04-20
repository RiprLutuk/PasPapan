<?php

namespace App\Support;

use App\Mail\CheckoutReminderMail;
use App\Models\ActivityLog;
use App\Models\Attendance;
use Illuminate\Support\Facades\Mail;

class AdminDashboardActionService
{
    public function sendCheckoutReminder(Attendance $attendance): bool
    {
        if (! $attendance->user || ! $attendance->user->email) {
            return false;
        }

        Mail::to($attendance->user->email)->send(new CheckoutReminderMail($attendance->user));

        ActivityLog::record('Notification Sent', 'Sent checkout reminder to ' . $attendance->user->name);

        return true;
    }
}
