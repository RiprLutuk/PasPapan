<?php

use App\Contracts\AuditServiceInterface;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    enableEnterpriseAttendanceForTests();
});

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

test('backup runs require explicit maintenance manage permission', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    $admin = User::factory()->admin()->create();
    $maintenanceManager = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Backup Maintenance Manager',
        'slug' => 'backup_maintenance_manager',
        'description' => 'Can manage maintenance backups.',
        'permissions' => ['admin.system_maintenance.manage'],
    ]);

    $maintenanceManager->roles()->sync([$role->id]);

    expect(fn () => SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]))->toThrow(AuthorizationException::class, 'You do not have permission to manage the backup system.');

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $maintenanceManager->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    expect($backupRun)->not->toBeNull()
        ->and($backupRun->requested_by_user_id)->toBe($maintenanceManager->id)
        ->and($audit->records)->toHaveCount(1);
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

test('activity logs are append only and expose integrity tampering', function () {
    $activityLog = ActivityLog::create([
        'user_id' => User::factory()->create()->id,
        'action' => 'Security Review',
        'description' => 'Created immutable audit row.',
        'ip_address' => '127.0.0.1',
    ]);

    expect($activityLog->hasValidIntegrityHash())->toBeTrue();

    $activityLog->forceFill(['count' => 2])->save();

    expect($activityLog->refresh()->hasValidIntegrityHash())->toBeTrue()
        ->and($activityLog->count)->toBe(2);

    expect(fn () => $activityLog->forceFill(['description' => 'Changed'])->save())
        ->toThrow(AuthorizationException::class, 'Activity logs are append-only and cannot be modified.');

    $activityLog->refresh();

    expect(fn () => $activityLog->delete())
        ->toThrow(AuthorizationException::class, 'Activity logs are append-only and cannot be deleted.');

    DB::table('activity_logs')
        ->where('id', $activityLog->id)
        ->update(['description' => 'Tampered outside the model']);

    expect($activityLog->refresh()->hasValidIntegrityHash())->toBeFalse();
});

test('backup artifact downloads and deletes require maintenance manager authorization', function () {
    $viewer = User::factory()->admin()->create();
    $manager = User::factory()->admin()->create();

    Role::create([
        'name' => 'Maintenance Viewer',
        'slug' => 'maintenance_viewer_artifact_policy',
        'description' => 'Can view maintenance only.',
        'permissions' => ['admin.system_maintenance.view'],
    ])->users()->attach($viewer);

    Role::create([
        'name' => 'Maintenance Manager',
        'slug' => 'maintenance_manager_artifact_policy',
        'description' => 'Can manage maintenance backups.',
        'permissions' => ['admin.system_maintenance.manage'],
    ])->users()->attach($manager);

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $manager->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    $backupRun->update([
        'status' => 'completed',
        'file_path' => 'backups/database.sql',
        'file_name' => 'database.sql',
        'size_bytes' => 512,
        'completed_at' => now(),
    ]);

    expect(Gate::forUser($viewer)->allows('download', $backupRun))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('delete', $backupRun))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('download', $backupRun))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $backupRun))->toBeTrue();

    Storage::fake('local');
    Storage::disk('local')->put('backups/database.sql', 'select 1;');

    $this->actingAs($viewer);

    Livewire::test(\App\Livewire\Admin\SystemMaintenance::class)
        ->call('downloadExistingBackup', $backupRun->id)
        ->assertDispatched('error');

    Livewire::test(\App\Livewire\Admin\SystemMaintenance::class)
        ->call('deleteBackup', $backupRun->id)
        ->assertDispatched('error');

    expect($backupRun->refresh()->exists)->toBeTrue()
        ->and(Storage::disk('local')->exists('backups/database.sql'))->toBeTrue();
});

test('destructive update and maintenance flows require explicit confirmation controls', function () {
    $updateScript = File::get(base_path('update.sh'));
    $maintenanceView = File::get(resource_path('views/livewire/admin/system-maintenance.blade.php'));

    expect($updateScript)
        ->toContain('PASPAPAN_UPDATE_CONFIRM')
        ->toContain('PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES')
        ->toContain('git reset --hard "origin/${TARGET_BRANCH}"')
        ->and($maintenanceView)
        ->toContain('wire:model.defer="restoreConfirmation"')
        ->toContain('wire:submit.prevent="restoreDatabase"')
        ->toContain('wire:confirm="{{ __(\'Delete this retained backup file?\') }}"');
});
