<?php

use App\Livewire\Admin\ScheduleComponent;
use App\Models\User;
use Livewire\Livewire;

test('admin schedule month and year filters update calendar state', function () {
    $admin = User::factory()->admin(true)->create();
    User::factory()->create(['name' => 'Schedule User']);

    $this->actingAs($admin);

    Livewire::test(ScheduleComponent::class)
        ->set('month', 3)
        ->assertSet('month', 3)
        ->set('year', now()->addYear()->year)
        ->assertSet('year', now()->addYear()->year)
        ->assertSee('schedule-month')
        ->assertSee('schedule-year');
});
