<?php

use App\Livewire\Admin\MasterData\Admin as AdminDirectory;
use App\Models\Role;
use App\Models\User;
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
        ->set('form.role_ids', [$superAdminRole->id])
        ->call('update')
        ->assertForbidden();
});
