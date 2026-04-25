<?php

namespace App\Notifications;

use App\Models\Overtime;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $overtime;

    public function __construct(Overtime $overtime)
    {
        $this->overtime = $overtime;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->overtime->user->name ?? __('Unknown');
        $appName = MailBranding::companyName();

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('New Overtime Request').' - '.$userName))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, Admin!'),
                'introLines' => [
                    __('A new overtime request has been submitted by :name.', ['name' => $userName]),
                ],
                'details' => [
                    __('Staff') => $userName,
                    __('Date') => $this->overtime->date->translatedFormat('d M Y'),
                    __('Time') => $this->overtime->start_time->format('H:i').' - '.$this->overtime->end_time->format('H:i'),
                    __('Duration') => $this->overtime->duration_text,
                    __('Reason') => $this->overtime->reason,
                ],
                'actionText' => __('View Request'),
                'actionUrl' => route('admin.overtime'),
                'outroLines' => [
                    __('Please review this request in the dashboard.'),
                ],
            ]);
    }
}
