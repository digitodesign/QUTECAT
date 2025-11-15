<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        // Force HTTPS in production (for Railway/cloud deployments)
        if (env('APP_ENV') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if(request()->ip() != '127.0.0.1'){
            Schema::defaultStringLength(191);
            // Check environment variable first (for Railway/cloud deployments), then file
            $isInstalled = env('APP_INSTALLED', false) || file_exists(base_path('storage/installed'));
            if (!$isInstalled && !request()->is('install') && !request()->is('install/*')) {
                header("Location: install");
                exit;
            }
        }
    }
}
