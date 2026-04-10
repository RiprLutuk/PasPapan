<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
            'title' => 'Asset Return Request',
            'message' => "{$this->userName} has requested to return {$this->assetName}. The OTP is: {$this->otp}",
            'otp' => $this->otp,
            'url' => route('admin.assets'),
        ];
    }
}
