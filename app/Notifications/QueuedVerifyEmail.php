<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $appName = MailBranding::companyName();
        $supportEmail = MailBranding::supportAddress();

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Verify Email Address')))
            ->view('emails.auth-action', [
                'title' => __('Verify Email Address'),
                'eyebrow' => __('Account Activation'),
                'greeting' => __('Hello, :name!', ['name' => $notifiable->name ?? __('there')]),
                'introLines' => [
                    __('Please confirm your email address to finish activating your account.'),
                    __('For your security, use the button below to verify this address before returning to the application.'),
                ],
                'actionText' => __('Verify Email Address'),
                'actionUrl' => $this->verificationUrl($notifiable),
                'outroLines' => [
                    __('If you did not create an account, you can safely ignore this email.'),
                ],
                'helpText' => __('Need help? Contact us at :email.', ['email' => $supportEmail]),
            ]);
    }
}
