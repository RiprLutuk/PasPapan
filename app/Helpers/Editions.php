<?php

namespace App\Helpers;

use App\Contracts\AttendanceServiceInterface;
use App\Contracts\AuditServiceInterface;
use App\Contracts\PayrollServiceInterface;
use App\Contracts\ReportingServiceInterface;
use App\Services\Enterprise\LicenseGuard;

class Editions
{
    public static function forceDemoModeLocking(): ?bool
    {
        if (config('app.env') === 'demo' && session()->has('demo_enterprise_mode')) {
            return ! session('demo_enterprise_mode');
        }

        return null;
    }

    /**
     * Check if a specific feature service is running in Community Mode (Locked).
     */
    public static function isLocked(string $contractClass): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        $feature = self::featureForContract($contractClass);

        return $feature === null
            ? ! LicenseGuard::hasValidLicense()
            : ! LicenseGuard::hasFeature($feature);
    }

    public static function payrollLocked(): bool
    {
        return self::isLocked(PayrollServiceInterface::class);
    }

    public static function reportingLocked(): bool
    {
        return self::isLocked(ReportingServiceInterface::class);
    }

    public static function auditLocked(): bool
    {
        return self::isLocked(AuditServiceInterface::class);
    }

    public static function attendanceLocked(): bool
    {
        return self::isLocked(AttendanceServiceInterface::class);
    }

    public static function assetLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('asset_management');
    }

    public static function appraisalLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('appraisal');
    }

    public static function analyticsLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('analytics');
    }

    public static function cashAdvanceLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('cash_advance');
    }

    public static function systemBackupLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('system_backup');
    }

    public static function documentRequestsLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('document_requests');
    }

    public static function tokoPosLocked(): bool
    {
        $demo = self::forceDemoModeLocking();
        if ($demo !== null) {
            return $demo;
        }

        return ! LicenseGuard::hasFeature('toko_pos');
    }

    private static function featureForContract(string $contractClass): ?string
    {
        return match ($contractClass) {
            AttendanceServiceInterface::class => 'attendance',
            PayrollServiceInterface::class => 'payroll',
            ReportingServiceInterface::class => 'reporting',
            AuditServiceInterface::class => 'audit',
            default => null,
        };
    }
}
