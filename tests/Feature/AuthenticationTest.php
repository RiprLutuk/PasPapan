<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/home');
});

test('admin users can authenticate using the login screen', function () {
    $user = User::factory()->admin()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/admin/dashboard');
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('inactive lifecycle accounts cannot authenticate using the login screen', function (string $status) {
    $user = User::factory()->create([
        'employment_status' => $status,
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login');
})->with([
    User::EMPLOYMENT_STATUS_INACTIVE,
    User::EMPLOYMENT_STATUS_RESIGNED,
    User::EMPLOYMENT_STATUS_DELETED,
]);

test('unverified users are redirected to the email verification prompt after login', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    expect(Route::has('verification.notice'))->toBeTrue();
    $response->assertRedirect(route('verification.notice'));
});
