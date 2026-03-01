<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashAdvanceRequested extends Notification
{
    public $advance;

    public function __construct(CashAdvance $advance)
    {
        $this->advance = $advance;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format($this->advance->amount, 0, ',', '.');

        $url = route('team-kasbon');
        if ($notifiable instanceof \App\Models\User && $notifiable->isAdmin) {
            $url = route('admin.manage-kasbon');
        }

        return [
            'type' => 'kasbon_request',
            'title' => 'New Kasbon Request',
            'user_id' => $this->advance->user_id,
            'user_name' => $this->advance->user->name,
            'amount' => $amount,
            'message' => "Request from {$this->advance->user->name}: Rp {$amount}",
            'url' => $url,
        ];
    }
}
