<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Notifications\Notification;

class OvertimeRequested extends Notification
{
    public $overtime;

    public function __construct(Overtime $overtime)
    {
        $this->overtime = $overtime;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overtime_request',
            'title' => __('New Overtime Request'),
            'user_id' => $this->overtime->user_id,
            'user_name' => $this->overtime->user->name,
            'date' => $this->overtime->date->format('Y-m-d'),
            'duration' => $this->overtime->duration_text,
            'message' => __('Overtime request from :name (:duration)', [
                'name' => $this->overtime->user->name,
                'duration' => $this->overtime->duration_text,
            ]),
            'url' => route('admin.overtime', absolute: false),
        ];
    }
}
