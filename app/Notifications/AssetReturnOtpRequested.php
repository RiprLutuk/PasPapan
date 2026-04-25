<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssetReturnOtpRequested extends Notification
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
        return ['database'];
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
            'title' => __('Asset Return Request'),
            'user_name' => $this->userName,
            'asset_name' => $this->assetName,
            'message' => __(':username has requested to return :asset. The OTP is: :otp', [
                'username' => $this->userName,
                'asset' => $this->assetName,
                'otp' => $this->otp,
            ]),
            'otp' => $this->otp,
            'url' => route('admin.assets', absolute: false),
        ];
    }
}
