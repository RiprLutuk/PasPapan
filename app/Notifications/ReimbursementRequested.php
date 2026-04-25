<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use Illuminate\Notifications\Notification;

class ReimbursementRequested extends Notification
{
    public $reimbursement;

    public function __construct(Reimbursement $reimbursement)
    {
        $this->reimbursement = $reimbursement;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $amount = number_format($this->reimbursement->amount, 0, ',', '.');

        return [
            'type' => 'reimbursement_request',
            'title' => __('New Reimbursement Request'),
            'user_id' => $this->reimbursement->user_id,
            'user_name' => $this->reimbursement->user->name,
            'amount' => $amount,
            'message' => __('Request from :name: :type (Rp :amount)', [
                'name' => $this->reimbursement->user->name,
                'type' => $this->reimbursement->type,
                'amount' => $amount,
            ]),
            'url' => route('admin.reimbursements', absolute: false),
        ];
    }
}
