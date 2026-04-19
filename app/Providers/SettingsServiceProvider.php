<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            View::share('appName', Setting::getValue('app.name', config('app.name')));
            View::share('companyName', Setting::getValue('app.company_name', 'My Company'));
            View::share('companyAddress', Setting::getValue('app.company_address', ''));
            View::share('supportContact', Setting::getValue('app.support_contact', ''));
        } catch (\Throwable) {
            // Ignore when the database is not ready yet.
        }
    }
}
