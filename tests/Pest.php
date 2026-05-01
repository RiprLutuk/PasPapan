<?php

use App\Models\Setting;
use App\Support\ApiTokenPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features as FortifyFeatures;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Fortify\RoutePath;
use Laravel\Jetstream\Features as JetstreamFeatures;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Unit');
uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function deviceApiAbilities(): array
{
    return ApiTokenPermission::deviceApi();
}

function enableJetstreamApiFeaturesForTests(): void
{
    $features = config('jetstream.features', []);

    if (! in_array(JetstreamFeatures::api(), $features, true)) {
        $features[] = JetstreamFeatures::api();
    }

    Config::set('jetstream.features', $features);
}

function enableFortifyEmailVerificationForTests(): void
{
    $features = config('fortify.features', []);

    if (! in_array(FortifyFeatures::emailVerification(), $features, true)) {
        $features[] = FortifyFeatures::emailVerification();
    }

    Config::set('fortify.features', $features);

    if (! Route::has('verification.notice')) {
        $verificationLimiter = config('fortify.limiters.verification', '6,1');

        Route::get(RoutePath::for('verification.notice', '/email/verify'), [EmailVerificationPromptController::class, '__invoke'])
            ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])
            ->name('verification.notice');

        Route::get(RoutePath::for('verification.verify', '/email/verify/{id}/{hash}'), [VerifyEmailController::class, '__invoke'])
            ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard'), 'signed', 'throttle:'.$verificationLimiter])
            ->name('verification.verify');

        Route::post(RoutePath::for('verification.send', '/email/verification-notification'), [EmailVerificationNotificationController::class, 'store'])
            ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard'), 'throttle:'.$verificationLimiter])
            ->name('verification.send');

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }
}

function getEnterpriseTestPrivateKey(): ?string
{
    $inlineKey = env('TEST_ENTERPRISE_LICENSE_PRIVATE_KEY');

    if (is_string($inlineKey) && trim($inlineKey) !== '') {
        return str_replace('\n', PHP_EOL, trim($inlineKey));
    }

    $configuredPath = env('TEST_ENTERPRISE_LICENSE_PRIVATE_KEY_PATH');
    $candidatePaths = [];

    if (is_string($configuredPath) && trim($configuredPath) !== '') {
        $candidatePaths[] = trim($configuredPath);
    }

    $candidatePaths[] = storage_path('license_test_private.key');
    $candidatePaths[] = storage_path('license_private.key');

    foreach ($candidatePaths as $path) {
        $resolvedPath = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);

        if (! is_file($resolvedPath)) {
            continue;
        }

        $key = file_get_contents($resolvedPath);

        if (is_string($key) && trim($key) !== '') {
            return trim($key);
        }
    }

    return null;
}

function requireEnterpriseTestPrivateKey(): string
{
    $key = getEnterpriseTestPrivateKey();

    if ($key === null) {
        throw new \PHPUnit\Framework\SkippedWithMessageException(
            'Enterprise license tests need TEST_ENTERPRISE_LICENSE_PRIVATE_KEY, TEST_ENTERPRISE_LICENSE_PRIVATE_KEY_PATH, or storage/license_test_private.key.'
        );
    }

    return $key;
}

function enterpriseTestFeatures(): array
{
    return [
        'attendance',
        'face_verification',
        'payroll',
        'cash_advance',
        'reporting',
        'audit',
        'analytics',
        'asset_management',
        'appraisal',
        'system_backup',
    ];
}

function makeEnterpriseTestLicense(array $overrides = []): string
{
    $payload = array_merge([
        'schema_version' => 1,
        'license_id' => 'LIC-'.strtoupper(bin2hex(random_bytes(8))),
        'client' => 'PT. PasPapan Indonesia',
        'support_contact' => 'https://t.me/RiprLutuk',
        'domain' => '*',
        'hwid' => '*',
        'expires_at' => now()->addYear()->toDateString(),
        'issued_at' => now()->toIso8601String(),
        'not_before' => now()->subMinutes(5)->toIso8601String(),
        'features' => enterpriseTestFeatures(),
        'max_users' => 0,
        'author' => 'RiprLutuk(https://riprlutuk.github.io)',
        'salt' => bin2hex(random_bytes(16)),
    ], $overrides);

    $json = json_encode($payload, JSON_THROW_ON_ERROR);
    openssl_sign($json, $signature, requireEnterpriseTestPrivateKey(), OPENSSL_ALGO_SHA256);

    return base64_encode($json).'.'.base64_encode($signature);
}

function enableEnterpriseAttendanceForTests(): void
{
    Setting::updateOrCreate(
        ['key' => 'app.company_name'],
        ['value' => 'PT. PasPapan Indonesia', 'group' => 'identity', 'type' => 'text']
    );
    Setting::updateOrCreate(
        ['key' => 'app.support_contact'],
        ['value' => 'https://t.me/RiprLutuk', 'group' => 'identity', 'type' => 'text']
    );

    Setting::updateOrCreate(
        ['key' => 'enterprise_license_key'],
        [
            'value' => makeEnterpriseTestLicense(),
            'group' => 'enterprise',
            'type' => 'textarea',
        ]
    );

    Setting::flushCache('app.company_name');
    Setting::flushCache('app.support_contact');
    Setting::flushCache('enterprise_license_key');
    \App\Services\Enterprise\LicenseGuard::clearLicenseCache();
}
