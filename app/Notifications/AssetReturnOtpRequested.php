<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AssetReturnOtpRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public $assetName;
    public $userName;
    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct($assetName, $userName, $otp)
    {
        $this->assetName = $assetName;
        $this->userName = $userName;
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject(__('Asset Return Request') . ': ' . $this->assetName)
            ->greeting(__('Hello,'))
            ->line(__(':username has requested to return their assigned company asset: **:asset**.', [
                'username' => $this->userName, 
                'asset' => $this->assetName
            ]))
            ->line(__('To confirm and finalize the return process, please provide the following 6-digit OTP code to :username:', [
                'username' => $this->userName
            ]))
            ->line(new \Illuminate\Support\HtmlString('<div style="background-color: #f3f4f6; margin: 20px 0; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 12px; border-radius: 8px;">' . $this->otp . '</div>'))
            ->action(__('View Asset Management'), route('admin.assets'))
            ->line(__('If this request is a mistake, you may ignore this message.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'asset_return_otp',
            'title' => 'Asset Return Request', // Keys are safe here, blade already translates it
            'user_name' => $this->userName,
            'asset_name' => $this->assetName,
            'message' => __(':username has requested to return :asset. The OTP is: :otp', [
                'username' => $this->userName,
                'asset' => $this->assetName,
                'otp' => $this->otp
            ]),
            'otp' => $this->otp,
            'url' => route('admin.assets'),
        ];
    }
}
