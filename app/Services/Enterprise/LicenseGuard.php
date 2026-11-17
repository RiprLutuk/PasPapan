<?php

namespace App\Services\Enterprise;

use App\Console\Commands\EnterpriseHwId;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class LicenseGuard
{
    /**
     * Ed25519 public verification key (32 bytes, base64). Rotated 2026-06:
     * the license signing scheme moved from RSA-4096/openssl_verify to
     * Ed25519/libsodium detached signatures. Licenses issued under the old
     * RSA key no longer validate and must be re-issued.
     */
    private const PUB = 'h4dLYg2nvK9Yk99cVX8ZzzMeO7Rs/cezymUvguR1anQ=';

    private const CACHE_STATUS_KEY = 'ent_lic_status';

    private const CACHE_HASH_KEY = 'ent_lic_hash';

    private const CACHE_RESULT_KEY = 'ent_lic_result';

    private static ?string $requestLicenseHash = null;

    private static ?array $requestValidationResult = null;

    private static ?array $requestFeatureMap = null;

    private static ?array $requestAddonMap = null;

    public static function check()
    {
        if (! self::hasValidLicense()) {
            abort(403, 'Invalid Enterprise License Key');
        }

        return true;
    }

    public static function hasValidLicense()
    {
        $licenseKey = self::configuredLicenseKey();
        if (trim($licenseKey) === '') {
            return false;
        }

        $currentHash = self::licenseHash($licenseKey);
        $cachedHash = Cache::get(self::CACHE_HASH_KEY);

        if ($cachedHash !== null && $cachedHash !== $currentHash) {
            self::clearLicenseCache();
        }

        $cached = Cache::get(self::CACHE_STATUS_KEY);
        if ($cached === 'valid') {
            return true;
        }

        if ($cached === 'invalid') {
            return false;
        }

        $result = self::cachedValidationResult();

        $cacheUntil = $result['valid'] ? self::validCacheExpiration($result) : now()->addMinutes(5);

        Cache::put(self::CACHE_STATUS_KEY, $result['valid'] ? 'valid' : 'invalid', $cacheUntil);
        Cache::put(self::CACHE_HASH_KEY, $currentHash, $cacheUntil);
        Cache::put(self::CACHE_RESULT_KEY, $result, $cacheUntil);

        return $result['valid'];
    }

    public static function clearLicenseCache()
    {
        Cache::forget(self::CACHE_STATUS_KEY);
        Cache::forget(self::CACHE_HASH_KEY);
        Cache::forget(self::CACHE_RESULT_KEY);

        self::$requestLicenseHash = null;
        self::$requestValidationResult = null;
        self::$requestFeatureMap = null;
        self::$requestAddonMap = null;
    }

    public static function cacheFingerprint(?string $rawKey = null, array $context = []): string
    {
        $licenseKey = self::configuredLicenseKey($rawKey);

        return self::licenseHash($licenseKey, $context);
    }

    public static function validateDetailed(?string $rawKey = null, array $context = []): array
    {
        $licenseKey = self::configuredLicenseKey($rawKey);

        if ($licenseKey === '') {
            return self::result(
                valid: false,
                code: 'missing_key',
                message: self::publicMessageFor('missing_key'),
            );
        }

        try {
            $parts = explode('.', $licenseKey);
            if (count($parts) !== 2) {
                return self::fail('invalid_format', 'License key format is invalid.', [], 'LicenseGuard: Invalid license format (not 2 parts).');
            }

            $payload = base64_decode(trim($parts[0]), true);
            $signature = base64_decode(trim($parts[1]), true);

            if ($payload === false || $signature === false) {
                return self::fail('invalid_format', 'License key format is invalid.', [], 'LicenseGuard: Invalid base64 payload or signature.');
            }

            $publicKey = self::verificationPublicKey();

            if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                return self::fail('invalid_signature', 'License signature verification failed.', [], 'LicenseGuard: Public key is misconfigured.');
            }

            if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
                || ! sodium_crypto_sign_verify_detached($signature, $payload, $publicKey)) {
                return self::fail('invalid_signature', 'License signature verification failed.', [], 'LicenseGuard: Signature verification failed.');
            }

            $license = json_decode($payload, true);
            if (! is_array($license)) {
                return self::fail('invalid_payload', 'License payload could not be read.', [], 'LicenseGuard: Payload is not valid JSON.');
            }

            $schemaCheck = self::validateSchema($license);
            if ($schemaCheck !== null) {
                return $schemaCheck;
            }

            if (($license['author'] ?? '') !== 'RiprLutuk(https://riprlutuk.github.io)') {
                return self::fail(
                    'invalid_author',
                    'License author does not match the expected issuer.',
                    ['author' => $license['author'] ?? ''],
                    'LicenseGuard: Author mismatch.',
                    ['author' => $license['author'] ?? '']
                );
            }

            $domainCheck = self::validateDomain($license, $context);
            if ($domainCheck !== null) {
                return $domainCheck;
            }

            $hwidCheck = self::validateHardwareId($license, $context);
            if ($hwidCheck !== null) {
                return $hwidCheck;
            }

            $expiryCheck = self::validateExpiry($license, $context);
            if ($expiryCheck !== null) {
                return $expiryCheck;
            }

            $notBeforeCheck = self::validateNotBefore($license, $context);
            if ($notBeforeCheck !== null) {
                return $notBeforeCheck;
            }

            $companyCheck = self::validateCompany($license, $context);
            if ($companyCheck !== null) {
                return $companyCheck;
            }

            $contactCheck = self::validateSupportContact($license, $context);
            if ($contactCheck !== null) {
                return $contactCheck;
            }

            $userCountCheck = self::validateUserCount($license, $context);
            if ($userCountCheck !== null) {
                return $userCountCheck;
            }

            return self::result(
                valid: true,
                code: 'valid',
                message: self::publicMessageFor('valid'),
                details: [
                    'current_company' => self::currentCompany($context),
                    'current_support_contact' => self::currentSupportContact($context),
                    'current_users' => self::currentUserCount($context),
                    'current_host' => self::currentHost($context),
                    'current_hwid' => self::currentHardwareId($context),
                ],
                license: $license,
            );
        } catch (\Throwable $e) {
            Log::error('LicenseGuard: Exception during validation.', ['error' => $e->getMessage()]);

            return self::result(
                valid: false,
                code: 'exception',
                message: self::publicMessageFor('exception'),
                details: [],
            );
        }
    }

    public static function getLicenseInfo()
    {
        $result = self::cachedValidationResult();

        return $result['valid'] ? $result['license'] : null;
    }

    public static function hasFeature(string $feature, array $context = []): bool
    {
        if (! self::hasRuntimeObfuscatorKey()) {
            return false;
        }

        $feature = self::normalizeFeatureKey($feature);
        if ($feature === '') {
            return false;
        }

        $result = $context === [] ? self::cachedValidationResult() : self::validateDetailed(null, $context);
        if (! ($result['valid'] ?? false)) {
            return false;
        }

        $features = $context === [] ? self::cachedFeatureMap($result) : self::featureMapFromResult($result);

        return isset($features['*']) || isset($features[$feature]);
    }

    public static function hasAnyFeature(array $features, array $context = []): bool
    {
        foreach ($features as $feature) {
            if (self::hasFeature((string) $feature, $context)) {
                return true;
            }
        }

        return false;
    }

    public static function hasAddon(string $addon, array $context = []): bool
    {
        if (! self::hasRuntimeObfuscatorKey()) {
            return false;
        }

        $addon = self::normalizeFeatureKey($addon);
        if ($addon === '') {
            return false;
        }

        $result = $context === [] ? self::cachedValidationResult() : self::validateDetailed(null, $context);
        if (! ($result['valid'] ?? false)) {
            return false;
        }

        $addons = $context === [] ? self::cachedAddonMap($result) : self::addonMapFromResult($result);

        return isset($addons['*']) || isset($addons[$addon]);
    }

    private static function cachedValidationResult(): array
    {
        $licenseKey = self::configuredLicenseKey();
        if ($licenseKey === '') {
            return self::validateDetailed('');
        }

        $currentHash = self::licenseHash($licenseKey);
        if (self::$requestLicenseHash === $currentHash && self::$requestValidationResult !== null) {
            return self::$requestValidationResult;
        }

        $cachedHash = Cache::get(self::CACHE_HASH_KEY);
        if ($cachedHash !== null && $cachedHash !== $currentHash) {
            self::clearLicenseCache();
        } elseif ($cachedHash === $currentHash) {
            $cachedResult = Cache::get(self::CACHE_RESULT_KEY);
            if (is_array($cachedResult)) {
                self::$requestLicenseHash = $currentHash;
                self::$requestValidationResult = $cachedResult;
                self::$requestFeatureMap = null;

                return $cachedResult;
            }
        }

        $result = self::validateDetailed($licenseKey);
        $cacheUntil = ($result['valid'] ?? false) ? self::validCacheExpiration($result) : now()->addMinutes(5);

        Cache::put(self::CACHE_STATUS_KEY, ($result['valid'] ?? false) ? 'valid' : 'invalid', $cacheUntil);
        Cache::put(self::CACHE_HASH_KEY, $currentHash, $cacheUntil);
        Cache::put(self::CACHE_RESULT_KEY, $result, $cacheUntil);

        self::$requestLicenseHash = $currentHash;
        self::$requestValidationResult = $result;
        self::$requestFeatureMap = null;

        return $result;
    }

    private static function configuredLicenseKey(?string $rawKey = null): string
    {
        if ($rawKey !== null) {
            return trim($rawKey);
        }

        $settingKey = trim((string) Setting::getValue('enterprise_license_key', ''));

        return $settingKey !== ''
            ? $settingKey
            : trim((string) config('app.enterprise_license_key', ''));
    }

    /**
     * Keep the production verification key immutable. Tests may supply their own
     * ephemeral keypair so fixtures can exercise real Ed25519 verification without
     * storing a signing secret in the repository.
     */
    private static function verificationPublicKey(): string|false
    {
        $encoded = self::PUB;

        if (app()->environment('testing')) {
            $candidate = trim((string) env('TEST_ENTERPRISE_LICENSE_PUBLIC_KEY', ''));
            if ($candidate !== '') {
                $encoded = $candidate;
            }
        }

        return base64_decode($encoded, true);
    }

    public static function hasRuntimeObfuscatorKey(?string $addon = null): bool
    {
        if (app()->environment('testing')) {
            if (! self::hasRuntimeEnvironmentSecret('TEST_ENTERPRISE_OBFUSCATOR_KEY', false)) {
                return false;
            }

            return $addon === null || self::hasRuntimeAddonSalt($addon, true);
        }

        return self::hasRuntimeSourceKey($addon);
    }

    public static function hasRuntimeSourceKey(?string $addon = null): bool
    {
        if (! self::hasRuntimeEnvironmentSecret('ENTERPRISE_OBFUSCATOR_KEY')) {
            return false;
        }

        return $addon === null || self::hasRuntimeAddonSalt($addon);
    }

    public static function clearRuntimeSourceSecretCache(?string $addon = null): void
    {
        unset($GLOBALS['__enterprise_obfuscator_secret_ENTERPRISE_OBFUSCATOR_KEY']);

        if ($addon !== null) {
            unset($GLOBALS['__enterprise_obfuscator_secret_'.self::runtimeAddonSaltEnvName($addon)]);
        }
    }

    public static function runtimeSourceSecretCacheMatches(?string $addon = null): bool
    {
        if (! self::cachedRuntimeEnvironmentSecretMatches('ENTERPRISE_OBFUSCATOR_KEY')) {
            return false;
        }

        return $addon === null || self::cachedRuntimeEnvironmentSecretMatches(self::runtimeAddonSaltEnvName($addon));
    }

    private static function cachedRuntimeEnvironmentSecretMatches(string $envName): bool
    {
        $expected = self::runtimeEnvironmentValue($envName);
        $cached = $GLOBALS['__enterprise_obfuscator_secret_'.$envName] ?? null;

        return is_string($expected)
            && trim($expected) !== ''
            && is_string($cached)
            && trim($cached) !== ''
            && hash_equals(trim($expected), trim($cached));
    }

    private static function hasRuntimeEnvironmentSecret(string $envName, bool $includeDotEnv = true): bool
    {
        $value = self::runtimeEnvironmentValue($envName, $includeDotEnv);

        return is_string($value) && strlen(trim($value)) >= 32;
    }

    private static function runtimeEnvironmentValue(string $envName, bool $includeDotEnv = true): ?string
    {
        $value = getenv($envName) ?: ($_ENV[$envName] ?? $_SERVER[$envName] ?? null);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (! $includeDotEnv) {
            return null;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            return null;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($lines)) {
            return null;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$lineName, $rawValue] = explode('=', $line, 2);
            if (trim($lineName) !== $envName) {
                continue;
            }

            $rawValue = trim($rawValue);
            if (
                (str_starts_with($rawValue, '"') && str_ends_with($rawValue, '"'))
                || (str_starts_with($rawValue, "'") && str_ends_with($rawValue, "'"))
            ) {
                $rawValue = substr($rawValue, 1, -1);
            }

            return trim(str_replace('\\n', PHP_EOL, $rawValue));
        }

        return null;
    }

    private static function hasRuntimeAddonSalt(string $addon, bool $testing = false): bool
    {
        $envName = self::runtimeAddonSaltEnvName($addon);
        $testEnvName = 'TEST_'.$envName;

        if ($testing) {
            return self::hasRuntimeEnvironmentSecret($testEnvName, false);
        }

        return self::hasRuntimeEnvironmentSecret($envName);
    }

    private static function runtimeAddonSaltEnvName(string $addon): string
    {
        return 'ENTERPRISE_ADDON_SALT_'.strtoupper(str_replace('-', '_', $addon));
    }

    private static function cachedFeatureMap(array $result): array
    {
        if (self::$requestFeatureMap !== null) {
            return self::$requestFeatureMap;
        }

        self::$requestFeatureMap = self::featureMapFromResult($result);

        return self::$requestFeatureMap;
    }

    private static function cachedAddonMap(array $result): array
    {
        if (self::$requestAddonMap !== null) {
            return self::$requestAddonMap;
        }

        self::$requestAddonMap = self::addonMapFromResult($result);

        return self::$requestAddonMap;
    }

    private static function featureMapFromResult(array $result): array
    {
        $features = $result['license']['features'] ?? [];
        if (! is_array($features)) {
            $features = [];
        }

        $addons = $result['license']['addons'] ?? [];
        if (is_array($addons)) {
            $features = array_merge($features, $addons);
        }

        $map = [];
        foreach ($features as $feature) {
            $key = self::normalizeFeatureKey((string) $feature);
            if ($key !== '') {
                $map[$key] = true;
            }
        }

        return $map;
    }

    private static function addonMapFromResult(array $result): array
    {
        $addons = $result['license']['addons'] ?? [];
        if (! is_array($addons)) {
            $addons = [];
        }

        $map = [];
        foreach ($addons as $addon) {
            $key = self::normalizeFeatureKey((string) $addon);
            if ($key !== '') {
                $map[$key] = true;
            }
        }

        return $map;
    }

    private static function validateDomain(array $license, array $context): ?array
    {
        $domain = trim((string) ($license['domain'] ?? '*'));
        if ($domain === '*' || $domain === 'localhost' || $domain === '127.0.0.1') {
            return null;
        }

        $currentHost = self::currentHost($context);
        if ($currentHost === '') {
            return null;
        }

        $cleanHost = preg_replace('/^www\./', '', mb_strtolower($currentHost));
        $cleanDomain = preg_replace('/^www\./', '', mb_strtolower($domain));

        if ($cleanHost === $cleanDomain) {
            return null;
        }

        return self::fail(
            'domain_mismatch',
            'License domain does not match this server host.',
            [
                'expected_domain' => $domain,
                'current_host' => $currentHost,
            ],
            'LicenseGuard: Domain binding mismatch.',
            ['license_domain' => $domain, 'host' => $currentHost]
        );
    }

    private static function validateSchema(array $license): ?array
    {
        $schemaVersion = $license['schema_version'] ?? 1;
        if ((int) $schemaVersion !== 1) {
            return self::fail(
                'unsupported_schema',
                'License schema version is not supported.',
                ['schema_version' => $schemaVersion],
                'LicenseGuard: Unsupported license schema.',
                ['schema_version' => $schemaVersion]
            );
        }

        if (array_key_exists('license_id', $license) && trim((string) $license['license_id']) === '') {
            return self::fail(
                'invalid_payload',
                'License payload could not be read.',
                ['field' => 'license_id'],
                'LicenseGuard: Empty license ID.'
            );
        }

        $features = $license['features'] ?? [];
        $addons = $license['addons'] ?? [];

        if (! is_array($features)) {
            return self::fail(
                'invalid_payload',
                'License payload could not be read.',
                ['field' => 'features'],
                'LicenseGuard: Invalid enterprise features.'
            );
        }

        if (! is_array($addons)) {
            return self::fail(
                'invalid_payload',
                'License payload could not be read.',
                ['field' => 'addons'],
                'LicenseGuard: Invalid enterprise add-ons.'
            );
        }

        if ($features === [] && $addons === []) {
            return self::fail(
                'invalid_payload',
                'License payload could not be read.',
                ['field' => 'features|addons'],
                'LicenseGuard: Missing enterprise entitlements.'
            );
        }

        return null;
    }

    private static function validateHardwareId(array $license, array $context): ?array
    {
        $licenseHwid = trim((string) ($license['hwid'] ?? '*'));
        if ($licenseHwid === '*') {
            return null;
        }

        $currentHardwareId = self::currentHardwareId($context);
        if ($currentHardwareId === $licenseHwid) {
            return null;
        }

        return self::fail(
            'hwid_mismatch',
            'License hardware ID does not match this server.',
            [
                'expected_hwid' => $licenseHwid,
                'current_hwid' => $currentHardwareId,
            ],
            'LicenseGuard: Hardware ID mismatch.',
            ['license_hwid' => $licenseHwid, 'current' => $currentHardwareId]
        );
    }

    private static function validateExpiry(array $license, array $context): ?array
    {
        if (! isset($license['expires_at']) || trim((string) $license['expires_at']) === '') {
            return null;
        }

        $verificationTime = self::verificationTime($context);
        $expiry = Carbon::parse($license['expires_at']);

        if (! $verificationTime->startOfDay()->isAfter($expiry->copy()->endOfDay())) {
            return null;
        }

        return self::fail(
            'expired',
            'License has expired.',
            [
                'expires_at' => $license['expires_at'],
                'verified_at' => $verificationTime->toDateTimeString(),
            ],
            'LicenseGuard: License has expired.',
            ['expires_at' => $license['expires_at'], 'verified_time' => $verificationTime->toDateTimeString()]
        );
    }

    private static function validateNotBefore(array $license, array $context): ?array
    {
        if (! isset($license['not_before']) || trim((string) $license['not_before']) === '') {
            return null;
        }

        $verificationTime = self::verificationTime($context);
        $notBefore = Carbon::parse($license['not_before']);

        if (! $verificationTime->isBefore($notBefore)) {
            return null;
        }

        return self::fail(
            'not_yet_valid',
            'License is not valid yet.',
            [
                'not_before' => $license['not_before'],
                'verified_at' => $verificationTime->toDateTimeString(),
            ],
            'LicenseGuard: License is not valid yet.',
            ['not_before' => $license['not_before'], 'verified_time' => $verificationTime->toDateTimeString()]
        );
    }

    private static function validateCompany(array $license, array $context): ?array
    {
        $expectedCompany = trim((string) ($license['client'] ?? ''));
        $currentCompany = self::currentCompany($context);

        if (self::normalizeCompany($expectedCompany) === self::normalizeCompany($currentCompany)) {
            return null;
        }

        return self::fail(
            'company_mismatch',
            'License company name does not match the current setting.',
            [
                'expected_company' => $expectedCompany,
                'current_company' => $currentCompany,
            ],
            'LicenseGuard: Client name mismatch.',
            ['license_client' => $expectedCompany, 'setting_company' => $currentCompany]
        );
    }

    private static function validateSupportContact(array $license, array $context): ?array
    {
        $expectedContact = trim((string) ($license['support_contact'] ?? ''));
        if ($expectedContact === '') {
            return null;
        }

        $currentContact = self::currentSupportContact($context);
        if (self::normalizeSupportContact($expectedContact) === self::normalizeSupportContact($currentContact)) {
            return null;
        }

        return self::fail(
            'support_contact_mismatch',
            'License support contact does not match the current setting.',
            [
                'expected_support_contact' => $expectedContact,
                'current_support_contact' => $currentContact,
            ],
            'LicenseGuard: Support contact mismatch.',
            ['license_contact' => $expectedContact, 'setting_contact' => $currentContact]
        );
    }

    private static function validateUserCount(array $license, array $context): ?array
    {
        $maxUsers = (int) ($license['max_users'] ?? 0);
        if ($maxUsers <= 0) {
            return null;
        }

        $currentUsers = self::currentUserCount($context);
        if ($currentUsers <= $maxUsers) {
            return null;
        }

        return self::fail(
            'max_users_exceeded',
            'Current user count exceeds the licensed limit.',
            [
                'max_users' => $maxUsers,
                'current_users' => $currentUsers,
            ],
            'LicenseGuard: MAX USERS limit exceeded.',
            ['max_users' => $maxUsers, 'current' => $currentUsers]
        );
    }

    private static function verificationTime(array $context): Carbon
    {
        if (array_key_exists('current_time', $context)) {
            return Carbon::parse($context['current_time']);
        }

        $verificationTime = now();
        $shouldTryRemoteTime = ($context['enable_remote_time'] ?? false) && ! app()->runningUnitTests();

        if (! $shouldTryRemoteTime) {
            return $verificationTime;
        }

        try {
            $streamContext = stream_context_create(['http' => ['timeout' => 2]]);
            $headers = @get_headers('https://google.com', true, $streamContext);
            if (! empty($headers['Date'])) {
                $dateHeader = is_array($headers['Date']) ? $headers['Date'][0] : $headers['Date'];
                $verificationTime = Carbon::parse($dateHeader);
            }
        } catch (\Throwable $e) {
            // Fall back to local server time silently.
        }

        return $verificationTime;
    }

    private static function currentCompany(array $context): string
    {
        return trim((string) ($context['current_company'] ?? Setting::getValue('app.company_name', '')));
    }

    private static function currentSupportContact(array $context): string
    {
        return trim((string) ($context['current_support_contact'] ?? Setting::getValue('app.support_contact', '')));
    }

    private static function currentHost(array $context): string
    {
        if (array_key_exists('current_host', $context)) {
            return trim((string) $context['current_host']);
        }

        if (app()->runningInConsole()) {
            return '';
        }

        return trim((string) request()->getHost());
    }

    private static function currentHardwareId(array $context): string
    {
        return trim((string) ($context['current_hwid'] ?? EnterpriseHwId::generate()));
    }

    private static function currentUserCount(array $context): int
    {
        return array_key_exists('user_count', $context) ? (int) $context['user_count'] : User::count();
    }

    private static function normalizeCompany(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/^(pt|cv)\b\.?\s*/u', '', $value);
        $value = preg_replace('/[^\pL\pN]+/u', '', $value);

        return $value ?? '';
    }

    private static function normalizeSupportContact(string $value): string
    {
        $value = mb_strtolower(trim($value));

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'https://t.me/')) {
            $value = substr($value, strlen('https://t.me/'));
        } elseif (str_starts_with($value, 'http://t.me/')) {
            $value = substr($value, strlen('http://t.me/'));
        } elseif (str_starts_with($value, 't.me/')) {
            $value = substr($value, strlen('t.me/'));
        }

        $value = explode('?', $value)[0];
        $value = trim($value, '/');

        if ($value !== '' && ! str_contains($value, '@') && ! str_contains($value, ':')) {
            $value = '@'.$value;
        }

        return $value;
    }

    private static function normalizeFeatureKey(string $feature): string
    {
        $feature = mb_strtolower(trim($feature));
        $feature = str_replace(['-', ' '], '_', $feature);

        return preg_replace('/[^a-z0-9_*]+/', '', $feature) ?? '';
    }

    private static function licenseHash(string $licenseKey, array $context = []): string
    {
        return hash('sha256', implode('|', [
            $licenseKey,
            self::normalizeCompany(self::currentCompany($context)),
            self::normalizeSupportContact(self::currentSupportContact($context)),
            mb_strtolower(self::currentHost($context)),
            self::currentHardwareId($context),
            (string) self::currentUserCount($context),
        ]));
    }

    private static function validCacheExpiration(array $result): Carbon
    {
        $expiresAt = $result['license']['expires_at'] ?? null;

        if (is_string($expiresAt) && trim($expiresAt) !== '') {
            try {
                $licenseExpiry = Carbon::parse($expiresAt)->endOfDay();

                if ($licenseExpiry->isBefore(now()->addHours(24))) {
                    return $licenseExpiry;
                }
            } catch (\Throwable $e) {
                // Keep the default cache TTL if the already validated date cannot be parsed here.
            }
        }

        return now()->addHours(24);
    }

    private static function fail(string $code, string $message, array $details, string $logMessage, array $logContext = []): array
    {
        Log::warning('LicenseGuard: Validation failed.', ['code' => $code]);

        return self::result(valid: false, code: $code, message: self::publicMessageFor($code), details: $details);
    }

    private static function result(bool $valid, string $code, string $message, array $details = [], ?array $license = null): array
    {
        return [
            'valid' => $valid,
            'code' => $code,
            'message' => $message,
            'details' => $details,
            'license' => $license,
        ];
    }

    private static function publicMessageFor(string $code): string
    {
        return match ($code) {
            'missing_key' => 'No enterprise license key saved yet.',
            'valid' => 'Enterprise license is active.',
            default => 'The enterprise license could not be validated on this server.',
        };
    }
}
