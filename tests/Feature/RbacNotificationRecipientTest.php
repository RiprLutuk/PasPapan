<?php

use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AssetReturnOtpRequested;
use App\Notifications\LeaveRequested;
use App\Notifications\OvertimeRequested;
use App\Notifications\ReimbursementRequested;
use App\Services\Attendance\LeaveRequestService;
use App\Support\UserAssetService;
use App\Support\UserNotificationRecipientService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

function createNotificationHierarchy(string $divisionName = 'Operations'): array
{
    $division = Division::create(['name' => $divisionName]);
    $managerLevel = JobLevel::create(['name' => $divisionName.' Manager Level', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => $divisionName.' Staff Level', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => $divisionName.' Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => $divisionName.' Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    $manager = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $managerTitle->id,
    ]);

    $employee = User::factory()->create([
        'division_id' => $division->id,
        'job_title_id' => $staffTitle->id,
    ]);

    return [$manager, $employee];
}

function createFinanceHeadReviewer(bool $admin = false): User
{
    $division = Division::create(['name' => 'Finance']);
    $level = JobLevel::create(['name' => 'Finance Head Level', 'rank' => 2]);
    $title = JobTitle::create([
        'name' => 'Finance Head',
        'job_level_id' => $level->id,
        'division_id' => $division->id,
    ]);

    $factory = $admin ? User::factory()->admin() : User::factory();

    return $factory->create([
        'division_id' => $division->id,
        'job_title_id' => $title->id,
    ]);
}

test('leave request notifications only target supervisor and explicit leave approvers', function () {
    Notification::fake();

    [$manager, $employee] = createNotificationHierarchy();
    $leaveAdmin = User::factory()->admin()->create();
    $dashboardAdmin = User::factory()->admin()->create();

    $leaveApproverRole = Role::create([
        'name' => 'Leave Approver',
        'slug' => 'leave_approver_notification',
        'description' => 'Can review leave requests.',
        'permissions' => ['admin.leave_approvals.approve'],
    ]);

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Notifications',
        'slug' => 'dashboard_only_notifications',
        'description' => 'Cannot review leave requests.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $leaveAdmin->roles()->sync([$leaveApproverRole->id]);
    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $result = app(LeaveRequestService::class)->submitLeaveRequest(
        $employee,
        'leave',
        'Family event',
        Carbon::tomorrow(),
        Carbon::tomorrow(),
    );

    expect($result->ok)->toBeTrue();

    $attendance = Attendance::query()->where('user_id', $employee->id)->latest('created_at')->firstOrFail();

    Notification::assertSentTo($manager, LeaveRequested::class, fn (LeaveRequested $notification) => $notification->attendance->is($attendance));
    Notification::assertSentTo($leaveAdmin, LeaveRequested::class, fn (LeaveRequested $notification) => $notification->attendance->is($attendance));
    Notification::assertNotSentTo($dashboardAdmin, LeaveRequested::class);
});

test('reimbursement request notifications only target supervisor and reimbursement approvers', function () {
    Notification::fake();

    [$manager, $employee] = createNotificationHierarchy();
    $financeHead = createFinanceHeadReviewer();
    $dashboardAdmin = User::factory()->admin()->create();

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Reimbursement Notifications',
        'slug' => 'dashboard_only_reimbursement_notifications',
        'description' => 'Cannot review reimbursement requests.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $reimbursement = Reimbursement::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'type' => 'Transport',
        'amount' => 125000,
        'description' => 'Taxi to client site',
        'status' => 'pending',
    ]);

    $service = app(UserNotificationRecipientService::class);
    $recipientEmails = $service->reimbursementApprovers($employee)->pluck('email')->all();
    $recipientCount = $service->notifyReimbursementRequested($reimbursement);

    expect($recipientEmails)->toEqualCanonicalizing([$manager->email, $financeHead->email])
        ->and($recipientCount)->toBe(2);

    Notification::assertSentTo($manager, ReimbursementRequested::class, fn (ReimbursementRequested $notification) => $notification->reimbursement->is($reimbursement));
    Notification::assertSentTo($financeHead, ReimbursementRequested::class, fn (ReimbursementRequested $notification) => $notification->reimbursement->is($reimbursement));
    Notification::assertNotSentTo($dashboardAdmin, ReimbursementRequested::class);
});

test('overtime request notifications only target supervisor and overtime approvers', function () {
    Notification::fake();

    [$manager, $employee] = createNotificationHierarchy();
    $overtimeAdmin = User::factory()->admin()->create();
    $dashboardAdmin = User::factory()->admin()->create();

    $overtimeApproverRole = Role::create([
        'name' => 'Overtime Approver',
        'slug' => 'overtime_approver_notification',
        'description' => 'Can review overtime requests.',
        'permissions' => ['admin.overtime.manage'],
    ]);

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Overtime Notifications',
        'slug' => 'dashboard_only_overtime_notifications',
        'description' => 'Cannot review overtime requests.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $overtimeAdmin->roles()->sync([$overtimeApproverRole->id]);
    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $overtime = Overtime::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'start_time' => '18:00:00',
        'end_time' => '20:00:00',
        'duration' => 120,
        'reason' => 'Server maintenance',
        'status' => 'pending',
    ]);

    $recipientEmails = app(UserNotificationRecipientService::class)->overtimeApprovers($employee)->pluck('email')->all();
    $recipientCount = app(UserNotificationRecipientService::class)->notifyOvertimeRequested($overtime);

    expect($recipientEmails)->toEqualCanonicalizing([$manager->email, $overtimeAdmin->email])
        ->and($recipientCount)->toBe(2);

    Notification::assertSentTo($manager, OvertimeRequested::class, fn (OvertimeRequested $notification) => $notification->overtime->is($overtime));
    Notification::assertSentTo($overtimeAdmin, OvertimeRequested::class, fn (OvertimeRequested $notification) => $notification->overtime->is($overtime));
    Notification::assertNotSentTo($dashboardAdmin, OvertimeRequested::class);
});

test('asset return otp notifications fall back to explicit asset admins when no supervisor exists', function () {
    Notification::fake();

    $employee = User::factory()->create([
        'division_id' => null,
        'job_title_id' => null,
    ]);
    $assetAdmin = User::factory()->admin()->create();
    $dashboardAdmin = User::factory()->admin()->create();

    $assetViewerRole = Role::create([
        'name' => 'Asset Viewer',
        'slug' => 'asset_viewer_notification',
        'description' => 'Can access company asset administration.',
        'permissions' => ['admin.assets.view'],
    ]);

    $dashboardOnlyRole = Role::create([
        'name' => 'Dashboard Only Asset Notifications',
        'slug' => 'dashboard_only_asset_notifications',
        'description' => 'Cannot access company asset administration.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $assetAdmin->roles()->sync([$assetViewerRole->id]);
    $dashboardAdmin->roles()->sync([$dashboardOnlyRole->id]);

    $asset = CompanyAsset::create([
        'name' => 'Laptop Aset',
        'type' => 'electronics',
        'user_id' => $employee->id,
        'date_assigned' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    app(UserAssetService::class)->requestReturnOtp($employee, $asset);

    Notification::assertSentTo($assetAdmin, AssetReturnOtpRequested::class);
    Notification::assertNotSentTo($dashboardAdmin, AssetReturnOtpRequested::class);
});
