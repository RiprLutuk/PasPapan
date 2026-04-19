<?php

namespace App\Providers;

use App\Contracts\AttendanceServiceInterface;
use App\Contracts\PayrollServiceInterface;
use App\Contracts\ReportingServiceInterface;
use App\Services\Attendance\CommunityService;
use App\Services\Enterprise\LicenseGuard;
use App\Services\Payroll\CommunityPayrollService;
use App\Services\Reporting\CommunityReportingService;
use Illuminate\Support\ServiceProvider;

class EnterpriseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AttendanceServiceInterface::class, function () {
            if (class_exists(\App\Services\Attendance\EnterpriseService::class) && LicenseGuard::hasValidLicense()) {
                return new \App\Services\Attendance\EnterpriseService();
            }

            return new CommunityService();
        });

        $this->app->singleton(PayrollServiceInterface::class, function () {
            if (class_exists(\App\Services\Payroll\EnterprisePayrollService::class) && LicenseGuard::hasValidLicense()) {
                return new \App\Services\Payroll\EnterprisePayrollService();
            }

            return new CommunityPayrollService();
        });

        $this->app->singleton(ReportingServiceInterface::class, function () {
            if (class_exists(\App\Services\Reporting\EnterpriseReportingService::class) && LicenseGuard::hasValidLicense()) {
                return new \App\Services\Reporting\EnterpriseReportingService();
            }

            return new CommunityReportingService();
        });
    }
}
