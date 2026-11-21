<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\EmailDeliveryService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

class QueueGenericNotificationEmail
{
    public function __construct(private readonly EmailDeliveryService $deliveries) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database'
            || ! $event->notifiable instanceof User
            || ! $event->response instanceof DatabaseNotification) {
            return;
        }

        $this->deliveries->queueDatabaseNotification(
            $event->notifiable,
            $event->notification,
            $event->response,
        );
    }
}
