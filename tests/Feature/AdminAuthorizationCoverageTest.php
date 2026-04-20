<?php

use App\Jobs\ProcessActivityLogExportRun;
use App\Jobs\ProcessAttendanceImportRun;
use App\Jobs\ProcessUserImportRun;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Gate;

test('admin settings page requires explicit settings ability', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($admin)
        ->get(route('admin.settings'))
        ->assertOk();

    $this->actingAs($superadmin)
        ->get(route('admin.settings'))
        ->assertOk();
});

test('admin only user import export endpoints are limited to superadmins', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();
    $file = UploadedFile::fake()->createWithContent('users.csv', implode("\n", [
        'NIP,Name,Email,Group,Password,Phone,Gender,Basic Salary,Hourly Rate,Division,Job Title,Education,Birth Date,Birth Place,Address,City',
        '9988776655,Import Route Test,import-route@example.com,user,password123,081111111111,male,5000000,25000,Engineering,Developer,Bachelor,1990-01-01,Jakarta,Jl. Test No. 1,Jakarta',
    ]));

    $this->actingAs($admin)
        ->get(route('admin.import-export.users'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.users.export'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->get(route('admin.import-export.users'))
        ->assertRedirect();

    $this->actingAs($superadmin)
        ->get(route('admin.users.export'))
        ->assertRedirect();
});

test('superadmin user import route queues a background run', function () {
    enableEnterpriseAttendanceForTests();
    Queue::fake();

    $superadmin = User::factory()->admin(true)->create();
    $file = UploadedFile::fake()->createWithContent('users.csv', implode("\n", [
        'NIP,Name,Email,Group,Password,Phone,Gender,Basic Salary,Hourly Rate,Division,Job Title,Education,Birth Date,Birth Place,Address,City',
        '1122334455,Imported Via Route,imported-via-route@example.com,user,password123,081234567890,male,5000000,25000,Engineering,Developer,Bachelor,1990-01-01,Jakarta,Jl. Sudirman No. 1,Jakarta',
    ]));

    $this->actingAs($superadmin)
        ->from(route('admin.import-export.users'))
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertRedirect(route('admin.import-export.users'));

    $run = ImportExportRun::query()
        ->where('resource', 'users')
        ->where('operation', 'import')
        ->latest('id')
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('queued')
        ->and($run->resource)->toBe('users')
        ->and($run->operation)->toBe('import');

    Queue::assertPushed(ProcessUserImportRun::class);
});

test('attendance import route queues a background run for authorized admins', function () {
    enableEnterpriseAttendanceForTests();
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->createWithContent('attendances.csv', implode("\n", [
        'nip,date,time_in,time_out,status',
        '1234567890,2026-04-01,08:00:00,17:00:00,hadir',
    ]));

    $this->actingAs($admin)
        ->from(route('admin.import-export.attendances'))
        ->post(route('admin.attendances.import'), ['file' => $file])
        ->assertRedirect(route('admin.import-export.attendances'));

    $run = ImportExportRun::query()
        ->where('resource', 'attendances')
        ->where('operation', 'import')
        ->latest('id')
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('queued');

    Queue::assertPushed(ProcessAttendanceImportRun::class);
});

test('activity log export is blocked for regular admins', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($admin)
        ->get(route('admin.activity-logs'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.export'))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->get(route('admin.activity-logs.export'))
        ->assertRedirect();
});

test('activity log export queues a background run for superadmin in enterprise mode', function () {
    enableEnterpriseAttendanceForTests();
    Queue::fake();

    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin)
        ->from(route('admin.activity-logs'))
        ->get(route('admin.activity-logs.export', [
            'search' => 'Login',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-20',
        ]))
        ->assertRedirect(route('admin.activity-logs'));

    $run = ImportExportRun::query()
        ->where('resource', 'activity_logs')
        ->where('operation', 'export')
        ->latest('id')
        ->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe('queued')
        ->and($run->meta)->toMatchArray([
            'search' => 'Login',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-20',
        ]);

    Queue::assertPushed(ProcessActivityLogExportRun::class);
});

test('admin authorization gates cover master data, barcode, and scoped user management', function () {
    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();

    expect(Gate::forUser($employee)->allows('manageMasterData'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageBarcodes'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageSystemSettings'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageEnterpriseLicense'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageMasterData'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageBarcodes'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageSystemSettings'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageEnterpriseLicense'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [null, 'user']))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [$admin, 'admin']))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [$otherAdmin, 'admin']))->toBeFalse()
        ->and(Gate::forUser($superadmin)->allows('manageSystemSettings'))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('manageEnterpriseLicense'))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('manageUserRecord', [$otherAdmin, 'admin']))->toBeTrue();
});
