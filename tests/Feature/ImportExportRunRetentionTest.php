<?php

use App\Models\ImportExportRun;
use App\Support\ImportExportRunRetention;

test('import export run retention hides expired terminal jobs from recent lists', function () {
    $recent = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'completed',
        'progress_percentage' => 100,
        'completed_at' => now()->subHours(11),
    ]);
    $expired = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'completed',
        'progress_percentage' => 100,
        'completed_at' => now()->subHours(13),
    ]);
    $running = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'running',
        'progress_percentage' => 40,
        'started_at' => now()->subHours(25),
    ]);

    $runs = [
        ['id' => $recent->id, 'status' => 'completed'],
        ['id' => $expired->id, 'status' => 'completed'],
        ['id' => $running->id, 'status' => 'running'],
    ];

    $visibleIds = collect(app(ImportExportRunRetention::class)->filterVisible($runs))
        ->pluck('id')
        ->all();

    expect($visibleIds)
        ->toContain($recent->id)
        ->toContain($running->id)
        ->not->toContain($expired->id);
});

test('expired import export run command prunes only terminal jobs older than retention window', function () {
    $expired = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'completed',
        'progress_percentage' => 100,
        'completed_at' => now()->subHours(13),
    ]);
    $recent = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'failed',
        'failed_at' => now()->subHours(11),
        'error_message' => 'Still visible',
    ]);
    $running = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'running',
        'started_at' => now()->subHours(25),
    ]);

    $this->artisan('import-export-runs:prune-expired')
        ->expectsOutput('Deleted 1 expired import/export job(s).')
        ->assertExitCode(0);

    expect(ImportExportRun::query()->whereKey($expired->id)->exists())->toBeFalse()
        ->and(ImportExportRun::query()->whereKey($recent->id)->exists())->toBeTrue()
        ->and(ImportExportRun::query()->whereKey($running->id)->exists())->toBeTrue();
});
