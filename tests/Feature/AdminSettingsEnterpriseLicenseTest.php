<?php

use App\Console\Commands\EnterpriseHwId;
use App\Contracts\AuditServiceInterface;
use App\Helpers\Editions;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\SettingsManagementService;
use App\Services\Audit\CommunityAuditService;
use App\Services\Enterprise\LicenseGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    LicenseGuard::clearLicenseCache();
});

function seedEnterpriseSettings(string $company = 'PT. PasPapan Indonesia', string $support = 'https://t.me/RiprLutuk', string $licenseKey = ''): void
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
    seedEnterpriseSettings(company: 'PT. Pas Papan', support: 'https://t.me/RiprLutuk');

    $licenseKey = makeEnterpriseLicense([
        'client' => 'PasPapan',
        'support_contact' => '@riprlutuk',
    ]);

    $result = LicenseGuard::validateDetailed($licenseKey, [
        'current_company' => 'PT. Pas Papan',
        'current_support_contact' => 'https://t.me/RiprLutuk',
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
    $tamperedSignature = ($signatureParts[1][0] === 'A' ? 'B' : 'A').substr($signatureParts[1], 1);
    $invalidSignatureKey = $signatureParts[0].'.'.$tamperedSignature;

    $cases = [
        'invalid_format' => LicenseGuard::validateDetailed('not-a-license'),
        'invalid_signature' => LicenseGuard::validateDetailed($invalidSignatureKey),
        'expired' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['expires_at' => '2020-01-01']),
            ['current_time' => '2026-01-01 00:00:00', 'user_count' => 1]
        ),
        'company_mismatch' => LicenseGuard::validateDetailed(
            $validKey,
            ['current_company' => 'Another Company', 'current_support_contact' => 'https://t.me/RiprLutuk', 'user_count' => 1]
        ),
        'support_contact_mismatch' => LicenseGuard::validateDetailed(
            $validKey,
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => '@other_support', 'user_count' => 1]
        ),
        'domain_mismatch' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['domain' => 'licensed.example.com']),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'https://t.me/RiprLutuk', 'current_host' => 'app.local', 'user_count' => 1]
        ),
        'hwid_mismatch' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['hwid' => 'expected-hwid']),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'https://t.me/RiprLutuk', 'current_hwid' => 'actual-hwid', 'user_count' => 1]
        ),
        'max_users_exceeded' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['max_users' => 1]),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'https://t.me/RiprLutuk', 'user_count' => 2]
        ),
        'not_yet_valid' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['not_before' => '2026-01-02T00:00:00+00:00']),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'https://t.me/RiprLutuk', 'current_time' => '2026-01-01 00:00:00', 'user_count' => 1]
        ),
        'unsupported_schema' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['schema_version' => 999]),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'https://t.me/RiprLutuk', 'user_count' => 1]
        ),
        'invalid_payload' => LicenseGuard::validateDetailed(
            makeEnterpriseLicense(['features' => []]),
            ['current_company' => 'PT. PasPapan Indonesia', 'current_support_contact' => 'https://t.me/RiprLutuk', 'user_count' => 1]
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
        ->assertSee(__('License active'));

    expect(Setting::where('key', 'enterprise_license_key')->value('value'))->toBe($licenseKey)
        ->and(Cache::get('ent_lic_status'))->toBe('valid')
        ->and(Cache::get('ent_lic_hash'))->toBe(LicenseGuard::cacheFingerprint($licenseKey));
});

it('shows the validation message when an applied enterprise license is invalid', function () {
    seedEnterpriseSettings();

    $superadmin = User::factory()->admin(true)->create();
    $this->actingAs($superadmin);

    Livewire::test(AdminSettings::class)
        ->set('enterpriseLicenseDraft', 'not-a-license')
        ->call('applyEnterpriseLicense')
        ->assertSet('licenseValidation.valid', false)
        ->assertSet('licenseValidation.code', 'invalid_format')
        ->assertSee('The enterprise license could not be validated on this server.');
});

it('falls back to community audit service when enterprise audit runtime cannot be decrypted', function () {
    seedEnterpriseSettings();

    Setting::where('key', 'enterprise_license_key')->update([
        'value' => makeEnterpriseLicense(['features' => ['audit']]),
    ]);
    LicenseGuard::clearLicenseCache();

    $previousEnv = getenv('ENTERPRISE_OBFUSCATOR_KEY');
    $previousServer = $_SERVER['ENTERPRISE_OBFUSCATOR_KEY'] ?? null;
    $previousGlobalSecret = $GLOBALS['__enterprise_obfuscator_secret_ENTERPRISE_OBFUSCATOR_KEY'] ?? null;

    try {
        putenv('ENTERPRISE_OBFUSCATOR_KEY='.str_repeat('x', 32));
        $_ENV['ENTERPRISE_OBFUSCATOR_KEY'] = str_repeat('x', 32);
        $_SERVER['ENTERPRISE_OBFUSCATOR_KEY'] = str_repeat('x', 32);
        unset($GLOBALS['__enterprise_obfuscator_secret_ENTERPRISE_OBFUSCATOR_KEY']);

        expect(app(AuditServiceInterface::class))->toBeInstanceOf(CommunityAuditService::class);
    } finally {
        if ($previousEnv === false) {
            putenv('ENTERPRISE_OBFUSCATOR_KEY');
            unset($_ENV['ENTERPRISE_OBFUSCATOR_KEY']);
        } else {
            putenv('ENTERPRISE_OBFUSCATOR_KEY='.$previousEnv);
            $_ENV['ENTERPRISE_OBFUSCATOR_KEY'] = $previousEnv;
        }

        if ($previousServer === null) {
            unset($_SERVER['ENTERPRISE_OBFUSCATOR_KEY']);
        } else {
            $_SERVER['ENTERPRISE_OBFUSCATOR_KEY'] = $previousServer;
        }

        if ($previousGlobalSecret === null) {
            unset($GLOBALS['__enterprise_obfuscator_secret_ENTERPRISE_OBFUSCATOR_KEY']);
        } else {
            $GLOBALS['__enterprise_obfuscator_secret_ENTERPRISE_OBFUSCATOR_KEY'] = $previousGlobalSecret;
        }
    }
});

it('invalidates cached enterprise status when the licensed company context changes', function () {
    seedEnterpriseSettings();

    User::factory()->admin(true)->create();

    $licenseKey = makeEnterpriseLicense(['max_users' => 10]);
    Setting::query()->where('key', 'enterprise_license_key')->firstOrFail()->update(['value' => $licenseKey]);
    LicenseGuard::clearLicenseCache();

    expect(LicenseGuard::hasValidLicense())->toBeTrue()
        ->and(Cache::get('ent_lic_status'))->toBe('valid');

    Setting::query()->where('key', 'app.company_name')->firstOrFail()->update(['value' => 'PT. Changed Company']);

    expect(LicenseGuard::hasValidLicense())->toBeFalse()
        ->and(Cache::get('ent_lic_status'))->toBe('invalid')
        ->and(Cache::get('ent_lic_result')['code'] ?? null)->toBe('company_mismatch');
});

it('shows settings groups that have stored settings instead of leaving them unreachable', function () {
    seedEnterpriseSettings();
    Setting::updateOrCreate(
        ['key' => 'payroll.country'],
        ['value' => 'ID', 'group' => 'payroll', 'type' => 'text', 'description' => 'Payroll localization country code']
    );
    Setting::updateOrCreate(
        ['key' => 'appraisal.attendance_weight'],
        ['value' => '30', 'group' => 'appraisal', 'type' => 'number', 'description' => 'Bobot Skor Absensi dalam Penilaian Appraisal (%)']
    );
    Setting::updateOrCreate(
        ['key' => 'appraisal.period_label'],
        ['value' => 'Q1 2026', 'group' => 'Appraisal', 'type' => 'text', 'description' => 'Appraisal period label']
    );

    $superadmin = User::factory()->admin(true)->create();
    $this->actingAs($superadmin);

    Livewire::test(AdminSettings::class)
        ->assertSee('payroll.country')
        ->assertSee('appraisal.attendance_weight')
        ->assertSee('appraisal.period_label')
        ->assertSee(__('Payroll'))
        ->assertSee(__('Appraisal'));
});

it('keeps core app settings visible when the settings table is incomplete', function () {
    Setting::query()->whereIn('key', [
        'app.name',
        'app.company_name',
        'app.company_address',
        'app.support_contact',
        'app.time_format',
        'app.show_seconds',
        'feature.require_photo',
    ])->delete();

    $superadmin = User::factory()->admin(true)->create();
    $this->actingAs($superadmin);

    Livewire::test(AdminSettings::class)
        ->assertSee(__('General'))
        ->assertSee('app.name')
        ->assertSee('app.company_name')
        ->assertSee('app.company_address')
        ->assertSee('app.support_contact')
        ->assertSee('app.time_format')
        ->assertSee('feature.require_photo');
});

it('normalizes legacy enterprise license setting metadata without clearing the license', function () {
    Setting::query()->updateOrCreate(
        ['key' => 'enterprise_license_key'],
        [
            'value' => 'saved-enterprise-license',
            'group' => 'system',
            'type' => 'text',
            'description' => 'Legacy Enterprise License Key',
        ],
    );

    app(SettingsManagementService::class)->enterpriseLicenseState();

    $setting = Setting::query()->where('key', 'enterprise_license_key')->firstOrFail();

    expect($setting->value)->toBe('saved-enterprise-license')
        ->and($setting->group)->toBe('enterprise')
        ->and($setting->type)->toBe('textarea')
        ->and($setting->description)->toBe('Enterprise License Key');
});

it('allows optional env enterprise license key when the stored setting is blank', function () {
    seedEnterpriseSettings(licenseKey: '');
    User::factory()->admin(true)->create();

    $licenseKey = makeEnterpriseLicense(['max_users' => 10]);
    Config::set('app.enterprise_license_key', $licenseKey);
    LicenseGuard::clearLicenseCache();

    expect(LicenseGuard::hasValidLicense())->toBeTrue();

    app(SettingsManagementService::class)->enterpriseLicenseState();

    expect(Setting::query()->where('key', 'enterprise_license_key')->value('value'))->toBe($licenseKey);
});

it('shows the server hardware id on the enterprise settings tab', function () {
    seedEnterpriseSettings();

    $superadmin = User::factory()->admin(true)->create();
    $this->actingAs($superadmin);

    Livewire::test(AdminSettings::class)
        ->assertSee('Hardware ID (HWID)')
        ->assertSee(EnterpriseHwId::generate());
});

it('keeps enterprise license read only for non superadmin users', function () {
    seedEnterpriseSettings();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $licenseKey = makeEnterpriseLicense();

    Livewire::test(AdminSettings::class)
        ->set('enterpriseLicenseDraft', $licenseKey)
        ->call('applyEnterpriseLicense')
        ->assertForbidden();

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

it('locks enterprise features that are not present in the license payload', function () {
    seedEnterpriseSettings();

    Setting::where('key', 'enterprise_license_key')->update([
        'value' => makeEnterpriseLicense(['features' => ['payroll']]),
    ]);
    LicenseGuard::clearLicenseCache();

    expect(LicenseGuard::hasValidLicense())->toBeTrue()
        ->and(LicenseGuard::hasFeature('payroll'))->toBeTrue()
        ->and(LicenseGuard::hasFeature('audit'))->toBeFalse()
        ->and(Editions::payrollLocked())->toBeFalse()
        ->and(Editions::auditLocked())->toBeTrue();
});

it('treats signed addons as separate premium entitlements', function () {
    seedEnterpriseSettings();

    Setting::where('key', 'enterprise_license_key')->update([
        'value' => makeEnterpriseLicense([
            'features' => ['payroll'],
            'addons' => ['toko_pos'],
        ]),
    ]);
    LicenseGuard::clearLicenseCache();

    expect(LicenseGuard::hasValidLicense())->toBeTrue()
        ->and(LicenseGuard::hasFeature('payroll'))->toBeTrue()
        ->and(LicenseGuard::hasFeature('toko_pos'))->toBeTrue()
        ->and(LicenseGuard::hasAddon('toko_pos'))->toBeTrue()
        ->and(LicenseGuard::hasFeature('audit'))->toBeFalse();
});
