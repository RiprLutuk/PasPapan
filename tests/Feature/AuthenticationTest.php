<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('authenticated users opening login are redirected to their permitted home', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect('/home');

    $this->actingAs($admin)
        ->get('/login')
        ->assertRedirect('/admin/dashboard');
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

test('login redirects to the current users permitted home instead of a stale intended url', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');

    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/home');
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
