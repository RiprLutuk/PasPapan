<?php

use App\Models\Overtime;
use App\Models\User;
use Livewire\Livewire;

test('overtime request blocks overlapping pending or approved requests', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Overtime::create([
        'user_id' => $user->id,
        'date' => '2026-04-16',
        'start_time' => '18:00:00',
        'end_time' => '20:00:00',
        'duration' => 120,
        'reason' => 'Existing overtime',
        'status' => 'approved',
    ]);

    Livewire::test(\App\Livewire\OvertimeRequest::class)
        ->set('date', '2026-04-16')
        ->set('start_time', '19:00')
        ->set('end_time', '21:00')
        ->set('reason', 'Overlap overtime')
        ->call('store')
        ->assertHasErrors(['start_time']);

    expect(Overtime::count())->toBe(1);
});
