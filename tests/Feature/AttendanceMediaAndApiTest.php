<?php

use App\Contracts\AttendanceServiceInterface;
use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('admin can view subordinate attendance photo', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $path = 'attendance_photos/test/check-in.jpg';
    Storage::disk('local')->put($path, 'fake-image');

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

test('enterprise attendance service returns secure attachment routes for multi-photo attachments', function () {
    enableEnterpriseAttendanceForTests();
    app()->forgetInstance(AttendanceServiceInterface::class);

    $attendance = Attendance::create([
        'user_id' => User::factory()->create()->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode([
            'in' => 'attendance_photos/test/check-in.jpg',
            'out' => 'attendance_photos/test/check-out.jpg',
        ]),
    ]);

    $service = app(AttendanceServiceInterface::class);
    $urls = $service->getAttachmentUrl($attendance);

    expect($urls)->toBeArray()
        ->and($urls['in'])->toBe(route('attendance.attachment.download', ['attendance' => $attendance->id]))
        ->and($urls['out'])->toBe(route('attendance.attachment.download', ['attendance' => $attendance->id]));
});

test('attendance photo route rejects unsafe attachment paths', function () {
    $owner = User::factory()->create();

    $attendance = Attendance::create([
        'user_id' => $owner->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => '../secrets.txt']),
    ]);

    $response = $this->actingAs($owner)->get(route('attendance.photo', [
        'attendance' => $attendance->id,
        'type' => 'in',
    ]));

    $response->assertNotFound();
});

test('device barcode api creates check in then check out using current attendance schema', function () {
    $user = User::factory()->create();
    $barcode = Barcode::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);

    Sanctum::actingAs($user, deviceApiAbilities());

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

test('device barcode api rejects scans outside checkpoint radius', function () {
    $user = User::factory()->create();
    $barcode = Barcode::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 50,
    ]);

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->postJson('/api/device/barcode', [
        'barcode_data' => $barcode->value,
        'latitude' => -7.2,
        'longitude' => 107.8,
        'timestamp' => '2026-04-15 08:00:00',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJson(fn ($json) => $json
            ->where('message', fn (string $message) => str_starts_with($message, 'Location out of range:'))
            ->etc()
        );

    expect(Attendance::count())->toBe(0);
});

test('device barcode api requires same checkpoint for check out', function () {
    $user = User::factory()->create();
    $checkInBarcode = Barcode::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);
    $otherBarcode = Barcode::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);

    Sanctum::actingAs($user, deviceApiAbilities());

    $this->postJson('/api/device/barcode', [
        'barcode_data' => $checkInBarcode->value,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'timestamp' => '2026-04-15 08:00:00',
    ])->assertOk();

    $response = $this->postJson('/api/device/barcode', [
        'barcode_data' => $otherBarcode->value,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'timestamp' => '2026-04-15 17:00:00',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', __('Please scan the same checkpoint used for check in.'));

    expect(Attendance::count())->toBe(1)
        ->and(Attendance::first()->time_out)->toBeNull();
});

test('device photo api stores attendance photo in attachment payload', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->post('/api/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);

    $response->assertOk();

    $attendance = Attendance::firstOrFail();
    $attachments = json_decode($attendance->attachment, true);

    expect($attachments)->toHaveKey('in')
        ->and(Storage::disk('local')->exists($attachments['in']))->toBeTrue()
        ->and($attendance->latitude_in)->toBe(-6.2)
        ->and($attendance->longitude_in)->toBe(106.8);
});

test('device barcode api rejects tokens without barcode ability', function () {
    $user = User::factory()->create();
    $barcode = Barcode::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);

    Sanctum::actingAs($user, [\App\Support\ApiTokenPermission::DEVICE_LOCATION]);

    $response = $this->postJson('/api/device/barcode', [
        'barcode_data' => $barcode->value,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'timestamp' => '2026-04-15 08:00:00',
    ]);

    $response->assertForbidden();

    expect(Attendance::count())->toBe(0);
});

test('device permissions api requires explicit permissions ability', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, [\App\Support\ApiTokenPermission::DEVICE_BARCODE]);

    $this->getJson('/api/device/permissions')
        ->assertForbidden();

    Sanctum::actingAs($user, [\App\Support\ApiTokenPermission::DEVICE_PERMISSIONS]);

    $this->getJson('/api/device/permissions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('permissions.camera.state', 'prompt');
});
