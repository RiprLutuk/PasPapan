<?php

use App\Jobs\RecordQueueHeartbeat;
use App\Support\ScheduledArtisanCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(new ScheduledArtisanCommand('inspire'))->name('inspire')->hourly();

Schedule::call(new ScheduledArtisanCommand('maintenance:scheduled-backups'))
    ->name('maintenance:scheduled-backups')
    ->everyMinute()
    ->withoutOverlapping();
Schedule::call(new ScheduledArtisanCommand('import-export-runs:prune-expired', ['--hours' => 12]))
    ->name('import-export-runs:prune-expired')
    ->hourly()
    ->withoutOverlapping();
Schedule::call(fn () => Cache::put('health:scheduler_heartbeat_at', now()->toIso8601String(), now()->addMinutes(10)))
    ->name('health.scheduler-heartbeat')
    ->everyMinute();
Schedule::job(new RecordQueueHeartbeat)
    ->name('health.queue-heartbeat')
    ->everyMinute();
Schedule::call(new ScheduledArtisanCommand('queue:work', [
    '--queue' => 'maintenance,default',
    '--stop-when-empty' => true,
    '--max-time' => 55,
    '--tries' => 1,
]))
    ->name('queue:work')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) config('queue.schedule_worker', true));
