<?php

namespace App\Notifications;

use App\Models\CashAdvance;
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
        $statusLabel = __(ucfirst($this->advance->status));

        return [
            'type' => 'kasbon_updated',
            'title' => __('Cash Advance Request').' '.$statusLabel,
            'user_id' => $this->advance->user_id,
            'user_name' => $this->advance->user->name,
            'amount' => $amount,
            'message' => __('Your cash advance request of Rp :amount was :status.', [
                'amount' => $amount,
                'status' => mb_strtolower($statusLabel),
            ]),
            'url' => route('my-kasbon', absolute: false),
        ];
    }
}
