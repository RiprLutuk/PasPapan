<?php

use App\Helpers\Editions;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\Enterprise\LicenseGuard;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    LicenseGuard::clearLicenseCache();
});

function seedEnterpriseSettings(string $company = 'PT. PasPapan Indonesia', string $support = 'support@example.com', string $licenseKey = ''): void
{
    Setting::updateOrCreate(
        ['key' => 'app.company_name'],
        ['value' => $company, 'group' => 'identity', 'type' => 'text', 'description' => 'Company Name']
    );

    Setting::updateOrCreate(
        ['key' => 'app.support_contact'],
        ['value' => $support, 'group' => 'identity', 'type' => 'text', 'description' => 'Support Contact']
    );

    Setting::updateOrCreate(
        ['key' => 'enterprise_license_key'],
        ['value' => $licenseKey, 'group' => 'enterprise', 'type' => 'textarea', 'description' => 'Enterprise License Key']
    );
}

function makeEnterpriseLicense(array $overrides = []): string
{
    return makeEnterpriseTestLicense($overrides);
}

it('normalizes company names when validating enterprise licenses', function () {
    seedEnterpriseSettings(company: 'PT. Pas Papan', support: 'support@example.com');

    $licenseKey = makeEnterpriseLicense([
        'client' => 'PasPapan',
        'support_contact' => 'support@example.com',
    ]);

    $result = LicenseGuard::validateDetailed($licenseKey, [
        'current_company' => 'PT. Pas Papan',
        'current_support_contact' => 'support@example.com',
        'user_count' => 1,
        'skip_remote_time' => true,
    ]);

    expect($result['valid'])->toBeTrue()
        ->and($result['code'])->toBe('valid')
        ->and($result['license']['client'])->toBe('PasPapan');
});

it('returns detailed validation reasons for invalid enterprise licenses', function () {
    seedEnterpriseSettings();

    $validKey = makeEnterpriseLicense();
    $signatureParts = explode('.', $validKey, 2);
    $tamperedSignature = ($signatureParts[1][0] === 'A' ? 'B' : 'A') . substr($signatureParts[1], 1);
    $invalidSignatureKey = $signatureParts[0] . '.' . $tamperedSignature;

    $cases = [
        'invalid_format' => LicenseGuard::validateDetailed('not-a-license'),
        'invalid_signature' => LicenseGuard::validateDetailed($invalidSignatureKey),
        'expired' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['expires_at' => '2020-01-01']),
            ['current_time' => '2026-01-01 00:00:00', 'user_count' => 1]
        ),
        'company_mismatch' => LicenseGuard::validateDetailed(
            $validKey,
            ['current_company' => 'Another Company', 'current_support_contact' => 'support@example.com', 'user_count' => 1]
        ),
        'support_contact_mismatch' => LicenseGuard::validateDetailed(
            $validKey,
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'ops@example.com', 'user_count' => 1]
        ),
        'domain_mismatch' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['domain' => 'licensed.example.com']),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'support@example.com', 'current_host' => 'app.local', 'user_count' => 1]
        ),
        'hwid_mismatch' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['hwid' => 'expected-hwid']),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'support@example.com', 'current_hwid' => 'actual-hwid', 'user_count' => 1]
        ),
        'max_users_exceeded' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['max_users' => 1]),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'support@example.com', 'user_count' => 2]
        ),
    ];

    foreach ($cases as $expectedCode => $result) {
        expect($result['valid'])->toBeFalse("Expected {$expectedCode} to be invalid")
            ->and($result['code'])->toBe($expectedCode);
    }
});

it('applies enterprise license from admin settings and refreshes validation state', function () {
    seedEnterpriseSettings();

    $superadmin = User::factory()->admin(true)->create();
    $this->actingAs($superadmin);

    $licenseKey = makeEnterpriseLicense(['max_users' => 10]);

    Cache::put('ent_lic_status', 'invalid');
    Cache::put('ent_lic_hash', 'stale-hash');

    Livewire::test(AdminSettings::class)
        ->set('enterpriseLicenseDraft', $licenseKey)
        ->call('applyEnterpriseLicense')
        ->assertSet('licenseValidation.valid', true)
        ->assertSet('licenseValidation.code', 'valid')
        ->assertSee('License active');

    expect(Setting::where('key', 'enterprise_license_key')->value('value'))->toBe($licenseKey)
        ->and(Cache::get('ent_lic_status'))->toBe('valid')
        ->and(Cache::get('ent_lic_hash'))->toBe(md5($licenseKey));
});

it('keeps enterprise license read only for non superadmin users', function () {
    seedEnterpriseSettings();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $licenseKey = makeEnterpriseLicense();

    Livewire::test(AdminSettings::class)
        ->set('enterpriseLicenseDraft', $licenseKey)
        ->call('applyEnterpriseLicense');

    expect(Setting::where('key', 'enterprise_license_key')->value('value'))->toBe('');
});

it('keeps hasValidLicense boolean compatible for editions callers', function () {
    seedEnterpriseSettings();

    User::factory()->admin(true)->create();

    $licenseKey = makeEnterpriseLicense(['max_users' => 10]);
    Setting::where('key', 'enterprise_license_key')->update(['value' => $licenseKey]);
    LicenseGuard::clearLicenseCache();

    expect(LicenseGuard::hasValidLicense())->toBeTrue()
        ->and(Editions::attendanceLocked())->toBeFalse()
        ->and(Editions::payrollLocked())->toBeFalse();
});
