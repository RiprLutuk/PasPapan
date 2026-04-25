<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReimbursementRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $reimbursement;

    public function __construct(Reimbursement $reimbursement)
    {
        $this->reimbursement = $reimbursement;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->reimbursement->user->name ?? __('Unknown');
        $amount = number_format($this->reimbursement->amount ?? 0, 0, ',', '.');
        $appName = MailBranding::companyName();
        $date = $this->reimbursement->date;
        $dateFormatted = $date ? \Carbon\Carbon::parse($date)->translatedFormat('d M Y') : '-';

        $details = [
            __('Staff') => $userName,
            __('Type') => $this->reimbursement->type,
            __('Amount') => 'Rp '.$amount,
            __('Description') => $this->reimbursement->description ?? '-',
            __('Date') => $dateFormatted,
        ];

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('New Reimbursement Request').' - '.$userName))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, Admin!'),
                'introLines' => [
                    __('A new reimbursement request has been submitted by :name.', ['name' => $userName]),
                ],
                'details' => $details,
                'actionText' => __('Review Request'),
                'actionUrl' => route('admin.reimbursements'),
                'outroLines' => [
                    __('Please review this request at your earliest convenience.'),
                ],
            ]);
    }
}
