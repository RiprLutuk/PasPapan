<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $attendance;

    public function __construct($attendance)
    {
        $this->attendance = $attendance;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->attendance->approval_status);
        $emoji = $this->attendance->approval_status === 'approved' ? '✅' : '❌';

        return (new MailMessage)
            ->subject("{$emoji} Leave Request {$status}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Your leave request for **{$this->attendance->date->format('d M Y')}** has been **{$status}**.")
            ->line("Reason: " . ($this->attendance->rejection_note ?? $this->attendance->status)) // Fallback or explicit note
            ->action('View Attendance History', route('attendance-history'))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        $status = ucfirst($this->attendance->approval_status);
        $emoji = $this->attendance->approval_status === 'approved' ? '✅' : '❌';
        
        return [
            'type' => 'leave_status',
            'title' => 'Leave Request ' . ucfirst($this->attendance->approval_status),
            'attendance_id' => $this->attendance->id,
            'status' => $this->attendance->approval_status,
            'date' => $this->attendance->date->format('Y-m-d'),

            'message' => __('Your leave for :date has been :status', [
                'date' => $this->attendance->date->format('d M'),
                'status' => $status
            ]) . " " . $emoji,
            'url' => route('attendance-history'),
        ];
    }
}
