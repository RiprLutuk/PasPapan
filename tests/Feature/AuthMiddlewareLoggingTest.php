<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

test('admin middleware omits raw email and roles unless auth debug logging is enabled', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin-log@example.test',
    ]);

    Config::set('auth.debug_log', false);
    Log::spy();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk();

    Log::shouldHaveReceived('info')
        ->with('AdminMiddleware checked request.', \Mockery::on(fn (array $context): bool => ($context['user_id'] ?? null) === $admin->id
            && ! array_key_exists('email', $context)
            && ! array_key_exists('roles', $context)))
        ->once();
});

test('admin middleware includes verbose identity fields when auth debug logging is enabled', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin-debug@example.test',
    ]);

    Config::set('auth.debug_log', true);
    Log::spy();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk();

    Log::shouldHaveReceived('info')
        ->with('AdminMiddleware checked request.', \Mockery::on(fn (array $context): bool => ($context['user_id'] ?? null) === $admin->id
            && ($context['email'] ?? null) === 'admin-debug@example.test'
            && array_key_exists('roles', $context)))
        ->once();
});
