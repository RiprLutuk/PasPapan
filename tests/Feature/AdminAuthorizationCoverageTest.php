<?php

use App\Exports\ActivityLogsExport;
use App\Jobs\ProcessActivityLogExportRun;
use App\Jobs\ProcessAttendanceImportRun;
use App\Jobs\ProcessUserImportRun;
use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\DashboardComponent;
use App\Livewire\Admin\EmployeeDocumentRequestManager;
use App\Livewire\Admin\Finance\CashAdvanceManager;
use App\Livewire\Admin\HolidayManager;
use App\Livewire\Admin\NotificationsPage;
use App\Livewire\Admin\OvertimeManager;
use App\Livewire\Admin\ScheduleComponent;
use App\Models\ActivityLog;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\ImportExportRun;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    enableEnterpriseAttendanceForTests();
});

test('admin settings page requires explicit settings ability', function () {
    $admin = User::factory()->admin()->create();
    $settingsViewer = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();
    $role = Role::create([
        'name' => 'Settings Viewer',
        'slug' => 'settings_viewer',
        'description' => 'Can access admin settings.',
        'permissions' => ['admin.dashboard.view', 'admin.settings.view'],
    ]);

    $admin->roles()->detach();
    $settingsViewer->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.settings'))
        ->assertForbidden();

    $this->actingAs($settingsViewer)
        ->get(route('admin.settings'))
        ->assertOk();

    $this->actingAs($superadmin)
        ->get(route('admin.settings'))
        ->assertOk();
});

test('user import export endpoints require explicit permissions', function () {
    $admin = User::factory()->admin()->create();
    $importExportAdmin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();
    $file = UploadedFile::fake()->createWithContent('users.csv', implode("\n", [
        'NIP,Name,Email,Group,Password,Phone,Gender,Basic Salary,Hourly Rate,Division,Job Title,Education,Birth Date,Birth Place,Address,City',
        '9988776655,Import Route Test,import-route@example.com,user,password123,081111111111,male,5000000,25000,Engineering,Developer,Bachelor,1990-01-01,Jakarta,Jl. Test No. 1,Jakarta',
    ]));
    $role = Role::create([
        'name' => 'User Import Export Manager',
        'slug' => 'user_import_export_manager',
        'description' => 'Can view, import, and export users.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.import_export_users.view',
            'admin.import_export_users.import',
            'admin.import_export_users.export',
        ],
    ]);

    $importExportAdmin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.import-export.users'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.users.export'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertForbidden();

    $this->actingAs($importExportAdmin)
        ->get(route('admin.users.export'))
        ->assertRedirect();

    $this->actingAs($importExportAdmin)
        ->post(route('admin.users.import'), ['file' => $file])
        ->assertRedirect(route('admin.import-export.users'));

    $this->actingAs($superadmin)
        ->get(route('admin.import-export.users'))
        ->assertOk();

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
    $role = Role::create([
        'name' => 'Attendance Import Manager',
        'slug' => 'attendance_import_manager',
        'description' => 'Can import attendance files.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.import_export_attendances.view',
            'admin.import_export_attendances.import',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

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
    $viewerAdmin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();
    $role = Role::create([
        'name' => 'Activity Log Viewer',
        'slug' => 'activity_log_viewer_coverage',
        'description' => 'Can view activity logs without exporting them.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.activity_logs.view',
        ],
    ]);

    $admin->roles()->detach();
    $viewerAdmin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs'))
        ->assertForbidden();

    $this->actingAs($viewerAdmin)
        ->get(route('admin.activity-logs'))
        ->assertOk();

    $this->actingAs($viewerAdmin)
        ->get(route('admin.activity-logs.export'))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->get(route('admin.activity-logs.export'))
        ->assertRedirect();
});

test('activity logs include and filter admin and superadmin actors', function () {
    $auditor = User::factory()->admin()->create();
    $employee = User::factory()->create(['name' => 'Audit Employee']);
    $admin = User::factory()->admin()->create(['name' => 'Audit Admin']);
    $superadmin = User::factory()->admin(true)->create(['name' => 'Audit Superadmin']);
    $role = Role::create([
        'name' => 'Activity Log Scope Auditor',
        'slug' => 'activity_log_scope_auditor',
        'description' => 'Can view activity logs.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.activity_logs.view',
        ],
    ]);

    $auditor->roles()->sync([$role->id]);

    ActivityLog::create([
        'user_id' => $employee->id,
        'action' => 'Employee Login Reviewed',
        'description' => 'Employee activity visible.',
        'ip_address' => '127.0.0.1',
    ]);
    ActivityLog::create([
        'user_id' => $admin->id,
        'action' => 'Admin Settings Updated',
        'description' => 'Admin activity visible.',
        'ip_address' => '127.0.0.2',
    ]);
    ActivityLog::create([
        'user_id' => $superadmin->id,
        'action' => 'Superadmin Role Changed',
        'description' => 'Superadmin activity visible.',
        'ip_address' => '127.0.0.3',
    ]);

    $this->actingAs($auditor);

    Livewire::test(ActivityLogs::class)
        ->assertSee('Employee Login Reviewed')
        ->assertSee('Admin Settings Updated')
        ->assertSee('Superadmin Role Changed')
        ->set('actorGroup', 'admin')
        ->assertDontSee('Employee Login Reviewed')
        ->assertSee('Admin Settings Updated')
        ->assertDontSee('Superadmin Role Changed')
        ->set('actorGroup', 'superadmin')
        ->assertDontSee('Employee Login Reviewed')
        ->assertDontSee('Admin Settings Updated')
        ->assertSee('Superadmin Role Changed');

    expect((new ActivityLogsExport(actorGroup: 'admin'))->query()->pluck('action')->all())
        ->toContain('Admin Settings Updated')
        ->not->toContain('Employee Login Reviewed', 'Superadmin Role Changed');
});

test('activity log export job applies actor group filter', function () {
    Storage::fake('local');

    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();

    ActivityLog::create([
        'user_id' => $employee->id,
        'action' => 'Employee Export Excluded',
        'description' => 'Employee row should not be counted.',
    ]);
    ActivityLog::create([
        'user_id' => $admin->id,
        'action' => 'Admin Export Included',
        'description' => 'Admin row should be counted.',
    ]);

    $run = ImportExportRun::create([
        'resource' => 'activity_logs',
        'operation' => 'export',
        'status' => 'queued',
        'meta' => ['actor_group' => 'admin'],
    ]);

    (new ProcessActivityLogExportRun($run->id))->handle();

    $run->refresh();

    expect($run->status)->toBe('completed')
        ->and($run->total_rows)->toBe(1)
        ->and($run->processed_rows)->toBe(1)
        ->and(Storage::disk('local')->exists($run->file_path))->toBeTrue();
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
            'actor_group' => 'superadmin',
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
            'actor_group' => 'superadmin',
        ]);

    Queue::assertPushed(ProcessActivityLogExportRun::class);
});

test('admin authorization gates cover admin pages, master data, barcodes, and scoped user management', function () {
    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();

    expect(Gate::forUser($employee)->allows('viewAdminDashboard'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('viewEmployees'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageSchedules'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageOvertime'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageAdminNotifications'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageHolidays'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageAnnouncements'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageCashAdvances'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageMasterData'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageBarcodes'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageSystemSettings'))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('manageEnterpriseLicense'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAdminDashboard'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewEmployees'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageSchedules'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageOvertime'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageAdminNotifications'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageHolidays'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageAnnouncements'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageCashAdvances'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageMasterData'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageBarcodes'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', SystemBackupRun::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageSystemSettings'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageEnterpriseLicense'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [null, 'user']))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [null, 'admin']))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [$admin, 'admin']))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageUserRecord', [$otherAdmin, 'admin']))->toBeFalse()
        ->and(Gate::forUser($superadmin)->allows('viewAny', SystemBackupRun::class))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('manageSystemSettings'))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('manageEnterpriseLicense'))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('manageUserRecord', [$otherAdmin, 'admin']))->toBeTrue();
});

test('shared viewAny policies stay user facing while admin resource access uses viewAdminAny', function () {
    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $resourceAdmin = User::factory()->admin()->create();
    $appraisalAdmin = User::factory()->admin()->create();
    $resourceRole = Role::create([
        'name' => 'Shared ViewAny Resource Viewer',
        'slug' => 'shared_viewany_resource_viewer',
        'description' => 'Can access the shared admin resource workspaces.',
        'permissions' => [
            'admin.attendances.view',
            'admin.reimbursements.view',
            'admin.assets.view',
            'admin.payroll.view',
        ],
    ]);
    $appraisalRole = Role::create([
        'name' => 'Shared ViewAny Appraisal Viewer',
        'slug' => 'shared_viewany_appraisal_viewer',
        'description' => 'Can access appraisal administration for shared policy coverage.',
        'permissions' => ['admin.appraisals.view'],
    ]);

    $resourceAdmin->roles()->sync([$resourceRole->id]);
    $admin->roles()->detach();
    $appraisalAdmin->roles()->sync([$appraisalRole->id]);

    foreach ([
        Attendance::class,
        Reimbursement::class,
        CompanyAsset::class,
        Payroll::class,
    ] as $modelClass) {
        expect(Gate::forUser($employee)->allows('viewAny', $modelClass))->toBeTrue()
            ->and(Gate::forUser($employee)->allows('viewAdminAny', $modelClass))->toBeFalse()
            ->and(Gate::forUser($resourceAdmin)->allows('viewAny', $modelClass))->toBeTrue()
            ->and(Gate::forUser($resourceAdmin)->allows('viewAdminAny', $modelClass))->toBeTrue();
    }

    expect(Gate::forUser($employee)->allows('viewAny', Appraisal::class))->toBeTrue()
        ->and(Gate::forUser($employee)->allows('viewAdminAny', Appraisal::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAny', Appraisal::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAdminAny', Appraisal::class))->toBeFalse()
        ->and(Gate::forUser($appraisalAdmin)->allows('viewAny', Appraisal::class))->toBeTrue()
        ->and(Gate::forUser($appraisalAdmin)->allows('viewAdminAny', Appraisal::class))->toBeTrue();
});

test('every admin route declares explicit can middleware', function () {
    $missing = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'admin'))
        ->reject(fn ($route) => collect($route->gatherMiddleware())
            ->contains(fn (string $middleware) => str_starts_with($middleware, 'can:')))
        ->map(fn ($route) => $route->uri())
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

test('focused admin routes declare page specific authorization middleware', function () {
    $routeCollection = collect(Route::getRoutes()->getRoutes())->keyBy(fn ($route) => $route->uri());

    foreach ([
        'admin/dashboard' => 'can:viewAdminDashboard',
        'admin/profile' => 'can:accessAdminPanel',
        'admin/employees' => 'can:viewEmployees',
        'admin/schedules' => 'can:manageSchedules',
        'admin/masterdata/leave-types' => 'can:manageLeaveTypes',
        'admin/shift-swaps' => 'can:manageShiftSwapApprovals',
        'admin/overtime' => 'can:manageOvertime',
        'admin/notifications' => 'can:manageAdminNotifications',
        'admin/holidays' => 'can:manageHolidays',
        'admin/announcements' => 'can:manageAnnouncements',
        'admin/manage-kasbon' => 'can:manageCashAdvances',
        'admin/attendances/report' => 'can:viewAttendanceReports',
        'admin/reports' => 'can:viewOperationalReports',
        'admin/reports/leaves/export' => 'can:manageLeaveApprovals',
        'admin/reports/overtime/export' => 'can:manageOvertime',
        'admin/reports/schedules/export' => 'can:manageSchedules',
        'admin/reports/payrolls/export' => 'can:viewAdminPayroll',
        'admin/import-export/users' => 'can:viewUserImportExport',
        'admin/import-export/attendances' => 'can:viewAttendanceImportExport',
        'admin/payrolls/settings' => 'can:managePayrollSettings',
        'admin/roles-permissions' => 'can:manageRbac',
        'admin/import-export/runs/{run}/download' => 'can:download,run',
    ] as $uri => $middleware) {
        $route = $routeCollection->get($uri);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain($middleware);
    }
});

test('shared resource admin routes declare viewAdminAny middleware', function () {
    $routeCollection = collect(Route::getRoutes()->getRoutes())->keyBy(fn ($route) => $route->uri());

    foreach ([
        'admin/attendances',
        'admin/reimbursements',
        'admin/document-requests',
        'admin/assets',
        'admin/appraisals',
        'admin/payrolls',
    ] as $uri) {
        $route = $routeCollection->get($uri);

        expect($route)->not->toBeNull()
            ->and(collect($route->gatherMiddleware())->contains(
                fn (string $middleware) => str_starts_with($middleware, 'can:viewAdminAny')
            ))->toBeTrue();
    }
});

test('focused admin page routes allow admins and reject regular users', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    foreach ([
        'admin.dashboard',
        'admin.profile.show',
        'admin.employees',
        'admin.schedules',
        'admin.masters.leave-types',
        'admin.overtime',
        'admin.reports.index',
        'admin.notifications',
        'admin.holidays',
        'admin.announcements',
    ] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route($routeName))
            ->assertForbidden();
    }
});

test('shared resource admin routes allow admins and reject regular users', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    foreach ([
        'admin.attendances',
        'admin.reimbursements',
        'admin.document-requests',
    ] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route($routeName))
            ->assertForbidden();
    }
});

test('appraisal admin route requires explicit appraisal view permission', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $appraisalViewer = User::factory()->admin()->create();
    $superadmin = User::factory()->admin(true)->create();
    $role = Role::create([
        'name' => 'Appraisal Viewer Coverage',
        'slug' => 'appraisal_viewer_coverage',
        'description' => 'Can access the appraisal workspace.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.appraisals.view',
        ],
    ]);

    $admin->roles()->detach();
    $appraisalViewer->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.appraisals'))
        ->assertForbidden();

    $this->actingAs($appraisalViewer)
        ->get(route('admin.appraisals'))
        ->assertOk();

    $this->actingAs($superadmin)
        ->get(route('admin.appraisals'))
        ->assertOk();
});

test('system routes allow explicitly authorized role admins and reject plain admins', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $systemAdmin = User::factory()->admin()->create();

    $role = Role::create([
        'name' => 'System Access Manager',
        'slug' => 'system_access_manager',
        'description' => 'Can access selected system modules.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.settings.view',
            'admin.activity_logs.view',
            'admin.import_export_attendances.view',
        ],
    ]);

    $admin->roles()->detach();
    $systemAdmin->roles()->sync([$role->id]);

    foreach ([
        'admin.settings',
        'admin.activity-logs',
        'admin.import-export.attendances',
    ] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertForbidden();

        $this->actingAs($systemAdmin)
            ->get(route($routeName))
            ->assertOk();
    }
});

test('admin livewire components reject direct mounting by regular users', function () {
    $employee = User::factory()->create();

    $this->actingAs($employee);

    foreach ([
        DashboardComponent::class,
        ScheduleComponent::class,
        OvertimeManager::class,
        NotificationsPage::class,
        HolidayManager::class,
        AnnouncementManager::class,
        EmployeeDocumentRequestManager::class,
        CashAdvanceManager::class,
    ] as $component) {
        Livewire::test($component)
            ->assertForbidden();
    }
});

test('import export run download route follows resource permissions for owners and shared admin access', function () {
    Storage::fake('local');

    $ownerAdmin = User::factory()->admin()->create();
    $authorizedAdmin = User::factory()->admin()->create();
    $limitedAdmin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $attendanceExportRole = Role::create([
        'name' => 'Attendance Exporter',
        'slug' => 'attendance_exporter',
        'description' => 'Can export attendance data.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.import_export_attendances.view',
            'admin.import_export_attendances.export',
        ],
    ]);

    $limitedRole = Role::create([
        'name' => 'Dashboard Access Only',
        'slug' => 'dashboard_access_only',
        'description' => 'Cannot download shared import export runs.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $ownerAdmin->roles()->sync([$attendanceExportRole->id]);
    $authorizedAdmin->roles()->sync([$attendanceExportRole->id]);
    $limitedAdmin->roles()->sync([$limitedRole->id]);

    $path = 'import-export/exports/attendance-report.csv';
    Storage::disk('local')->put($path, "nip,date\n123,2026-04-01\n");

    $run = ImportExportRun::create([
        'resource' => 'attendances',
        'operation' => 'export',
        'status' => 'completed',
        'requested_by_user_id' => $ownerAdmin->id,
        'file_path' => $path,
        'file_name' => 'attendance-report.csv',
    ]);

    expect(Gate::forUser($ownerAdmin)->allows('download', $run))->toBeTrue()
        ->and(Gate::forUser($authorizedAdmin)->allows('download', $run))->toBeTrue()
        ->and(Gate::forUser($limitedAdmin)->allows('download', $run))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('download', $run))->toBeFalse();

    $this->actingAs($ownerAdmin)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($authorizedAdmin)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($limitedAdmin)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertForbidden();
});
