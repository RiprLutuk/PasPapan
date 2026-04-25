<?php

namespace App\Providers;

use App\Contracts\AuditServiceInterface;
use App\Models\ActivityLog;
use App\Models\SystemBackupRun;
use App\Observers\SystemBackupRunObserver;
use App\Services\Audit\CommunityAuditService;
use App\Services\Enterprise\LicenseGuard;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditServiceInterface::class, function () {
            if (class_exists(\App\Services\Audit\EnterpriseAuditService::class) && LicenseGuard::hasValidLicense()) {
                return new \App\Services\Audit\EnterpriseAuditService;
            }

            return new CommunityAuditService;
        });
    }

    public function boot(): void
    {
        SystemBackupRun::observe(SystemBackupRunObserver::class);

        Event::listen(Login::class, function (Login $event) {
            ActivityLog::record('Login Successful', 'User logged in.');
        });

        Event::listen(Failed::class, function (Failed $event) {
            ActivityLog::record(
                'Login Failed',
                'Failed login attempt for email: '.($event->credentials['email'] ?? 'unknown')
            );
        });
    }
}
