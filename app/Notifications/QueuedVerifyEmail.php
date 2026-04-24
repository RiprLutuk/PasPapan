<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class QueuedVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private ?string $code = null)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = MailBranding::companyName();
        $supportEmail = MailBranding::supportAddress();
        $code = $this->code ?? (string) random_int(100000, 999999);

        if ($this->code === null && ! $notifiable->hasVerifiedEmail()) {
            $notifiable->forceFill([
                'email_verification_code_hash' => Hash::make($code),
                'email_verification_code_expires_at' => now()->addMinutes(15),
            ])->save();
        }

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
                    __('Enter this verification code in the app. It expires in :minutes minutes.', ['minutes' => 15]),
                ],
                'verificationCode' => $code,
                'actionText' => __('Open Verification Page'),
                'actionUrl' => route('verification.notice'),
                'outroLines' => [
                    __('If you did not create an account, you can safely ignore this email.'),
                ],
                'helpText' => __('Need help? Contact us at :email.', ['email' => $supportEmail]),
            ]);
    }
}
