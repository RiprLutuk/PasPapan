<?php

namespace App\Notifications;

use App\Models\Reimbursement;
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
        $status = ucfirst($this->reimbursement->status);
        $amount = number_format($this->reimbursement->amount, 0, ',', '.');
        $emoji = $this->reimbursement->status === 'approved' ? '✅' : '❌';

        return (new MailMessage)
            ->subject("{$emoji} Reimbursement {$status}: {$this->reimbursement->type}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Your reimbursement request for **{$this->reimbursement->type}** submitted on {$this->reimbursement->date->format('d M Y')} has been **{$status}**.")
            ->line("Amount: Rp {$amount}")
            ->line("Description: {$this->reimbursement->description}")
            ->action('View Details', route('reimbursement'))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Reimbursement ' . ucfirst($this->reimbursement->status),
            'message' => "Your claim for {$this->reimbursement->type} of Rp " . number_format($this->reimbursement->amount, 0, ',', '.') . " was {$this->reimbursement->status}.",
            'action_url' => route('reimbursement'),
        ];
    }
}
