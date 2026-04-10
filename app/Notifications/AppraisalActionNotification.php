<?php

namespace App\Notifications;

use App\Models\Appraisal;
use App\Models\User;
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
        return (new MailMessage)
                    ->subject('Performance Appraisal Update: ' . $this->appraisal->status)
                    ->greeting('Hello, ' . $notifiable->name)
                    ->line($this->messageStr)
                    ->line('Appraisal Period: ' . date('F', mktime(0, 0, 0, $this->appraisal->period_month, 10)) . ' ' . $this->appraisal->period_year)
                    ->action('View Assessment', $this->actionUrl)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appraisal',
            'title' => 'Appraisal Update',
            'message' => $this->messageStr,
            'url' => $this->actionUrl,
        ];
    }
}
