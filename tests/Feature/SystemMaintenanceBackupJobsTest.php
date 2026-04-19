<?php

use App\Jobs\RunSystemBackup;
use App\Livewire\Admin\SystemMaintenance;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('superadmin can queue database backup jobs from system maintenance', function () {
    Queue::fake();

    $superadmin = User::factory()->admin(true)->create();
    $this->actingAs($superadmin);

    Livewire::test(SystemMaintenance::class)
        ->call('queueDatabaseBackupJob')
        ->assertDispatched('success');

    $backupRun = SystemBackupRun::query()->first();

    expect($backupRun)->not->toBeNull()
        ->and($backupRun->type)->toBe('database')
        ->and($backupRun->status)->toBe('queued')
        ->and($backupRun->queue)->toBe('maintenance')
        ->and($backupRun->requested_by_user_id)->toBe($superadmin->id);

    Queue::assertPushed(RunSystemBackup::class, function (RunSystemBackup $job) use ($backupRun) {
        return $job->backupRunId === $backupRun->id
            && $job->queue === 'maintenance';
    });
});

test('non superadmin cannot queue backup jobs from system maintenance', function () {
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(SystemMaintenance::class)
        ->call('queueApplicationBackupJob')
        ->assertDispatched('error');

    expect(SystemBackupRun::count())->toBe(0);
    Queue::assertNothingPushed();
});
