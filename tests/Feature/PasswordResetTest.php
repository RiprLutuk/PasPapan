<?php

use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, QueuedResetPassword::class);
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function (object $notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function (object $notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        return true;
    });
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');

test('password reset sends email verification notification for unverified accounts', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function (object $notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        return true;
    });

    Notification::assertSentTo($user, QueuedVerifyEmail::class);

    expect($user->fresh()->email_verification_code_hash)->not->toBeNull()
        ->and($user->fresh()->email_verification_code_expires_at)->not->toBeNull();
})->skip(function () {
    return ! Features::enabled(Features::resetPasswords());
}, 'Password updates are not enabled.');
