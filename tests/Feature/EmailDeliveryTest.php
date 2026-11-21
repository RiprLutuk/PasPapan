<?php

use App\Jobs\SendEmailDelivery;
use App\Models\EmailDelivery;
use App\Models\User;
use App\Services\EmailDeliveryService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Queue;

test('database-only notification creates a deduplicated email delivery event', function () {
    Queue::fake();
    $user = User::factory()->create();

    $user->notify(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toArray(object $notifiable): array
        {
            return [
                'title' => 'Review needed',
                'message' => 'A custom workflow needs your review.',
                'url' => '/admin/dashboard',
            ];
        }
    });

    $delivery = EmailDelivery::query()->sole();

    expect($delivery->category)->toBe('notification')
        ->and($delivery->recipient_email)->toBe($user->email)
        ->and($delivery->status)->toBe(EmailDelivery::STATUS_QUEUED)
        ->and($delivery->source_notification_id)->not->toBeNull();

    Queue::assertPushed(SendEmailDelivery::class, fn (SendEmailDelivery $job): bool => $job->deliveryId === $delivery->id);
});

test('employee invitation is queued once per account', function () {
    Queue::fake();
    $user = User::factory()->create();
    $service = app(EmailDeliveryService::class);

    $service->queueEmployeeInvitation($user);
    $service->queueEmployeeInvitation($user);

    expect(EmailDelivery::query()->where('category', 'employee_invitation')->count())->toBe(1);
    Queue::assertPushed(SendEmailDelivery::class, 1);
});
