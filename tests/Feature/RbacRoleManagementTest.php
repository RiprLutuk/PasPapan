<?php

use App\Livewire\Admin\MasterData\Admin as AdminDirectory;
use App\Livewire\Admin\AttendanceCorrectionManager;
use App\Livewire\Admin\AppraisalManager;
use App\Livewire\Admin\ReimbursementManager;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Notifications\CashAdvanceRequested;
use App\Support\UserNotificationRecipientService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('superadmin can access role permission management', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin)
        ->get(route('admin.roles.permissions'))
        ->assertOk();
});

test('unauthorized admin cannot access role permission management', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.roles.permissions'))
        ->assertForbidden();
});

test('explicitly authorized admin can access role permission management', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Access Manager',
        'slug' => 'access_manager',
        'description' => 'Can manage access roles.',
        'permissions' => ['admin.dashboard.view', 'admin.rbac.manage'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.roles.permissions'))
        ->assertOk();
});

test('assigned role permission grants menu access and blocks unrelated admin modules', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Limited Finance',
        'slug' => 'limited_finance',
        'description' => 'Can only open reimbursements.',
        'permissions' => ['admin.dashboard.view', 'admin.reimbursements.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.reimbursements'))
        ->assertOk()
        ->assertSee(route('admin.reimbursements'))
        ->assertDontSee(route('admin.employees'))
        ->assertDontSee(route('admin.roles.permissions'));

    $this->actingAs($admin)
        ->get(route('admin.employees'))
        ->assertForbidden();
});

test('users cannot change their own role assignment', function () {
    $superadmin = User::factory()->admin(true)->create();
    $role = Role::create([
        'name' => 'HR Custom',
        'slug' => 'hr_custom',
        'description' => 'Temporary role for testing.',
        'permissions' => ['admin.dashboard.view', 'admin.employees.view'],
    ]);

    Livewire::actingAs($superadmin)
        ->test(AdminDirectory::class)
        ->call('edit', $superadmin->id)
        ->set('form.role_ids', [$role->id])
        ->call('update')
        ->assertForbidden();
});

test('non superadmin cannot assign the super admin role', function () {
    $roleManager = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create();
    $accessRole = Role::create([
        'name' => 'Role Assigner',
        'slug' => 'role_assigner',
        'description' => 'Can manage admin accounts and assign roles.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.admin_accounts.view',
            'admin.admin_accounts.manage',
            'admin.rbac.assign',
        ],
    ]);

    $roleManager->roles()->sync([$accessRole->id]);

    $superAdminRole = Role::query()->where('slug', 'super_admin')->firstOrFail();

    Livewire::actingAs($roleManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetAdmin->id)
        ->set('form.address', 'Jl. Role Manager No. 1')
        ->set('form.role_ids', [$superAdminRole->id])
        ->call('update')
        ->assertForbidden();
});

test('admin account manager role can update another non superadmin admin account', function () {
    $roleManager = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create([
        'name' => 'Original Admin Name',
    ]);

    $accessRole = Role::create([
        'name' => 'Admin Account Manager',
        'slug' => 'admin_account_manager',
        'description' => 'Can manage administrator accounts.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.admin_accounts.view',
            'admin.admin_accounts.manage',
        ],
    ]);

    $roleManager->roles()->sync([$accessRole->id]);

    Livewire::actingAs($roleManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetAdmin->id)
        ->set('form.address', 'Jl. Update Admin No. 1')
        ->set('form.name', 'Updated Admin Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($targetAdmin->fresh()->name)->toBe('Updated Admin Name');
});

test('explicitly authorized admin can manage another superadmin account', function () {
    $superadminManager = User::factory()->admin()->create();
    $targetSuperadmin = User::factory()->admin(true)->create([
        'name' => 'Original Superadmin Name',
    ]);

    $accessRole = Role::create([
        'name' => 'Superadmin Account Manager',
        'slug' => 'superadmin_account_manager',
        'description' => 'Can view and manage superadmin accounts.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.admin_accounts.view',
            'admin.admin_accounts.manage',
            'admin.admin_accounts.superadmin_view',
            'admin.admin_accounts.superadmin_manage',
        ],
    ]);

    $superadminManager->roles()->sync([$accessRole->id]);

    expect($superadminManager->canViewSuperadminAccounts())->toBeTrue()
        ->and($superadminManager->canManageSuperadminAccounts())->toBeTrue()
        ->and(Gate::forUser($superadminManager)->allows('manageUserRecord', [$targetSuperadmin, 'superadmin']))->toBeTrue();

    Livewire::actingAs($superadminManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetSuperadmin->id)
        ->set('form.address', 'Jl. Superadmin No. 9')
        ->set('form.name', 'Updated Superadmin Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($targetSuperadmin->fresh()->name)->toBe('Updated Superadmin Name');
});

test('explicitly authorized admin can assign the super admin role', function () {
    $roleManager = User::factory()->admin()->create();
    $targetAdmin = User::factory()->admin()->create();

    $accessRole = Role::create([
        'name' => 'Superadmin Role Assigner',
        'slug' => 'superadmin_role_assigner',
        'description' => 'Can assign roles including the super admin role.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.admin_accounts.view',
            'admin.admin_accounts.manage',
            'admin.admin_accounts.superadmin_view',
            'admin.admin_accounts.superadmin_manage',
            'admin.rbac.assign',
        ],
    ]);

    $roleManager->roles()->sync([$accessRole->id]);

    $superAdminRole = Role::query()->where('slug', 'super_admin')->firstOrFail();

    Livewire::actingAs($roleManager)
        ->test(AdminDirectory::class)
        ->call('edit', $targetAdmin->id)
        ->set('form.address', 'Jl. Promote Admin No. 7')
        ->set('form.role_ids', [$superAdminRole->id])
        ->call('update')
        ->assertHasNoErrors();

    expect($targetAdmin->fresh()->roles()->where('slug', 'super_admin')->exists())->toBeTrue();
});

test('assigned-role admin notifications require notifications permission', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Dashboard Only',
        'slug' => 'dashboard_only',
        'description' => 'Can only open the dashboard.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.notifications'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('notifications'))
        ->assertOk();
});

test('limited admin root routes fall back to the first permitted admin page', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Notifications Only',
        'slug' => 'notifications_only',
        'description' => 'Can only access admin notifications.',
        'permissions' => ['admin.notifications.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('admin.notifications'));

    $this->actingAs($admin)
        ->get('/')
        ->assertRedirect(route('admin.notifications'));
});

test('assigned-role admin system maintenance access requires explicit permission', function () {
    $maintenanceAdmin = User::factory()->admin()->create();
    $limitedAdmin = User::factory()->admin()->create();

    $maintenanceRole = Role::create([
        'name' => 'Maintenance Viewer',
        'slug' => 'maintenance_viewer',
        'description' => 'Can open system maintenance.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.system_maintenance.view',
        ],
    ]);

    $limitedRole = Role::create([
        'name' => 'Limited Admin Access',
        'slug' => 'limited_admin_access',
        'description' => 'Cannot open maintenance.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $maintenanceAdmin->roles()->sync([$maintenanceRole->id]);
    $limitedAdmin->roles()->sync([$limitedRole->id]);

    expect(Gate::forUser($maintenanceAdmin)->allows('viewAny', SystemBackupRun::class))->toBeTrue()
        ->and(Gate::forUser($limitedAdmin)->allows('viewAny', SystemBackupRun::class))->toBeFalse();

    $this->actingAs($maintenanceAdmin)
        ->get(route('admin.system-maintenance'))
        ->assertOk();

    $this->actingAs($limitedAdmin)
        ->get(route('admin.system-maintenance'))
        ->assertForbidden();
});

test('view-only appraisal admins cannot edit or calibrate appraisals', function () {
    enableEnterpriseAttendanceForTests();

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Appraisal Viewer',
        'slug' => 'appraisal_viewer',
        'description' => 'Can view appraisals without changing them.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.appraisals.view',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $appraisal = Appraisal::create([
        'user_id' => $employee->id,
        'evaluator_id' => $admin->id,
        'period_month' => now()->month,
        'period_year' => now()->year,
        'status' => 'completed',
        'calibration_status' => 'pending',
    ]);

    expect(Gate::forUser($admin)->allows('viewAdminAny', Appraisal::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage', Appraisal::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('calibrate', $appraisal))->toBeFalse();

    Livewire::actingAs($admin)
        ->test(AppraisalManager::class)
        ->call('initOrEvaluate', $employee->id)
        ->assertForbidden();

    Livewire::actingAs($admin)
        ->test(AppraisalManager::class)
        ->call('calibrate', $appraisal->id, 'approved')
        ->assertForbidden();
});

test('explicitly authorized appraisal calibrator can approve pending calibration', function () {
    enableEnterpriseAttendanceForTests();

    $calibrator = User::factory()->admin()->create();
    $manager = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Appraisal Calibrator',
        'slug' => 'appraisal_calibrator',
        'description' => 'Can calibrate completed appraisals.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.appraisals.view',
            'admin.appraisals.calibrate',
        ],
    ]);

    $calibrator->roles()->sync([$role->id]);

    $appraisal = Appraisal::create([
        'user_id' => $employee->id,
        'evaluator_id' => $manager->id,
        'period_month' => now()->month,
        'period_year' => now()->year,
        'status' => 'completed',
        'calibration_status' => 'pending',
    ]);

    Livewire::actingAs($calibrator)
        ->test(AppraisalManager::class)
        ->call('calibrate', $appraisal->id, 'approved')
        ->assertHasNoErrors();

    $appraisal->refresh();

    expect($appraisal->calibrator_id)->toBe($calibrator->id)
        ->and($appraisal->calibration_status)->toBe('approved');
});

test('view-only reimbursement admin cannot approve reimbursement requests', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Reimbursement Viewer',
        'slug' => 'reimbursement_viewer',
        'description' => 'Can view reimbursements without approving them.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.reimbursements.view',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $reimbursement = Reimbursement::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'type' => 'Meal',
        'amount' => 75000,
        'description' => 'Lunch meeting',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reimbursements'))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(ReimbursementManager::class)
        ->assertDontSee(__('Approve this claim?'))
        ->assertDontSee(__('Reject this claim?'))
        ->call('approve', $reimbursement->id)
        ->assertForbidden();
});

test('view-only attendance correction admin cannot approve corrections', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Correction Viewer',
        'slug' => 'correction_viewer',
        'description' => 'Can view attendance corrections without approving them.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.attendance_corrections.view',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $correction = AttendanceCorrection::create([
        'user_id' => $employee->id,
        'attendance_date' => now()->toDateString(),
        'request_type' => AttendanceCorrection::TYPE_WRONG_TIME,
        'reason' => 'Sync issue',
        'status' => AttendanceCorrection::STATUS_PENDING_ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.attendance-corrections'))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(AttendanceCorrectionManager::class)
        ->call('approve', $correction->id)
        ->assertForbidden();
});

test('admin without cash advance permission cannot manage cash advance approvals', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Dashboard Only Admin',
        'slug' => 'dashboard_only_admin',
        'description' => 'Can access admin home only.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 200000,
        'purpose' => 'Travel advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    expect(Gate::forUser($admin)->allows('approve', $advance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('reject', $advance))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $advance))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.manage-kasbon'))
        ->assertForbidden();
});

test('cash advance request notifications only target actual reviewers', function () {
    Notification::fake();

    $operations = \App\Models\Division::create(['name' => 'Operations']);
    $managerLevel = \App\Models\JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = \App\Models\JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = \App\Models\JobTitle::create([
        'name' => 'Operations Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $operations->id,
    ]);

    $staffTitle = \App\Models\JobTitle::create([
        'name' => 'Operations Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $operations->id,
    ]);

    $manager = User::factory()->create([
        'division_id' => $operations->id,
        'job_title_id' => $managerTitle->id,
    ]);

    $employee = User::factory()->create([
        'division_id' => $operations->id,
        'job_title_id' => $staffTitle->id,
    ]);

    $cashAdvanceAdmin = User::factory()->admin()->create();
    $viewOnlyAdmin = User::factory()->admin()->create();
    $cashAdvanceRole = Role::create([
        'name' => 'Cash Advance Reviewer',
        'slug' => 'cash_advance_reviewer',
        'description' => 'Can review cash advance requests.',
        'permissions' => ['admin.cash_advances.manage'],
    ]);

    $viewOnlyRole = Role::create([
        'name' => 'View Only Finance',
        'slug' => 'view_only_finance',
        'description' => 'Cannot manage cash advances.',
        'permissions' => ['admin.dashboard.view', 'admin.reimbursements.view'],
    ]);

    $cashAdvanceAdmin->roles()->sync([$cashAdvanceRole->id]);
    $viewOnlyAdmin->roles()->sync([$viewOnlyRole->id]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 350000,
        'purpose' => 'Field travel',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $recipientCount = app(UserNotificationRecipientService::class)->notifyCashAdvanceRequested($advance);

    expect($recipientCount)->toBe(2);

    Notification::assertSentTo($manager, CashAdvanceRequested::class, function (CashAdvanceRequested $notification, array $channels) use ($manager) {
        return $notification->toArray($manager)['url'] === route('team-kasbon', absolute: false);
    });

    Notification::assertSentTo($cashAdvanceAdmin, CashAdvanceRequested::class, function (CashAdvanceRequested $notification, array $channels) use ($cashAdvanceAdmin) {
        return $notification->toArray($cashAdvanceAdmin)['url'] === route('admin.manage-kasbon', absolute: false);
    });

    Notification::assertNotSentTo($viewOnlyAdmin, CashAdvanceRequested::class);
});

test('dashboard-only admin dashboard hides unauthorized workflow links and counts', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $role = Role::create([
        'name' => 'Dashboard Only Access',
        'slug' => 'dashboard_only_access',
        'description' => 'Can only open the dashboard.',
        'permissions' => ['admin.dashboard.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'sick',
        'approval_status' => 'pending',
    ]);

    Reimbursement::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'type' => 'Meal',
        'amount' => 50000,
        'description' => 'Team lunch',
        'status' => 'pending',
    ]);

    CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 150000,
        'purpose' => 'Fuel advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('Pending') . ': 0')
        ->assertDontSee(route('admin.leaves'))
        ->assertDontSee(route('admin.attendance-corrections'))
        ->assertDontSee(route('admin.reimbursements'))
        ->assertDontSee(route('admin.overtime'))
        ->assertDontSee(route('admin.manage-kasbon'))
        ->assertDontSee(route('admin.notifications'))
        ->assertDontSee(route('admin.activity-logs'))
        ->assertDontSee(route('admin.employees'));
});

test('view-only activity log admins do not see export action', function () {
    $admin = User::factory()->admin()->create();

    $role = Role::create([
        'name' => 'Activity Log Viewer',
        'slug' => 'activity_log_viewer',
        'description' => 'Can view logs without exporting them.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.activity_logs.view',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin)
        ->get(route('admin.activity-logs'))
        ->assertOk()
        ->assertSee(__('Read-only audit access'))
        ->assertDontSee(route('admin.activity-logs.export'));
});
