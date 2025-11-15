<?php

namespace App\Providers;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
       $this->routes(function () {
            // API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web Routes
            Route::middleware('web')
                ->group(function () {
                    Route::get('/', function(){
                        // Check if app is installed via env variable or file
                        $isInstalled = env('APP_INSTALLED', false) || file_exists(storage_path('installed'));

                        if ($isInstalled) {
                            // Redirect to admin area if installed
                            return redirect('/admin');
                        }

                        // Redirect to installer if not installed
                        return redirect()->route('installer.welcome.index');
                    });
                });
       });
    }
}
