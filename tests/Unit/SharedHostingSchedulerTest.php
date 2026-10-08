<?php

use App\Support\ScheduledArtisanCommand;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\NullOutput;

test('scheduled commands run in process with their options', function () {
    Artisan::shouldReceive('call')->once()
        ->with('queue:work', ['--stop-when-empty' => true], Mockery::type(NullOutput::class))
        ->andReturn(0);

    (new ScheduledArtisanCommand('queue:work', ['--stop-when-empty' => true]))();
});

test('scheduled command failures are reported instead of treated as success', function () {
    Artisan::shouldReceive('call')->once()
        ->with('inspire', [], Mockery::type(NullOutput::class))->andReturn(1);

    expect(fn () => (new ScheduledArtisanCommand('inspire'))())
        ->toThrow(RuntimeException::class, 'failed with exit code 1');
});

test('application scheduled tasks do not require subprocesses', function () {
    $events = app(Schedule::class)->events();

    expect($events)->not->toBeEmpty();

    foreach ($events as $event) {
        expect($event)->toBeInstanceOf(CallbackEvent::class);
    }

    $commands = collect($events)->keyBy('description');
    foreach (['maintenance:scheduled-backups', 'import-export-runs:prune-expired', 'queue:work'] as $name) {
        expect($commands->get($name)->withoutOverlapping)->toBeTrue();
    }

    config(['queue.schedule_worker' => false]);
    expect($commands->get('queue:work')->filtersPass(app()))->toBeFalse();
    config(['queue.schedule_worker' => true]);
    expect($commands->get('queue:work')->filtersPass(app()))->toBeTrue();
});
