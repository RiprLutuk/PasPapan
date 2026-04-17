<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssetReturnOtpRequestedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $assetName;
    public $userName;
    public $otp;

    public function __construct($assetName, $userName, $otp)
    {
        $this->assetName = $assetName;
        $this->userName = $userName;
        $this->otp = $otp;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = MailBranding::companyName();

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(MailBranding::replyToAddress(), $appName)
            ->subject(MailBranding::subject(__('Asset Return Request') . ' - ' . $this->assetName))
            ->view('emails.asset-return-otp', [
                'assetName' => $this->assetName,
                'userName' => $this->userName,
                'otp' => $this->otp,
            ]);
    }
}
