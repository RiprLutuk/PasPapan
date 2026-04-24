<?php

use App\Models\Attendance;
use App\Models\Barcode;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Support\DynamicBarcodeTokenService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

test('dynamic checkpoint rejects its static barcode value on device api', function () {
    $user = User::factory()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-001',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->postJson('/api/device/barcode', [
        'barcode_data' => $barcode->value,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'timestamp' => '2026-04-18 08:00:00',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Invalid barcode');
});

test('dynamic barcode token can be used by device attendance api', function () {
    $user = User::factory()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-002',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);

    $token = app(DynamicBarcodeTokenService::class)
        ->generateTokenPayload($barcode, now())['token'];

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->postJson('/api/device/barcode', [
        'barcode_data' => $token,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'timestamp' => '2026-04-18 08:00:00',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('action', 'check_in');

    $attendance = Attendance::firstOrFail();

    expect($attendance->barcode_id)->toBe($barcode->id)
        ->and($attendance->time_in?->format('Y-m-d H:i:s'))->toBe('2026-04-18 08:00:00')
        ->and(app(DynamicBarcodeTokenService::class)->resolveScannedBarcode($token))->toBeNull();
});

test('dynamic barcode token uses random nonce and does not expose static barcode value', function () {
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-SECRET-STATIC-CODE',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
    ]);

    $service = app(DynamicBarcodeTokenService::class);
    $issuedAt = now();
    $firstToken = $service->generateTokenPayload($barcode, $issuedAt)['token'];
    $secondToken = $service->generateTokenPayload($barcode, $issuedAt)['token'];
    $decodePayload = function (string $token): array {
        [, $encodedPayload] = explode('.', $token, 3);
        $remainder = strlen($encodedPayload) % 4;

        if ($remainder > 0) {
            $encodedPayload .= str_repeat('=', 4 - $remainder);
        }

        return json_decode(base64_decode(strtr($encodedPayload, '-_', '+/')), true);
    };

    $firstPayload = $decodePayload($firstToken);
    $secondPayload = $decodePayload($secondToken);
    $firstNonce = $firstPayload['n'] ?? $firstPayload['nonce'] ?? null;
    $secondNonce = $secondPayload['n'] ?? $secondPayload['nonce'] ?? null;

    expect($firstToken)->not->toBe($secondToken)
        ->and($firstNonce)->not->toBe($secondNonce)
        ->and(strlen((string) $firstNonce))->toBeGreaterThanOrEqual(12)
        ->and(array_key_exists('code', $firstPayload))->toBeFalse()
        ->and(json_encode($firstPayload))->not->toContain($barcode->value)
        ->and($service->resolveScannedBarcode($secondToken)?->is($barcode))->toBeTrue();
});

test('new dynamic barcode token invalidates the previously displayed token', function () {
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-ROTATING',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
    ]);

    $service = app(DynamicBarcodeTokenService::class);
    $firstToken = $service->generateTokenPayload($barcode, now())['token'];

    expect($service->resolveScannedBarcode($firstToken)?->is($barcode))->toBeTrue();

    $secondToken = $service->generateTokenPayload($barcode, now()->addSecond())['token'];

    expect($service->resolveScannedBarcode($secondToken)?->is($barcode))->toBeTrue()
        ->and($service->resolveScannedBarcode($firstToken))->toBeNull();
});

test('expired dynamic barcode token is rejected', function () {
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-EXPIRED',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
    ]);

    $service = app(DynamicBarcodeTokenService::class);
    $issuedAt = now()->subSeconds(45);
    $token = $service->generateTokenPayload($barcode, $issuedAt)['token'];

    expect($service->resolveScannedBarcode($token))->toBeNull();
});

test('web dynamic barcode token is single use after successful scan', function () {
    Setting::query()->updateOrCreate(
        ['key' => 'feature.require_photo'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::query()->updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::query()->updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache();

    $user = User::factory()->create();
    $shift = Shift::factory()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-WEB-SINGLE-USE',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 5000,
    ]);
    $token = app(DynamicBarcodeTokenService::class)->generateTokenPayload($barcode, now())['token'];

    Livewire::actingAs($user)
        ->test(\App\Livewire\User\ScanComponent::class)
        ->set('shift_id', $shift->id)
        ->set('currentLiveCoords', [-6.2, 106.8])
        ->call('scan', $token, -6.2, 106.8)
        ->assertReturned(true);

    expect(Attendance::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(app(DynamicBarcodeTokenService::class)->resolveScannedBarcode($token))->toBeNull();
});

test('regenerating dynamic barcode secret invalidates previous token', function () {
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-003',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 45,
    ]);

    $service = app(DynamicBarcodeTokenService::class);
    $token = $service->generateTokenPayload($barcode, now())['token'];

    expect($service->resolveScannedBarcode($token)?->is($barcode))->toBeTrue();

    $barcode->update([
        'secret_key' => Str::random(64),
    ]);

    expect($service->resolveScannedBarcode($token))->toBeNull();
});

test('admin can open dynamic barcode display page', function () {
    $admin = User::factory()->admin()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-004',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 60,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.barcodes.dynamic-display', $barcode));

    $response
        ->assertOk()
        ->assertSee(__('Dynamic barcode display'))
        ->assertSee('Fullscreen')
        ->assertDontSee('Expires in')
        ->assertDontSee('Last refresh')
        ->assertDontSee('TTL');
});

test('admin can open dynamic barcode edit page without regex compilation errors', function () {
    $admin = User::factory()->admin()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-EDIT-001',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 60,
    ]);

    $this
        ->actingAs($admin)
        ->get(route('admin.barcodes.edit', $barcode))
        ->assertOk()
        ->assertSee(__('Regenerate Secret'));
});

test('admin can fetch dynamic barcode token payload', function () {
    $admin = User::factory()->admin()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-005',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 60,
    ]);

    $response = $this
        ->actingAs($admin)
        ->getJson(route('admin.barcodes.dynamic-token', $barcode));

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('barcode.id', $barcode->id)
        ->assertJsonStructure([
            'success',
            'barcode' => ['id', 'name', 'radius'],
            'data' => ['token', 'issued_at', 'expires_at', 'ttl_seconds', 'configured_ttl_seconds', 'grace_seconds', 'refresh_in_seconds'],
        ]);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('admin barcode validation rejects invalid coordinates and radius', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.barcodes.create'))
        ->post(route('admin.barcodes.store'), [
            'name' => 'Invalid Coordinate',
            'value' => 'STATIC-INVALID-COORDINATE',
            'lat' => 91,
            'lng' => 181,
            'radius' => 0,
            'dynamic_ttl_seconds' => 60,
        ]);

    $response
        ->assertRedirect(route('admin.barcodes.create'))
        ->assertSessionHasErrors(['lat', 'lng', 'radius']);
});

test('admin static barcode download uses valid octet stream content type', function () {
    $admin = User::factory()->admin()->create();
    $barcode = Barcode::factory()->create([
        'dynamic_enabled' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.barcodes.download', $barcode->id));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/octet-stream');
});

test('dynamic barcode download route redirects admin back to edit page', function () {
    $admin = User::factory()->admin()->create();
    $barcode = Barcode::factory()->create([
        'value' => 'CHK-DYNAMIC-006',
        'secret_key' => Str::random(64),
        'dynamic_enabled' => true,
        'dynamic_ttl_seconds' => 60,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.barcodes.download', $barcode->id));

    $response
        ->assertRedirect(route('admin.barcodes.edit', $barcode))
        ->assertSessionHas('flash.bannerStyle', 'danger');
});

test('admin can create dynamic barcode without submitting static value', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.barcodes.store'), [
            'name' => 'Dynamic Lobby',
            'lat' => -6.2,
            'lng' => 106.8,
            'radius' => 50,
            'dynamic_enabled' => '1',
            'dynamic_ttl_seconds' => 60,
        ]);

    $response->assertRedirect(route('admin.barcodes'));

    $barcode = Barcode::query()->where('name', 'Dynamic Lobby')->firstOrFail();

    expect($barcode->dynamic_enabled)->toBeTrue()
        ->and($barcode->value)->toStartWith('BC-')
        ->and(strlen($barcode->value))->toBe(35);
});

test('admin must submit value when creating static barcode', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.barcodes.create'))
        ->post(route('admin.barcodes.store'), [
            'name' => 'Static Lobby',
            'lat' => -6.2,
            'lng' => 106.8,
            'radius' => 50,
            'dynamic_ttl_seconds' => 60,
        ]);

    $response
        ->assertRedirect(route('admin.barcodes.create'))
        ->assertSessionHasErrors('value');
});

test('web scan rejects check out from a different checkpoint without creating another attendance', function () {
    Setting::query()->updateOrCreate(
        ['key' => 'feature.require_photo'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::query()->updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::query()->updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache();

    $user = User::factory()->create();
    $shift = Shift::factory()->create([
        'start_time' => '07:00:00',
        'end_time' => '15:00:00',
    ]);
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

    Attendance::create([
        'user_id' => $user->id,
        'barcode_id' => $checkInBarcode->id,
        'shift_id' => $shift->id,
        'date' => now()->toDateString(),
        'time_in' => now()->subHours(8),
        'status' => 'present',
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\User\ScanComponent::class)
        ->set('shift_id', $shift->id)
        ->set('currentLiveCoords', [-6.2, 106.8])
        ->call('scan', $otherBarcode->value, -6.2, 106.8)
        ->assertReturned(__('Please scan the same checkpoint used for check in.'));

    expect(Attendance::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Attendance::first()->time_out)->toBeNull();
});
