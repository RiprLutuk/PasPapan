<?php

use App\Models\Attendance;
use App\Models\LeaveType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('leave request is blocked when annual leave quota is exhausted', function () {
    $user = User::factory()->create();
    $annualLeave = LeaveType::query()->where('code', 'annual_leave')->firstOrFail();

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
        'leave_type_id' => $annualLeave->id,
        'note' => 'Need another leave day',
        'from' => now()->addDays(5)->toDateString(),
        'to' => now()->addDays(5)->toDateString(),
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('error', __('Not enough remaining annual leave quota for this request.'));
});

test('sick leave request does not use annual quota', function () {
    $user = User::factory()->create();
    $sickLeave = LeaveType::query()->where('code', 'sick_leave')->firstOrFail();

    Setting::updateOrCreate(
        ['key' => 'leave.annual_quota'],
        ['value' => '0', 'group' => 'leave', 'type' => 'number']
    );
    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '0', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache('leave.annual_quota');
    Setting::flushCache('leave.require_attachment');

    $date = now()->addDays(7)->toDateString();

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'leave_type_id' => $sickLeave->id,
        'note' => 'Medical rest',
        'from' => $date,
        'to' => $date,
        'attachment' => UploadedFile::fake()->create('medical-certificate.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('attendances', [
        'user_id' => $user->id,
        'date' => $date,
        'status' => 'sick',
        'leave_type_id' => $sickLeave->id,
        'approval_status' => Attendance::STATUS_PENDING,
    ]);
});

test('custom leave type can be requested without annual quota usage', function () {
    $user = User::factory()->create();
    $customLeave = LeaveType::create([
        'code' => 'bereavement_leave',
        'name' => 'Cuti Duka',
        'category' => LeaveType::CATEGORY_OTHER,
        'counts_against_quota' => false,
        'requires_attachment' => false,
        'is_active' => true,
        'sort_order' => 80,
    ]);

    Setting::updateOrCreate(
        ['key' => 'leave.annual_quota'],
        ['value' => '0', 'group' => 'leave', 'type' => 'number']
    );
    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '0', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache('leave.annual_quota');
    Setting::flushCache('leave.require_attachment');

    $date = now()->addDays(8)->toDateString();

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'leave_type_id' => $customLeave->id,
        'note' => 'Family bereavement',
        'from' => $date,
        'to' => $date,
    ]);

    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('attendances', [
        'user_id' => $user->id,
        'date' => $date,
        'status' => 'excused',
        'leave_type_id' => $customLeave->id,
        'approval_status' => Attendance::STATUS_PENDING,
    ]);
});

test('leave request rejects unsafe attachment types and invalid coordinates', function () {
    $user = User::factory()->create();

    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '1', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache('leave.require_attachment');

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'status' => 'sick',
        'note' => 'Need sick leave',
        'from' => now()->addDays(2)->toDateString(),
        'to' => now()->addDays(2)->toDateString(),
        'attachment' => UploadedFile::fake()->create('proof.exe', 1, 'application/x-msdownload'),
        'lat' => 91,
        'lng' => 181,
    ]);

    $response->assertSessionHasErrors(['attachment', 'lat', 'lng']);
});
