<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashAdvanceUpdated extends Notification
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
        $statusLabel = ucfirst($this->advance->status);

        return [
            'type' => 'kasbon_updated',
            'title' => 'Kasbon Request ' . $statusLabel,
            'user_id' => $this->advance->user_id,
            'user_name' => $this->advance->user->name,
            'amount' => $amount,
            'message' => "Your Kasbon request of Rp {$amount} was {$this->advance->status}.",
            'url' => route('my-kasbon'),
        ];
    }
}
