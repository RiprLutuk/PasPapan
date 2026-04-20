<?php

use App\Livewire\Admin\EmployeeComponent;
use App\Models\User;
use Livewire\Livewire;

test('employee page delete button renders valid livewire click binding', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create(['name' => 'Budi "Operator"']);

    $this->actingAs($superadmin);

    $response = $this->get(route('admin.employees'));

    $response->assertOk();
    $response->assertSee($employee->name);
});

test('employee component can open delete confirmation and delete user', function () {
    $superadmin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(EmployeeComponent::class)
        ->call('confirmDeletion', $employee->id)
        ->assertSet('confirmingDeletion', true)
        ->assertSet('deleteName', $employee->name)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('users', ['id' => $employee->id]);
});
