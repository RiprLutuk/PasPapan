<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

test('email verification screen can be rendered', function () {
    enableFortifyEmailVerificationForTests();

    $user = User::factory()->withPersonalTeam()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/email/verify');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    enableFortifyEmailVerificationForTests();

    Event::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(config('fortify.home').'?verified=1');
});

test('email can be verified with a verification code', function () {
    enableFortifyEmailVerificationForTests();

    Event::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
        'email_verification_code_hash' => Hash::make('123456'),
        'email_verification_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)->post('/email/verify-code', [
        'code' => '123456',
    ]);

    Event::assertDispatched(Verified::class);

    $user->refresh();

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->email_verification_code_hash)->toBeNull()
        ->and($user->email_verification_code_expires_at)->toBeNull();

    $response->assertRedirect('/');
});

test('email verification rejects invalid verification code', function () {
    enableFortifyEmailVerificationForTests();

    Event::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
        'email_verification_code_hash' => Hash::make('123456'),
        'email_verification_code_expires_at' => now()->addMinutes(15),
    ]);

    $this->actingAs($user)->post('/email/verify-code', [
        'code' => '654321',
    ])->assertSessionHasErrors('code');

    Event::assertNotDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email verification link clicked before login is completed after login', function () {
    enableFortifyEmailVerificationForTests();

    Event::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($verificationUrl)->assertRedirect('/login');

    $loginResponse = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertRedirect($verificationUrl);

    $response = $this->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(config('fortify.home').'?verified=1');
});

test('email can not verified with invalid hash', function () {
    enableFortifyEmailVerificationForTests();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
