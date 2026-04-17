<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $attendance;
    public $fromDate;
    public $toDate;
    public $totalDays;

    public function __construct($attendance, $fromDate = null, $toDate = null)
    {
        $this->attendance = $attendance;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        
        // Calculate total days
        if ($fromDate && $toDate) {
            $this->totalDays = $fromDate->diffInDays($toDate) + 1;
        } else {
            $this->totalDays = 1;
        }
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->attendance->user->name ?? __('Unknown');
        $leaveType = $this->attendance->status === 'sick'
            ? __('Sick Leave')
            : __('Leave');
        $appName = MailBranding::companyName();

        if ($this->fromDate && $this->toDate && $this->totalDays > 1) {
            $dateDisplay = $this->fromDate->translatedFormat('d M Y') . ' - ' . $this->toDate->translatedFormat('d M Y');
            $daysInfo = trans_choice(':count day|:count days', $this->totalDays, ['count' => $this->totalDays]);
        } else {
            $dateDisplay = $this->attendance->date?->translatedFormat('d M Y') ?? __('Unknown');
            $daysInfo = trans_choice(':count day|:count days', 1, ['count' => 1]);
        }

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('New Leave Request') . ' - ' . $userName))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, Admin!'),
                'introLines' => [
                    __('There is a new leave request that requires your attention.')
                ],
                'details' => [
                    __('Employee') => $userName,
                    __('Type') => $leaveType,
                    __('Date') => $dateDisplay . ' (' . $daysInfo . ')',
                    __('Reason') => $this->attendance->note ?? '-',
                ],
                'actionText' => __('View Request'),
                'actionUrl' => route('admin.leaves'),
                'outroLines' => [
                    __('Please login to approve or reject this request.')
                ]
            ]);
    }
}
