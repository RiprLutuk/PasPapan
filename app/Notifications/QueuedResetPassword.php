<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $appName = MailBranding::companyName();
        $supportEmail = MailBranding::supportAddress();
        $passwordBroker = config('auth.defaults.passwords', 'users');
        $expireMinutes = (int) config("auth.passwords.{$passwordBroker}.expire", 60);

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Reset Password')))
            ->view('emails.auth-action', [
                'title' => __('Reset Password'),
                'eyebrow' => __('Account Security'),
                'greeting' => __('Hello, :name!', ['name' => $notifiable->name ?? __('there')]),
                'introLines' => [
                    __('We received a request to reset the password for your account.'),
                    __('Use the secure button below to choose a new password and regain access to the application.'),
                ],
                'actionText' => __('Reset Password'),
                'actionUrl' => $this->resetUrl($notifiable),
                'outroLines' => [
                    __('This password reset link will expire in :count minutes.', ['count' => $expireMinutes]),
                    __('If you did not request a password reset, no further action is required.'),
                ],
                'helpText' => __('Need help? Contact us at :email.', ['email' => $supportEmail]),
            ]);
    }
}
