<?php

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;

test('leave request is blocked when annual leave quota is exhausted', function () {
    $user = User::factory()->create();

    Setting::updateOrCreate(
        ['key' => 'leave.annual_quota'],
        ['value' => '1', 'group' => 'leave', 'type' => 'number']
    );
    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '0', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache('leave.annual_quota');
    Setting::flushCache('leave.require_attachment');

    Attendance::create([
        'user_id' => $user->id,
        'date' => now()->startOfYear()->addDay()->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
        'note' => 'Used quota',
    ]);

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'status' => 'excused',
        'note' => 'Need another leave day',
        'from' => now()->addDays(5)->toDateString(),
        'to' => now()->addDays(5)->toDateString(),
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('error', __('Not enough remaining annual leave quota for this request.'));
});
