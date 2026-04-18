<?php

namespace App\Notifications;

use App\Models\Overtime;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public Overtime $overtime;

    public function __construct(Overtime $overtime)
    {
        $this->overtime = $overtime;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = __(ucfirst($this->overtime->status));
        $appName = MailBranding::companyName();

        $mail = (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Overtime Request') . ' - ' . $statusLabel))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line(__('Your overtime request for **:date** has been **:status**.', [
                'date' => $this->overtime->date->translatedFormat('d M Y'),
                'status' => $statusLabel,
            ]))
            ->line(__('Duration: :duration', ['duration' => $this->overtime->duration_text]));

        if ($this->overtime->status === 'rejected' && $this->overtime->rejection_reason) {
            $mail->line(__('Reason: :reason', ['reason' => $this->overtime->rejection_reason]));
        }

        return $mail
            ->action(__('View Overtime'), route('overtime'))
            ->line(__('Thank you for using our application!'));
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = __(ucfirst($this->overtime->status));
        $emoji = $this->overtime->status === 'approved' ? '✅' : '❌';

        return [
            'type' => 'overtime_status',
            'title' => __('Overtime Request') . ' ' . $statusLabel,
            'overtime_id' => $this->overtime->id,
            'status' => $this->overtime->status,
            'date' => $this->overtime->date->format('Y-m-d'),
            'duration' => $this->overtime->duration_text,

            'message' => __('Your overtime for :date has been :status', [
                'date' => $this->overtime->date->translatedFormat('d M'),
                'status' => $statusLabel
            ]) . " " . $emoji,
            'url' => route('overtime', absolute: false),
        ];
    }
}
