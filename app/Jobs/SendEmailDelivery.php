<?php

namespace App\Jobs;

use App\Mail\GenericNotificationMail;
use App\Models\EmailDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $deliveryId) {}

    public function handle(): void
    {
        $delivery = EmailDelivery::query()->find($this->deliveryId);
        if ($delivery === null || $delivery->status === EmailDelivery::STATUS_SENT) {
            return;
        }

        $delivery->forceFill([
            'status' => EmailDelivery::STATUS_SENDING,
            'attempts' => $delivery->attempts + 1,
            'last_error' => null,
        ])->save();

        try {
            Mail::to($delivery->recipient_email, $delivery->recipient_name)
                ->send(new GenericNotificationMail($delivery));

            $delivery->forceFill([
                'status' => EmailDelivery::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
            ])->save();
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => EmailDelivery::STATUS_RETRYING,
                'available_at' => now()->addSeconds($this->backoff[min($delivery->attempts - 1, count($this->backoff) - 1)]),
                'last_error' => str($exception->getMessage())->limit(1000),
            ])->save();

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        EmailDelivery::query()->whereKey($this->deliveryId)->update([
            'status' => EmailDelivery::STATUS_FAILED,
            'failed_at' => now(),
            'last_error' => str($exception->getMessage())->limit(1000),
        ]);
    }
}
