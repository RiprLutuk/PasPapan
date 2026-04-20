<?php

use App\Livewire\Admin\MasterData\Admin as AdminDirectory;
use App\Models\User;
use Livewire\Livewire;

test('superadmin can create admin account from admin directory', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->set('form.name', 'Finance Admin')
        ->set('form.nip', '909090')
        ->set('form.email', 'finance-admin@example.com')
        ->set('form.phone', '09090909')
        ->set('form.password', 'admin123')
        ->set('form.gender', 'male')
        ->set('form.address', 'Jl. Jend. Sudirman No. 1')
        ->set('form.group', 'admin')
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'finance-admin@example.com',
        'group' => 'admin',
        'gender' => 'male',
        'address' => 'Jl. Jend. Sudirman No. 1',
    ]);
});

test('admin directory create validates required gender before insert', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->set('form.name', 'Ops Admin')
        ->set('form.nip', '808080')
        ->set('form.email', 'ops-admin@example.com')
        ->set('form.phone', '08080808')
        ->set('form.password', 'admin123')
        ->set('form.address', 'Jl. Veteran No. 2')
        ->set('form.group', 'superadmin')
        ->call('create')
        ->assertHasErrors(['form.gender' => 'required']);

    $this->assertDatabaseMissing('users', [
        'email' => 'ops-admin@example.com',
    ]);
});

test('superadmin can update admin account from admin directory', function () {
    $superadmin = User::factory()->admin(true)->create();
    $admin = User::factory()->admin()->create([
        'name' => 'Old Admin',
        'email' => 'old-admin@example.com',
        'gender' => 'male',
        'address' => 'Jl. Lama',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(AdminDirectory::class)
        ->call('edit', $admin->id)
        ->set('form.name', 'Updated Admin')
        ->set('form.phone', '08123456789')
        ->set('form.gender', 'female')
        ->set('form.address', 'Jl. Baru No. 99')
        ->call('update')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'name' => 'Updated Admin',
        'gender' => 'female',
        'address' => 'Jl. Baru No. 99',
    ]);
});
