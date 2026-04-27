<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('users cannot log in while another active session exists', function () {
    config()->set('session.driver', 'database');

    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Existing Device',
        'payload' => 'test',
        'last_activity' => now()->getTimestamp(),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    expect(DB::table('sessions')->where('user_id', $user->getKey())->count())->toBe(1);
});

test('expired sessions do not block a new login', function () {
    config()->set('session.driver', 'database');

    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => (string) Str::uuid(),
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Expired Device',
        'payload' => 'test',
        'last_activity' => now()->subMinutes(config('session.lifetime') + 5)->getTimestamp(),
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
});
