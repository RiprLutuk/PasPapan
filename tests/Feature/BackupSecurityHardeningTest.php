<?php

use App\Contracts\AuditServiceInterface;
use App\Models\Setting;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

function fakeAuditRecorder(): object
{
    return new class implements AuditServiceInterface
    {
        public array $records = [];

        public function record(string $action, ?string $description = null)
        {
            $this->records[] = compact('action', 'description');

            return null;
        }
    };
}

test('backup runs can only be created by superadmins', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    $admin = User::factory()->admin()->create();

    expect(fn () => SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]))->toThrow(AuthorizationException::class, 'Only superadmins can manage the backup system.');

    expect($audit->records)->toHaveCount(0);
});

test('backup runs can require mfa for superadmins', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    Setting::updateOrCreate(
        ['key' => 'backup.require_mfa'],
        ['value' => '1', 'group' => 'maintenance', 'type' => 'boolean']
    );
    Setting::flushCache('backup.require_mfa');

    $superadmin = User::factory()->admin(true)->create();

    expect(fn () => SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]))->toThrow(AuthorizationException::class, 'Multi-factor authentication is required before managing the backup system.');

    $superadmin->forceFill([
        'two_factor_secret' => encrypt('otp-secret'),
    ])->save();

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    expect($backupRun)->not->toBeNull()
        ->and($audit->records)->toHaveCount(1)
        ->and($audit->records[0]['action'])->toBe('Backup Database Queued');
});

test('completed backup runs beyond the configured size limit are downgraded to failed and audited', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    Setting::updateOrCreate(
        ['key' => 'backup.max_file_size_bytes'],
        ['value' => '1024', 'group' => 'maintenance', 'type' => 'number']
    );
    Setting::flushCache('backup.max_file_size_bytes');

    $superadmin = User::factory()->admin(true)->create();

    $backupRun = SystemBackupRun::create([
        'type' => 'restore',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    $backupRun->update([
        'status' => 'completed',
        'file_name' => 'oversized-backup.sql',
        'size_bytes' => 4096,
        'completed_at' => now(),
    ]);

    $backupRun->refresh();

    expect($backupRun->status)->toBe('failed')
        ->and($backupRun->failed_at)->not->toBeNull()
        ->and($backupRun->error_message)->toContain('configured size limit');

    expect($audit->records)->toHaveCount(2)
        ->and($audit->records[0]['action'])->toBe('Backup Restore Queued')
        ->and($audit->records[1]['action'])->toBe('Backup Restore Failed');
});
