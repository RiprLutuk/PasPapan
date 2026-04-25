<?php

use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\HolidayManager;
use App\Livewire\Admin\MasterData\Admin as AdminDirectory;
use App\Models\Announcement;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

test('superadmin sees delete action for admin rows and other superadmin rows but not for own row', function () {
    $superadmin = User::factory()->admin(true)->create([
        'name' => 'Primary Superadmin',
        'email' => 'primary-superadmin@example.com',
    ]);
    $anotherSuperadmin = User::factory()->admin(true)->create([
        'name' => 'Secondary Superadmin',
        'email' => 'secondary-superadmin@example.com',
    ]);
    $admin = User::factory()->admin()->create([
        'name' => 'Regional Admin',
        'email' => 'regional-admin@example.com',
    ]);

    $this->actingAs($superadmin);

    $response = $this->get(route('admin.masters.admin'));

    $response->assertOk();
    $response->assertSee("confirmDeletion('{$admin->id}'", false);
    $response->assertDontSee("confirmDeletion('{$superadmin->id}'", false);
    $response->assertSee("confirmDeletion('{$anotherSuperadmin->id}'", false);
});

test('admin manager without superadmin delete permission cannot delete superadmin rows', function () {
    $manager = User::factory()->admin()->create();
    $targetSuperadmin = User::factory()->admin(true)->create();
    $accessRole = Role::create([
        'name' => 'Superadmin Manager Without Delete',
        'slug' => 'superadmin_manager_without_delete',
        'description' => 'Can manage but not delete superadmin accounts.',
        'permissions' => [
            'admin.dashboard.view',
            'admin.admin_accounts.view',
            'admin.admin_accounts.manage',
            'admin.admin_accounts.superadmin_view',
            'admin.admin_accounts.superadmin_manage',
        ],
    ]);

    $manager->roles()->sync([$accessRole->id]);

    $this->actingAs($manager);

    $this->get(route('admin.masters.admin'))
        ->assertOk()
        ->assertDontSee("confirmDeletion('{$targetSuperadmin->id}'", false);

    Livewire::test(AdminDirectory::class)
        ->call('confirmDeletion', $targetSuperadmin->id)
        ->call('delete')
        ->assertForbidden();
});

test('superadmin can delete admin account from admin directory', function () {
    $superadmin = User::factory()->admin(true)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('confirmDeletion', $admin->id)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('users', ['id' => $admin->id]);
});

test('superadmin cannot delete own account from admin directory', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('confirmDeletion', $superadmin->id)
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
});

test('superadmin can delete announcements', function () {
    $superadmin = User::factory()->admin(true)->create();
    $announcement = Announcement::create([
        'title' => 'Maintenance Window',
        'content' => 'The app will be down briefly.',
        'priority' => 'normal',
        'modal_behavior' => 'acknowledge',
        'publish_date' => now()->toDateString(),
        'is_active' => true,
        'created_by' => $superadmin->id,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(AnnouncementManager::class)
        ->call('delete', $announcement->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
});

test('admin can delete announcements', function () {
    $admin = User::factory()->admin()->create();
    $announcement = Announcement::create([
        'title' => 'Admin Announcement',
        'content' => 'The app is available.',
        'priority' => 'normal',
        'modal_behavior' => 'acknowledge',
        'publish_date' => now()->toDateString(),
        'is_active' => true,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(AnnouncementManager::class)
        ->call('delete', $announcement->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
});

test('superadmin can delete holidays', function () {
    $superadmin = User::factory()->admin(true)->create();
    $holiday = Holiday::create([
        'date' => now()->addWeek()->toDateString(),
        'name' => 'National Leave',
        'description' => 'Temporary test holiday.',
        'is_recurring' => false,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(HolidayManager::class)
        ->call('delete', $holiday->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
});

test('admin can delete holidays', function () {
    $admin = User::factory()->admin()->create();
    $holiday = Holiday::create([
        'date' => now()->addWeek()->toDateString(),
        'name' => 'Admin Holiday',
        'description' => 'Temporary test holiday.',
        'is_recurring' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(HolidayManager::class)
        ->call('delete', $holiday->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
});
