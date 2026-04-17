<?php

use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('admin can view subordinate attendance photo', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $path = 'attendance_photos/test/check-in.jpg';
    Storage::disk('public')->put($path, 'fake-image');

    $attendance = Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => $path]),
    ]);

    $response = $this->actingAs($admin)->get("/attendance/photo/{$attendance->id}/in");

    $response->assertOk();
});

test('non admin cannot view another users attendance photo', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $path = 'attendance_photos/test/check-in.jpg';
    Storage::disk('public')->put($path, 'fake-image');

    $attendance = Attendance::create([
        'user_id' => $owner->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => $path]),
    ]);

    $response = $this->actingAs($otherUser)->get("/attendance/photo/{$attendance->id}/in");

    $response->assertForbidden();
});

test('device barcode api creates check in then check out using current attendance schema', function () {
    $user = User::factory()->create();
    $barcode = Barcode::factory()->create();

    Sanctum::actingAs($user);

    $checkIn = $this->postJson('/api/device/barcode', [
        'barcode_data' => $barcode->value,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'timestamp' => '2026-04-15 08:00:00',
    ]);

    $checkIn
        ->assertOk()
        ->assertJsonPath('action', 'check_in');

    $attendance = Attendance::firstOrFail();

    expect($attendance->barcode_id)->toBe($barcode->id)
        ->and($attendance->time_in?->format('Y-m-d H:i:s'))->toBe('2026-04-15 08:00:00')
        ->and($attendance->latitude_in)->toBe(-6.2)
        ->and($attendance->longitude_in)->toBe(106.8)
        ->and($attendance->time_out)->toBeNull();

    $checkOut = $this->postJson('/api/device/barcode', [
        'barcode_data' => $barcode->value,
        'latitude' => -6.21,
        'longitude' => 106.81,
        'timestamp' => '2026-04-15 17:00:00',
    ]);

    $checkOut
        ->assertOk()
        ->assertJsonPath('action', 'check_out');

    $attendance->refresh();

    expect($attendance->time_out?->format('Y-m-d H:i:s'))->toBe('2026-04-15 17:00:00')
        ->and($attendance->latitude_out)->toBe(-6.21)
        ->and($attendance->longitude_out)->toBe(106.81);
});

test('device photo api stores attendance photo in attachment payload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->post('/api/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);

    $response->assertOk();

    $attendance = Attendance::firstOrFail();
    $attachments = json_decode($attendance->attachment, true);

    expect($attachments)->toHaveKey('in')
        ->and(Storage::disk('public')->exists($attachments['in']))->toBeTrue()
        ->and($attendance->latitude_in)->toBe(-6.2)
        ->and($attendance->longitude_in)->toBe(106.8);
});
