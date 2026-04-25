<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimbursementStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $reimbursement;

    public function __construct(Reimbursement $reimbursement)
    {
        $this->reimbursement = $reimbursement;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $statusLabel = __(ucfirst($this->reimbursement->status));
        $amount = number_format($this->reimbursement->amount, 0, ',', '.');
        $appName = MailBranding::companyName();

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Reimbursement').' - '.$statusLabel.' - '.$this->reimbursement->type))
            ->greeting(__('Hello, :name!', ['name' => $notifiable->name]))
            ->line(__('Your reimbursement request for **:type** submitted on :date has been **:status**.', [
                'type' => $this->reimbursement->type,
                'date' => $this->reimbursement->date->translatedFormat('d M Y'),
                'status' => $statusLabel,
            ]))
            ->line(__('Amount: :amount', ['amount' => 'Rp '.$amount]))
            ->line(__('Description: :description', ['description' => $this->reimbursement->description]))
            ->action(__('View Details'), route('reimbursement'))
            ->line(__('Thank you for using our application!'));
    }

    public function toArray($notifiable)
    {
        $statusLabel = __(ucfirst($this->reimbursement->status));

        return [
            'title' => __('Reimbursement').' '.$statusLabel,
            'message' => __('Your claim for :type of Rp :amount was :status.', [
                'type' => $this->reimbursement->type,
                'amount' => number_format($this->reimbursement->amount, 0, ',', '.'),
                'status' => mb_strtolower($statusLabel),
            ]),
            'url' => route('reimbursement', absolute: false),
        ];
    }
}
