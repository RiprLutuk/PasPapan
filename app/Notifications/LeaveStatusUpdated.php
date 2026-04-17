<?php

namespace App\Notifications;

use App\Support\MailBranding;
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
        $statusLabel = __(ucfirst($this->attendance->approval_status));
        $appName = MailBranding::companyName();
        $note = $this->attendance->rejection_note ?: __($this->attendance->status);

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Leave Request') . ' - ' . $statusLabel))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line(__('Your leave request for **:date** has been **:status**.', [
                'date' => $this->attendance->date->translatedFormat('d M Y'),
                'status' => $statusLabel,
            ]))
            ->line(__('Note: :note', ['note' => $note]))
            ->action(__('View Attendance History'), route('attendance-history'))
            ->line(__('Thank you for using our application!'));
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = __(ucfirst($this->attendance->approval_status));
        $emoji = $this->attendance->approval_status === 'approved' ? '✅' : '❌';

        return [
            'type' => 'leave_status',
            'title' => __('Leave Request') . ' ' . $statusLabel,
            'attendance_id' => $this->attendance->id,
            'status' => $this->attendance->approval_status,
            'date' => $this->attendance->date->format('Y-m-d'),

            'message' => __('Your leave for :date has been :status', [
                'date' => $this->attendance->date->translatedFormat('d M'),
                'status' => $statusLabel
            ]) . " " . $emoji,
            'url' => route('attendance-history'),
        ];
    }
}
