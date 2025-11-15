<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Shop\Auth\LoginController as ShopLoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root route - redirect based on installation status
Route::get('/', function () {
    $isInstalled = env('APP_INSTALLED', false) || file_exists(storage_path('installed'));

    if ($isInstalled) {
        return redirect()->route('admin.login');
    }

    return redirect()->route('installer.welcome.index');
});

/*
|--------------------------------------------------------------------------
| Admin Web Routes
|--------------------------------------------------------------------------
*/

// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Login routes
    Route::get('/login', [AdminLoginController::class, 'index'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.store');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // Protected admin routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });
});

// Shop Authentication Routes
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/login', [ShopLoginController::class, 'index'])->name('login');
    Route::post('/login', [ShopLoginController::class, 'login'])->name('login.store');
    Route::post('/logout', [ShopLoginController::class, 'logout'])->name('logout');
});
