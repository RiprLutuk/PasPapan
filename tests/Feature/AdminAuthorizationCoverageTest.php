<?php

use App\Jobs\ProcessActivityLogExportRun;
use App\Jobs\ProcessAttendanceImportRun;
use App\Jobs\ProcessUserImportRun;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\DashboardComponent;
use App\Livewire\Admin\EmployeeDocumentRequestManager;
use App\Livewire\Admin\Finance\CashAdvanceManager;
use App\Livewire\Admin\HolidayManager;
use App\Livewire\Admin\NotificationsPage;
use App\Livewire\Admin\OvertimeManager;
use App\Livewire\Admin\ScheduleComponent;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\ImportExportRun;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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
        ->and(Gate::forUser($admin)->allows('manageCashAdvances'))->toBeTrue()
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

test('shared viewAny policies stay user facing while admin resource access uses viewAdminAny', function () {
    $employee = User::factory()->create();
    $admin = User::factory()->admin()->create();

    foreach ([
        Attendance::class,
        Reimbursement::class,
        CompanyAsset::class,
        Appraisal::class,
        Payroll::class,
    ] as $modelClass) {
        expect(Gate::forUser($employee)->allows('viewAny', $modelClass))->toBeTrue()
            ->and(Gate::forUser($employee)->allows('viewAdminAny', $modelClass))->toBeFalse()
            ->and(Gate::forUser($admin)->allows('viewAny', $modelClass))->toBeTrue()
            ->and(Gate::forUser($admin)->allows('viewAdminAny', $modelClass))->toBeTrue();
    }
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
        'admin/employees' => 'can:viewEmployees',
        'admin/schedules' => 'can:manageSchedules',
        'admin/overtime' => 'can:manageOvertime',
        'admin/notifications' => 'can:manageAdminNotifications',
        'admin/holidays' => 'can:manageHolidays',
        'admin/announcements' => 'can:manageAnnouncements',
        'admin/manage-kasbon' => 'can:manageCashAdvances',
        'admin/attendances/report' => 'can:viewAttendanceReports',
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
        'admin.employees',
        'admin.schedules',
        'admin.overtime',
        'admin.notifications',
        'admin.holidays',
        'admin.announcements',
        'admin.manage-kasbon',
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
        'admin.import-export.attendances',
        'admin.reimbursements',
        'admin.document-requests',
        'admin.assets',
        'admin.appraisals',
        'admin.payrolls',
        'admin.payroll.settings',
    ] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route($routeName))
            ->assertForbidden();
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

test('import export run download route allows authorized owner admin and blocks others', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $path = 'import-export/exports/attendance-report.csv';
    Storage::disk('local')->put($path, "nip,date\n123,2026-04-01\n");

    $run = ImportExportRun::create([
        'resource' => 'attendances',
        'operation' => 'export',
        'status' => 'completed',
        'requested_by_user_id' => $admin->id,
        'file_path' => $path,
        'file_name' => 'attendance-report.csv',
    ]);

    expect(Gate::forUser($admin)->allows('download', $run))->toBeTrue()
        ->and(Gate::forUser($otherAdmin)->allows('download', $run))->toBeFalse()
        ->and(Gate::forUser($employee)->allows('download', $run))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($otherAdmin)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.import-export.runs.download', $run))
        ->assertForbidden();
});
