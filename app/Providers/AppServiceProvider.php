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
        //
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
