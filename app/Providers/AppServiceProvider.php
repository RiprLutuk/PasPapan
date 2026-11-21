<?php

namespace App\Providers;

use App\Listeners\QueueGenericNotificationEmail;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(NotificationSent::class, QueueGenericNotificationEmail::class);
    }
}
