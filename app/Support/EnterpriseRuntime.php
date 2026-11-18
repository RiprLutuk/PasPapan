<?php

namespace App\Support;

use App\Services\Audit\EnterpriseAuditService;
use App\Services\Enterprise\LicenseGuard;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

final class EnterpriseRuntime
{
    public static function sourceAvailable(?string $addon = null, ?string $probeClass = null): bool
    {
        $probeClass ??= $addon === 'toko_pos'
            ? TokoPosSalesService::class
            : EnterpriseAuditService::class;

        try {
            if (! class_exists($probeClass) && ! interface_exists($probeClass) && ! trait_exists($probeClass)) {
                return false;
            }

            // Rebuilt Enterprise modules are deliberately stored as ordinary PHP.
            // They do not need the legacy artifact decryption secret; feature access
            // remains protected by LicenseGuard at the route/component boundary.
            if (! self::classUsesSecuredRuntimeFile($probeClass)) {
                return true;
            }

            if (! LicenseGuard::hasRuntimeSourceKey($addon)) {
                return false;
            }

            return self::loadedRuntimeClassIsUsable($probeClass, $addon);
        } catch (\Throwable $e) {
            LicenseGuard::clearRuntimeSourceSecretCache($addon);

            Log::warning('Enterprise runtime source unavailable.', [
                'addon' => $addon,
                'probe' => $probeClass,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  class-string  $enterpriseClass
     */
    public static function resolve(string $feature, string $enterpriseClass, object $communityService): object
    {
        if (! LicenseGuard::hasFeature($feature) || ! LicenseGuard::hasRuntimeSourceKey()) {
            return $communityService;
        }

        try {
            if (class_exists($enterpriseClass) && self::loadedRuntimeClassIsUsable($enterpriseClass)) {
                return new $enterpriseClass;
            }
        } catch (\Throwable $e) {
            LicenseGuard::clearRuntimeSourceSecretCache();

            Log::warning('Enterprise runtime service unavailable; falling back to community service.', [
                'feature' => $feature,
                'service' => $enterpriseClass,
                'error' => $e->getMessage(),
            ]);
        }

        return $communityService;
    }

    /**
     * @param  class-string  $class
     */
    private static function loadedRuntimeClassIsUsable(string $class, ?string $addon = null): bool
    {
        if (! self::classUsesSecuredRuntimeFile($class)) {
            return true;
        }

        return LicenseGuard::runtimeSourceSecretCacheMatches($addon);
    }

    /**
     * @param  class-string  $class
     */
    private static function classUsesSecuredRuntimeFile(string $class): bool
    {
        $path = self::runtimeFileForClass($class);
        if ($path === null) {
            return false;
        }

        $contents = file_get_contents($path, false, null, 0, 4096);
        if (! is_string($contents)) {
            return false;
        }

        return str_contains($contents, 'Enterprise Core Secured')
            && str_contains($contents, 'eval(gzinflate(base64_decode');
    }

    /**
     * @param  class-string  $class
     */
    private static function runtimeFileForClass(string $class): ?string
    {
        try {
            $reflectionFile = (new ReflectionClass($class))->getFileName();
            if (is_string($reflectionFile) && is_file($reflectionFile)) {
                return $reflectionFile;
            }
        } catch (\ReflectionException) {
            return null;
        }

        $prefix = 'App\\';
        if (! str_starts_with($class, $prefix)) {
            return null;
        }

        $path = base_path('app/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php');

        return is_file($path) ? $path : null;
    }
}
