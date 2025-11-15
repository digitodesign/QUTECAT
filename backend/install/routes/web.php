<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CreateSuperAdmin;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Shop\Auth\LoginController as ShopLoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// =============================================================================
// ROOT USER CREATION (One-time setup)
// =============================================================================
Route::middleware(['check_root_user'])->group(function () {
    Route::get('create-root', [CreateSuperAdmin::class, 'index'])->name('create.root');
    Route::post('create-root', [CreateSuperAdmin::class, 'store'])->name('create.superadmin');
});

// =============================================================================
// ADMIN AUTHENTICATION
// =============================================================================
Route::prefix('admin')->name('admin.')->group(function () {
    // Admin Login Routes
    Route::get('login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// =============================================================================
// SHOP/VENDOR AUTHENTICATION
// =============================================================================
Route::prefix('shop')->name('shop.')->group(function () {
    // Shop Login Routes
    Route::get('login', [ShopLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [ShopLoginController::class, 'login'])->name('login.post');
    Route::post('logout', [ShopLoginController::class, 'logout'])->name('logout');
});

// =============================================================================
// ADMIN PANEL ROUTES (Root & Superadmin Only)
// =============================================================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:root|superadmin'])->group(function () {

    // Dashboard
    Route::get('dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/order-statistics', [App\Http\Controllers\Admin\DashboardController::class, 'orderStatistics'])->name('dashboard.order.statistics');

    // Product Management (Approval System)
    Route::prefix('products')->name('product.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('index');
        Route::get('{product}/show', [App\Http\Controllers\Admin\ProductController::class, 'show'])->name('show');
        Route::post('{product}/approve', [App\Http\Controllers\Admin\ProductController::class, 'approve'])->name('approve');
        Route::delete('{product}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('destroy');
    });

    // Category Management
    Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('sub-categories', App\Http\Controllers\Admin\SubCategoryController::class);

    // Brand Management
    Route::resource('brands', App\Http\Controllers\Admin\BrandController::class);

    // Shop/Vendor Management
    Route::resource('shops', App\Http\Controllers\Admin\ShopController::class);
    Route::post('shops/{shop}/approve', [App\Http\Controllers\Admin\ShopController::class, 'approve'])->name('shops.approve');
    Route::post('shops/{shop}/status', [App\Http\Controllers\Admin\ShopController::class, 'updateStatus'])->name('shops.status');

    // Order Management
    Route::resource('orders', App\Http\Controllers\Admin\OrderController::class);
    Route::get('orders/{order}/invoice', [App\Http\Controllers\Admin\OrderController::class, 'invoice'])->name('orders.invoice');
    Route::post('orders/{order}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.status');

    // Return Order Management
    Route::resource('return-orders', App\Http\Controllers\Admin\ReturnOrderController::class);
    Route::post('return-orders/{order}/status', [App\Http\Controllers\Admin\ReturnOrderController::class, 'updateStatus'])->name('return-orders.status');

    // Customer Management
    Route::resource('customers', App\Http\Controllers\Admin\CustomerController::class);
    Route::post('customers/{customer}/status', [App\Http\Controllers\Admin\CustomerController::class, 'updateStatus'])->name('customers.status');

    // Customer Notifications
    Route::resource('customer-notifications', App\Http\Controllers\Admin\CustomerNotificationController::class);

    // Employee Management
    Route::resource('employees', App\Http\Controllers\Admin\EmployeeManageController::class);

    // Role & Permission Management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RolePermissionController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\Admin\RolePermissionController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\RolePermissionController::class, 'store'])->name('store');
        Route::get('{role}/edit', [App\Http\Controllers\Admin\RolePermissionController::class, 'edit'])->name('edit');
        Route::put('{role}', [App\Http\Controllers\Admin\RolePermissionController::class, 'update'])->name('update');
        Route::delete('{role}', [App\Http\Controllers\Admin\RolePermissionController::class, 'destroy'])->name('destroy');
    });

    // Flash Sale Management
    Route::resource('flash-sales', App\Http\Controllers\Admin\FlashSaleController::class);
    Route::post('flash-sales/{flashSale}/status', [App\Http\Controllers\Admin\FlashSaleController::class, 'updateStatus'])->name('flash-sales.status');

    // Coupon Management
    Route::resource('coupons', App\Http\Controllers\Admin\CouponController::class);
    Route::post('coupons/{coupon}/status', [App\Http\Controllers\Admin\CouponController::class, 'updateStatus'])->name('coupons.status');

    // Banner Management
    Route::resource('banners', App\Http\Controllers\Admin\BannerController::class);
    Route::post('banners/{banner}/status', [App\Http\Controllers\Admin\BannerController::class, 'updateStatus'])->name('banners.status');

    // Blog Management
    Route::resource('blogs', App\Http\Controllers\Admin\BlogController::class);
    Route::post('blogs/{blog}/status', [App\Http\Controllers\Admin\BlogController::class, 'updateStatus'])->name('blogs.status');

    // Ads Management
    Route::resource('ads', App\Http\Controllers\Admin\AdController::class);
    Route::post('ads/{ad}/status', [App\Http\Controllers\Admin\AdController::class, 'updateStatus'])->name('ads.status');

    // Review Management
    Route::resource('reviews', App\Http\Controllers\Admin\ReviewsController::class);
    Route::post('reviews/{review}/status', [App\Http\Controllers\Admin\ReviewsController::class, 'updateStatus'])->name('reviews.status');

    // Rider Management
    Route::resource('riders', App\Http\Controllers\Admin\RiderController::class);
    Route::post('riders/{rider}/status', [App\Http\Controllers\Admin\RiderController::class, 'updateStatus'])->name('riders.status');

    // Delivery Charge Management
    Route::resource('delivery-charges', App\Http\Controllers\Admin\DeliveryChargeController::class);

    // VAT/Tax Management
    Route::resource('vat-taxes', App\Http\Controllers\Admin\VatTaxController::class);

    // Color Management
    Route::resource('colors', App\Http\Controllers\Admin\ColorController::class);

    // Size Management
    Route::resource('sizes', App\Http\Controllers\Admin\SizeController::class);

    // Unit Management
    Route::resource('units', App\Http\Controllers\Admin\UnitController::class);

    // Currency Management
    Route::resource('currencies', App\Http\Controllers\Admin\CurrencyController::class);
    Route::post('currencies/{currency}/default', [App\Http\Controllers\Admin\CurrencyController::class, 'setDefault'])->name('currencies.default');

    // Country Management
    Route::resource('countries', App\Http\Controllers\Admin\CountryController::class);
    Route::post('countries/{country}/status', [App\Http\Controllers\Admin\CountryController::class, 'updateStatus'])->name('countries.status');

    // Subscription Plan Management (SaaS)
    Route::resource('subscription-plans', App\Http\Controllers\Admin\SubscriptionPlanController::class);
    Route::post('subscription-plans/{plan}/status', [App\Http\Controllers\Admin\SubscriptionPlanController::class, 'updateStatus'])->name('subscription-plans.status');

    // Withdraw Management
    Route::resource('withdraws', App\Http\Controllers\Admin\WithdrawController::class);
    Route::post('withdraws/{withdraw}/approve', [App\Http\Controllers\Admin\WithdrawController::class, 'approve'])->name('withdraws.approve');
    Route::post('withdraws/{withdraw}/deny', [App\Http\Controllers\Admin\WithdrawController::class, 'deny'])->name('withdraws.deny');

    // Support Ticket Management
    Route::resource('support-tickets', App\Http\Controllers\Admin\SupportTicketController::class);
    Route::post('support-tickets/{ticket}/reply', [App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('support-tickets.reply');
    Route::post('support-tickets/{ticket}/close', [App\Http\Controllers\Admin\SupportTicketController::class, 'close'])->name('support-tickets.close');

    // Ticket Issue Type Management
    Route::resource('ticket-issue-types', App\Http\Controllers\Admin\TicketIssueTypeController::class);

    // Support/Help Management
    Route::resource('support', App\Http\Controllers\Admin\SupportController::class);

    // Page Management
    Route::resource('pages', App\Http\Controllers\Admin\PageController::class);
    Route::post('pages/{page}/status', [App\Http\Controllers\Admin\PageController::class, 'updateStatus'])->name('pages.status');

    // Menu Management
    Route::resource('menus', App\Http\Controllers\Admin\MenuController::class);
    Route::post('menus/{menu}/status', [App\Http\Controllers\Admin\MenuController::class, 'updateStatus'])->name('menus.status');

    // Footer Management
    Route::resource('footers', App\Http\Controllers\Admin\FooterController::class);

    // Social Link Management
    Route::resource('social-links', App\Http\Controllers\Admin\SocialLinkController::class);

    // Language Management
    Route::resource('languages', App\Http\Controllers\Admin\LanguageController::class);
    Route::post('languages/{language}/default', [App\Http\Controllers\Admin\LanguageController::class, 'setDefault'])->name('languages.default');
    Route::get('languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'translations'])->name('languages.translations');
    Route::post('languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'updateTranslations'])->name('languages.translations.update');

    // Contact Us Messages
    Route::get('contact-us', [App\Http\Controllers\Admin\ContactUsController::class, 'index'])->name('contact-us.index');
    Route::get('contact-us/{message}', [App\Http\Controllers\Admin\ContactUsController::class, 'show'])->name('contact-us.show');
    Route::delete('contact-us/{message}', [App\Http\Controllers\Admin\ContactUsController::class, 'destroy'])->name('contact-us.destroy');

    // Notifications
    Route::get('notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-as-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');

    // Online Users
    Route::get('online-users', [App\Http\Controllers\Admin\CheckOnlineUserController::class, 'index'])->name('online-users');

    // =============================================================================
    // SETTINGS
    // =============================================================================
    Route::prefix('settings')->name('settings.')->group(function () {

        // General Settings
        Route::get('general', [App\Http\Controllers\Admin\GeneraleSettingController::class, 'index'])->name('general');
        Route::post('general', [App\Http\Controllers\Admin\GeneraleSettingController::class, 'update'])->name('general.update');

        // Business Setup
        Route::get('business', [App\Http\Controllers\Admin\BusinessSetupController::class, 'index'])->name('business');
        Route::post('business', [App\Http\Controllers\Admin\BusinessSetupController::class, 'update'])->name('business.update');

        // Theme Color
        Route::get('theme-color', [App\Http\Controllers\Admin\ThemeColorController::class, 'index'])->name('theme-color');
        Route::post('theme-color', [App\Http\Controllers\Admin\ThemeColorController::class, 'update'])->name('theme-color.update');

        // Mail Configuration
        Route::get('mail', [App\Http\Controllers\Admin\MailConfigurationController::class, 'index'])->name('mail');
        Route::post('mail', [App\Http\Controllers\Admin\MailConfigurationController::class, 'update'])->name('mail.update');

        // SMS Gateway Setup
        Route::get('sms-gateway', [App\Http\Controllers\Admin\SMSGatewaySetupController::class, 'index'])->name('sms-gateway');
        Route::post('sms-gateway', [App\Http\Controllers\Admin\SMSGatewaySetupController::class, 'update'])->name('sms-gateway.update');

        // Payment Gateway
        Route::get('payment-gateway', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'index'])->name('payment-gateway');
        Route::post('payment-gateway', [App\Http\Controllers\Admin\PaymentGatewayController::class, 'update'])->name('payment-gateway.update');

        // Social Authentication
        Route::get('social-auth', [App\Http\Controllers\Admin\SocialAuthController::class, 'index'])->name('social-auth');
        Route::post('social-auth', [App\Http\Controllers\Admin\SocialAuthController::class, 'update'])->name('social-auth.update');

        // Firebase Configuration
        Route::get('firebase', [App\Http\Controllers\Admin\FirebaseController::class, 'index'])->name('firebase');
        Route::post('firebase', [App\Http\Controllers\Admin\FirebaseController::class, 'update'])->name('firebase.update');

        // Pusher Configuration
        Route::get('pusher', [App\Http\Controllers\Admin\PusherConfigController::class, 'index'])->name('pusher');
        Route::post('pusher', [App\Http\Controllers\Admin\PusherConfigController::class, 'update'])->name('pusher.update');

        // Google reCAPTCHA
        Route::get('recaptcha', [App\Http\Controllers\Admin\GoogleReCaptchaController::class, 'index'])->name('recaptcha');
        Route::post('recaptcha', [App\Http\Controllers\Admin\GoogleReCaptchaController::class, 'update'])->name('recaptcha.update');

        // Verify Manage (2FA, etc.)
        Route::get('verify-manage', [App\Http\Controllers\Admin\VerifyManageController::class, 'index'])->name('verify-manage');
        Route::post('verify-manage', [App\Http\Controllers\Admin\VerifyManageController::class, 'update'])->name('verify-manage.update');
    });

    // Admin Profile
    Route::get('profile', [App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('profile');
    Route::post('profile', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/password', [App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
});

// =============================================================================
// SHOP PANEL ROUTES (For Vendors/Shop Owners)
// =============================================================================
Route::prefix('shop')->name('shop.')->middleware(['authShop', 'check.limits'])->group(function () {

    // Dashboard
    Route::get('dashboard', [App\Http\Controllers\Shop\DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/statistics', [App\Http\Controllers\Shop\DashboardController::class, 'statistics'])->name('dashboard.statistics');

    // Product Management
    Route::resource('products', App\Http\Controllers\Shop\ProductController::class);
    Route::post('products/{product}/status', [App\Http\Controllers\Shop\ProductController::class, 'updateStatus'])->name('products.status');
    Route::get('products/{product}/duplicate', [App\Http\Controllers\Shop\ProductController::class, 'duplicate'])->name('products.duplicate');

    // Bulk Product Operations
    Route::get('products/bulk/export', [App\Http\Controllers\Shop\BulkProductExportController::class, 'export'])->name('products.bulk.export');
    Route::get('products/bulk/import', [App\Http\Controllers\Shop\BulkProductImportController::class, 'index'])->name('products.bulk.import');
    Route::post('products/bulk/import', [App\Http\Controllers\Shop\BulkProductImportController::class, 'import'])->name('products.bulk.import.post');

    // Category Management (Shop-specific)
    Route::resource('categories', App\Http\Controllers\Shop\CategoryController::class);
    Route::resource('sub-categories', App\Http\Controllers\Shop\SubCategoryController::class);

    // Brand Management (Shop-specific)
    Route::resource('brands', App\Http\Controllers\Shop\BrandController::class);

    // Order Management
    Route::resource('orders', App\Http\Controllers\Shop\OrderController::class);
    Route::get('orders/{order}/invoice', [App\Http\Controllers\Shop\OrderController::class, 'invoice'])->name('orders.invoice');
    Route::post('orders/{order}/status', [App\Http\Controllers\Shop\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('orders/{order}/assign-rider', [App\Http\Controllers\Shop\OrderController::class, 'assignRider'])->name('orders.assign-rider');

    // Return Order Management
    Route::resource('return-orders', App\Http\Controllers\Shop\ReturnOrderController::class);
    Route::post('return-orders/{order}/status', [App\Http\Controllers\Shop\ReturnOrderController::class, 'updateStatus'])->name('return-orders.status');

    // POS (Point of Sale)
    Route::get('pos', [App\Http\Controllers\Shop\POSController::class, 'index'])->name('pos');
    Route::post('pos/create-order', [App\Http\Controllers\Shop\POSController::class, 'createOrder'])->name('pos.create-order');

    // Flash Sale Management
    Route::resource('flash-sales', App\Http\Controllers\Shop\FlashSaleController::class);
    Route::post('flash-sales/{flashSale}/status', [App\Http\Controllers\Shop\FlashSaleController::class, 'updateStatus'])->name('flash-sales.status');

    // Voucher/Coupon Management
    Route::resource('vouchers', App\Http\Controllers\Shop\VoucherController::class);
    Route::post('vouchers/{voucher}/status', [App\Http\Controllers\Shop\VoucherController::class, 'updateStatus'])->name('vouchers.status');

    // Banner Management
    Route::resource('banners', App\Http\Controllers\Shop\BannerController::class);
    Route::post('banners/{banner}/status', [App\Http\Controllers\Shop\BannerController::class, 'updateStatus'])->name('banners.status');

    // Gallery Management
    Route::resource('gallery', App\Http\Controllers\Shop\GalleryController::class);

    // Color Management
    Route::resource('colors', App\Http\Controllers\Shop\ColorController::class);

    // Size Management
    Route::resource('sizes', App\Http\Controllers\Shop\SizeController::class);

    // Unit Management
    Route::resource('units', App\Http\Controllers\Shop\UnitController::class);

    // Employee Management
    Route::resource('employees', App\Http\Controllers\Shop\EmployeeController::class);
    Route::post('employees/{employee}/status', [App\Http\Controllers\Shop\EmployeeController::class, 'updateStatus'])->name('employees.status');

    // Customer Messages/Chat
    Route::get('customer-messages', [App\Http\Controllers\Shop\CustomerMessageController::class, 'index'])->name('customer-messages.index');
    Route::get('customer-messages/{customerId}', [App\Http\Controllers\Shop\CustomerMessageController::class, 'show'])->name('customer-messages.show');
    Route::post('customer-messages/{customerId}', [App\Http\Controllers\Shop\CustomerMessageController::class, 'send'])->name('customer-messages.send');

    // Withdraw Management
    Route::get('withdraws', [App\Http\Controllers\Shop\WithdrawController::class, 'index'])->name('withdraws.index');
    Route::get('withdraws/create', [App\Http\Controllers\Shop\WithdrawController::class, 'create'])->name('withdraws.create');
    Route::post('withdraws', [App\Http\Controllers\Shop\WithdrawController::class, 'store'])->name('withdraws.store');

    // Subscription Management (SaaS)
    Route::get('subscription', [App\Http\Controllers\Shop\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('subscription/plans', [App\Http\Controllers\Shop\SubscriptionController::class, 'plans'])->name('subscription.plans');
    Route::post('subscription/subscribe', [App\Http\Controllers\Shop\SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('subscription/cancel', [App\Http\Controllers\Shop\SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    // Notifications
    Route::get('notifications', [App\Http\Controllers\Shop\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-as-read', [App\Http\Controllers\Shop\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');

    // Shop Profile
    Route::get('profile', [App\Http\Controllers\Shop\ProfileController::class, 'index'])->name('profile');
    Route::post('profile', [App\Http\Controllers\Shop\ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/password', [App\Http\Controllers\Shop\ProfileController::class, 'updatePassword'])->name('profile.password');
});

// =============================================================================
// SELLER CHAT (Real-time chat between vendors and customers)
// =============================================================================
Route::prefix('seller')->name('seller.')->middleware(['authShop'])->group(function () {
    Route::get('chat', [App\Http\Controllers\Seller\SellerChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{customerId}', [App\Http\Controllers\Seller\SellerChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{customerId}/send', [App\Http\Controllers\Seller\SellerChatController::class, 'send'])->name('chat.send');
});

// =============================================================================
// DEFAULT ROUTE (Redirect to installer or login)
// =============================================================================
Route::get('/', function () {
    // If storage/installed exists, redirect to admin login
    if (file_exists(storage_path('installed'))) {
        return redirect()->route('admin.login');
    }
    // Otherwise, redirect to installer
    return redirect()->route('installer.welcome.index');
});
