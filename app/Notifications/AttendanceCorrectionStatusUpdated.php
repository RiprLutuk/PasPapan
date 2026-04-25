<?php

namespace App\Notifications;

use App\Models\AttendanceCorrection;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceCorrectionStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AttendanceCorrection $correction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->correction->statusLabel();
        $appName = MailBranding::companyName();
        $summary = match ($this->correction->status) {
            AttendanceCorrection::STATUS_PENDING_ADMIN => __('Your attendance correction request for :date has been approved by your supervisor and is now waiting for admin review.', [
                'date' => $this->correction->attendance_date->translatedFormat('d M Y'),
            ]),
            AttendanceCorrection::STATUS_REJECTED => __('Your attendance correction request for :date has been rejected.', [
                'date' => $this->correction->attendance_date->translatedFormat('d M Y'),
            ]),
            default => __('Your attendance correction request for :date has been :status.', [
                'date' => $this->correction->attendance_date->translatedFormat('d M Y'),
                'status' => mb_strtolower($statusLabel),
            ]),
        };

        $mail = (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Attendance Correction').' - '.$statusLabel))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line($summary)
            ->line(__('Request type: :type', ['type' => $this->correction->requestTypeLabel()]))
            ->line(__('Reason: :reason', ['reason' => $this->correction->reason]));

        if ($this->correction->status === AttendanceCorrection::STATUS_REJECTED && $this->correction->rejection_note) {
            $mail->line(__('Rejection note: :note', ['note' => $this->correction->rejection_note]));
        }

        return $mail
            ->action(__('View Requests'), route('attendance-corrections'))
            ->line(__('Thank you for using our application!'));
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = $this->correction->statusLabel();
        $emoji = match ($this->correction->status) {
            AttendanceCorrection::STATUS_APPROVED => '✅',
            AttendanceCorrection::STATUS_PENDING_ADMIN => '⏳',
            default => '❌',
        };
        $message = match ($this->correction->status) {
            AttendanceCorrection::STATUS_PENDING_ADMIN => __('Your attendance correction for :date is waiting for admin review', [
                'date' => $this->correction->attendance_date->translatedFormat('d M'),
            ]),
            default => __('Your attendance correction for :date was :status', [
                'date' => $this->correction->attendance_date->translatedFormat('d M'),
                'status' => mb_strtolower($statusLabel),
            ]),
        };

        return [
            'type' => 'attendance_correction_status',
            'title' => __('Attendance Correction').' '.$statusLabel,
            'correction_id' => $this->correction->id,
            'status' => $this->correction->status,
            'attendance_date' => $this->correction->attendance_date->format('Y-m-d'),
            'message' => $message.' '.$emoji,
            'url' => route('attendance-corrections', absolute: false),
        ];
    }
}
