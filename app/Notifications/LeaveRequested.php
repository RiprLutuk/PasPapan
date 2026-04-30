<?php

namespace App\Notifications;

use App\Support\AnnouncementRefresh;
use Illuminate\Notifications\Notification;

class LeaveRequested extends Notification
{
    public $attendance;

    public $fromDate;

    public $toDate;

    public $totalDays;

    public function __construct($attendance, $fromDate = null, $toDate = null)
    {
        $this->attendance = $attendance;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;

        // Calculate total days
        if ($fromDate && $toDate) {
            $this->totalDays = $fromDate->diffInDays($toDate) + 1;
        } else {
            $this->totalDays = 1;
        }
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (AnnouncementRefresh::broadcastingEnabled()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        $leaveType = $this->attendance->leaveType?->name ?: ($this->attendance->status === 'sick'
            ? __('Sick Leave')
            : __('Leave'));

        if ($this->fromDate && $this->toDate && $this->totalDays > 1) {
            $dateDisplay = $this->fromDate->translatedFormat('d M').' - '.$this->toDate->translatedFormat('d M Y');
            $message = __('Leave request from :name for :type (:count)', [
                'name' => $this->attendance->user->name,
                'type' => $leaveType,
                'count' => trans_choice(':count day|:count days', $this->totalDays, ['count' => $this->totalDays]),
            ]);
        } else {
            $dateDisplay = $this->attendance->date->format('Y-m-d');
            $message = __('Leave request from :name for :type', [
                'name' => $this->attendance->user->name,
                'type' => $leaveType,
            ]);
        }

        return [
            'type' => 'leave_request',
            'title' => __('New Leave Request'),
            'user_id' => $this->attendance->user_id,
            'user_name' => $this->attendance->user->name,
            'leave_type' => $leaveType,
            'date' => $dateDisplay,
            'total_days' => $this->totalDays,
            'message' => $message,
            'url' => route('admin.leaves', absolute: false),
        ];
    }
}
