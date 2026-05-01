<?php

use App\Jobs\RunSystemBackup;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\SystemBackupService;

beforeEach(function () {
    enableEnterpriseAttendanceForTests();
});

test('backup run job marks database runs as completed when backup service succeeds', function () {
    $superadmin = User::factory()->admin(true)->create();

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    $backupService = mock(SystemBackupService::class);
    $backupService->shouldReceive('createDatabaseBackup')
        ->once()
        ->andReturn([
            'filename' => 'backup-db-test.sql',
            'path' => 'maintenance-backups/database/backup-db-test.sql',
            'size_bytes' => 4096,
        ]);

    (new RunSystemBackup($backupRun->id))->handle($backupService);

    $backupRun->refresh();

    expect($backupRun->status)->toBe('completed')
        ->and($backupRun->file_name)->toBe('backup-db-test.sql')
        ->and($backupRun->file_path)->toBe('maintenance-backups/database/backup-db-test.sql')
        ->and($backupRun->size_bytes)->toBe(4096)
        ->and($backupRun->completed_at)->not->toBeNull();
});

test('backup run job marks application runs as failed when backup service throws', function () {
    $superadmin = User::factory()->admin(true)->create();

    $backupRun = SystemBackupRun::create([
        'type' => 'application',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    $backupService = mock(SystemBackupService::class);
    $backupService->shouldReceive('createApplicationBackup')
        ->once()
        ->andThrow(new \RuntimeException('ZipArchive is not available on this server.'));

    expect(fn () => (new RunSystemBackup($backupRun->id))->handle($backupService))
        ->toThrow(\RuntimeException::class, 'ZipArchive is not available on this server.');

    $backupRun->refresh();

    expect($backupRun->status)->toBe('failed')
        ->and($backupRun->failed_at)->not->toBeNull()
        ->and($backupRun->error_message)->toContain('ZipArchive is not available');
});
