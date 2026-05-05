<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

test('auth debug endpoint is unavailable when production debug is disabled', function () {
    Config::set('app.debug', false);
    app()->detectEnvironment(fn () => 'production');

    $this->actingAs(User::factory()->create())
        ->get('/__auth-debug')
        ->assertNotFound();
});

test('apk e2e login hook is local only and token protected', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    Config::set('services.e2e.login_token', 'test-token');

    $this->get('/__e2e-login?token=wrong-token&email='.$user->email)
        ->assertForbidden();

    $this->get('/__e2e-login?token=test-token&email='.$user->email.'&to=/document-requests')
        ->assertRedirect('/document-requests');

    $this->assertAuthenticatedAs($user);

    app()->detectEnvironment(fn () => 'production');

    $this->get('/__e2e-login?token=test-token&email='.$user->email)
        ->assertNotFound();

    app()->detectEnvironment(fn () => 'testing');
});

test('vercel migrate endpoint requires a token and is rate limited', function () {
    Config::set('services.vercel.maintenance_endpoint_enabled', true);
    Config::set('services.vercel.maintenance_token', 'expected-token');
    Log::spy();

    $this->post('/__vercel-migrate', ['token' => 'wrong-token'])
        ->assertNotFound();

    Log::shouldHaveReceived('warning')
        ->with('Vercel maintenance endpoint rejected.', \Mockery::on(function (array $context): bool {
            return ($context['reason'] ?? null) === 'invalid_token'
                && ! array_key_exists('token', $context)
                && array_key_exists('ip', $context)
                && array_key_exists('user_agent', $context);
        }))
        ->once();

    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === '__vercel-migrate' && in_array('POST', $route->methods(), true));

    expect($route?->gatherMiddleware())->toContain('throttle:3,1');
    expect($route?->methods())->toContain('POST')
        ->not->toContain('GET');
});

test('vercel migrate endpoint is disabled by default', function () {
    Config::set('services.vercel.maintenance_endpoint_enabled', false);
    Config::set('services.vercel.maintenance_token', 'expected-token');
    Log::spy();

    Artisan::shouldReceive('call')->never();

    $this->post('/__vercel-migrate', ['token' => 'expected-token'])
        ->assertNotFound();

    Log::shouldHaveReceived('warning')
        ->with('Vercel maintenance endpoint rejected.', \Mockery::on(fn (array $context): bool => ($context['reason'] ?? null) === 'endpoint_disabled'))
        ->once();
});

test('vercel migrate endpoint blocks web seeding unless explicitly allowed', function () {
    Config::set('services.vercel.maintenance_endpoint_enabled', true);
    Config::set('services.vercel.maintenance_token', 'expected-token');
    Config::set('services.vercel.allow_web_seed', false);
    Log::spy();

    Artisan::shouldReceive('call')->never();

    $this->post('/__vercel-migrate', ['token' => 'expected-token', 'seed' => true])
        ->assertForbidden();

    Log::shouldHaveReceived('warning')
        ->with('Vercel maintenance endpoint rejected.', \Mockery::on(fn (array $context): bool => ($context['reason'] ?? null) === 'seed_not_allowed'))
        ->once();
});

test('vercel migrate endpoint runs only with the configured token and does not echo it', function () {
    Config::set('services.vercel.maintenance_endpoint_enabled', true);
    Config::set('services.vercel.maintenance_token', 'expected-token');
    Log::spy();

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Migrated successfully.');

    $response = $this->post('/__vercel-migrate', ['token' => 'expected-token'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'migrate_exit_code' => 0,
            'seed_exit_code' => null,
            'migrate_output' => 'Migrated successfully.',
        ]);

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('expected-token')
        ->not->toContain('host')
        ->not->toContain('database');

    Log::shouldHaveReceived('info')
        ->with('Vercel maintenance migration started.', \Mockery::on(fn (array $context): bool => ($context['seed'] ?? null) === false && ! array_key_exists('token', $context)))
        ->once();

    Log::shouldHaveReceived('info')
        ->with('Vercel maintenance migration finished.', \Mockery::on(fn (array $context): bool => ($context['ok'] ?? null) === true && ($context['migrate_exit_code'] ?? null) === 0))
        ->once();
});

test('vercel migrate endpoint does not expose command output in production', function () {
    Config::set('services.vercel.maintenance_endpoint_enabled', true);
    Config::set('services.vercel.maintenance_token', 'expected-token');
    app()->detectEnvironment(fn () => 'production');

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Migrated sensitive production output.');

    $response = $this->post('/__vercel-migrate', ['token' => 'expected-token'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'migrate_exit_code' => 0,
            'seed_exit_code' => null,
        ]);

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('Migrated sensitive production output')
        ->not->toContain('host')
        ->not->toContain('database');

    app()->detectEnvironment(fn () => 'testing');
});
