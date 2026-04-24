<?php

namespace App\Notifications;

use App\Models\CashAdvance;
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

        $url = route('team-kasbon', absolute: false);
        if ($notifiable instanceof \App\Models\User && $notifiable->isAdmin) {
            $url = route('admin.manage-kasbon', absolute: false);
        }

        return [
            'type' => 'kasbon_request',
            'title' => __('New Cash Advance Request'),
            'user_id' => $this->advance->user_id,
            'user_name' => $this->advance->user->name,
            'amount' => $amount,
            'message' => __('Request from :name: Rp :amount', [
                'name' => $this->advance->user->name,
                'amount' => $amount,
            ]),
            'url' => $url,
        ];
    }
}
