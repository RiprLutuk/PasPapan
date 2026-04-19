<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Open-Core Architecture:
        // Bind to EnterpriseService if available AND LICENSED, otherwise fallback to CommunityService.
        $this->app->singleton(\App\Contracts\AttendanceServiceInterface::class, function ($app) {
             if (class_exists(\App\Services\Attendance\EnterpriseService::class) && \App\Services\Enterprise\LicenseGuard::hasValidLicense()) {
                 return new \App\Services\Attendance\EnterpriseService();
             }
             return new \App\Services\Attendance\CommunityService();
        });

        // Payroll Service Binding (Locked)
        $this->app->singleton(\App\Contracts\PayrollServiceInterface::class, function ($app) {
             if (class_exists(\App\Services\Payroll\EnterprisePayrollService::class) && \App\Services\Enterprise\LicenseGuard::hasValidLicense()) {
                 return new \App\Services\Payroll\EnterprisePayrollService();
             }
             return new \App\Services\Payroll\CommunityPayrollService();
        });

        // Reporting Service Binding (Locked)
        $this->app->singleton(\App\Contracts\ReportingServiceInterface::class, function ($app) {
             if (class_exists(\App\Services\Reporting\EnterpriseReportingService::class) && \App\Services\Enterprise\LicenseGuard::hasValidLicense()) {
                 return new \App\Services\Reporting\EnterpriseReportingService();
             }
             return new \App\Services\Reporting\CommunityReportingService();
        });

        // Audit Service Binding (Locked)
        $this->app->singleton(\App\Contracts\AuditServiceInterface::class, function ($app) {
             if (class_exists(\App\Services\Audit\EnterpriseAuditService::class) && \App\Services\Enterprise\LicenseGuard::hasValidLicense()) {
                 return new \App\Services\Audit\EnterpriseAuditService();
             }
             return new \App\Services\Audit\CommunityAuditService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share App Settings with all views
        try {
            // Wrap in try-catch to avoid issues during migration/seeding if table doesn't exist
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                \Illuminate\Support\Facades\View::share('appName', \App\Models\Setting::getValue('app.name', config('app.name')));
                \Illuminate\Support\Facades\View::share('companyName', \App\Models\Setting::getValue('app.company_name', 'My Company'));
                \Illuminate\Support\Facades\View::share('companyAddress', \App\Models\Setting::getValue('app.company_address', ''));
                \Illuminate\Support\Facades\View::share('supportContact', \App\Models\Setting::getValue('app.support_contact', ''));
            }
        } catch (\Exception $e) {
            // Fallback defaults
        }

        \Illuminate\Support\Facades\RateLimiter::for('global', function (\Illuminate\Http\Request $request) {
            $limit = (int) \App\Models\Setting::getValue('security.rate_limit_global', 1000);
            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            $limit = (int) \App\Models\Setting::getValue('security.rate_limit_login', 5);
            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($request->ip());
        });

        // API Rate Limiter - Protect against API abuse
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            $limit = (int) \App\Models\Setting::getValue('security.rate_limit_api', 60);
            return \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });


        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            \App\Models\ActivityLog::create([
                'user_id' => $event->user->id,
                'action' => 'Login Successful',
                'description' => 'User logged in.',
                'ip_address' => request()->ip(),
            ]);
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            // Failed login usually doesn't have a user instance if user not found, but might have credentials
            // We'll try to find user by email if possible, or just log generic
             \App\Models\ActivityLog::create([
                'user_id' => null, // Can't link to user if failed
                'action' => 'Login Failed',
                'description' => 'Failed login attempt for email: ' . ($event->credentials['email'] ?? 'unknown'),
                'ip_address' => request()->ip(),
            ]);
        });
    }
}
