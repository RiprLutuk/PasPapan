<?php

namespace App\Notifications;

use App\Models\Appraisal;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppraisalActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $appraisal;

    public $messageStr;

    public $actionUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appraisal $appraisal, string $messageStr, string $actionUrl)
    {
        $this->appraisal = $appraisal;
        $this->messageStr = $messageStr;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = MailBranding::companyName();
        $statusLabel = __(ucfirst((string) $this->appraisal->status));

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(MailBranding::replyToAddress(), $appName)
            ->subject(MailBranding::subject(__('Performance Appraisal Update').' - '.$statusLabel))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line($this->messageStr)
            ->line(__('Appraisal Period: :period', [
                'period' => \Carbon\Carbon::create(
                    $this->appraisal->period_year,
                    $this->appraisal->period_month,
                    1
                )->translatedFormat('F Y'),
            ]))
            ->action(__('View Assessment'), $this->actionUrl)
            ->line(__('Thank you for using our application!'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appraisal',
            'title' => __('Appraisal Update'),
            'message' => $this->messageStr,
            'url' => $this->actionUrl,
        ];
    }
}
