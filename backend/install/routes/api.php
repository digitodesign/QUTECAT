<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// =============================================================================
// PUBLIC API ROUTES (No Authentication Required)
// =============================================================================

// DEBUG: Check filesystem
Route::get('/debug-files', function() {
    $publicPath = public_path();
    $buildPath = public_path('build');
    $manifestPath = public_path('build/manifest.json');

    return response()->json([
        'public_path' => $publicPath,
        'public_exists' => is_dir($publicPath),
        'build_path' => $buildPath,
        'build_exists' => is_dir($buildPath),
        'manifest_path' => $manifestPath,
        'manifest_exists' => file_exists($manifestPath),
        'build_files_count' => is_dir($buildPath) ? count(scandir($buildPath)) - 2 : 0,
        'working_dir' => getcwd(),
        'base_path' => base_path(),
    ]);
});

// Home & Master Data
Route::get('/home', [App\Http\Controllers\API\HomeController::class, 'index']);
Route::get('/master', [App\Http\Controllers\API\MasterController::class, 'index']);

// Language Translations
Route::get('/lang/{code}', function ($code) {
    $filePath = base_path("lang/{$code}.json");
    
    if (file_exists($filePath)) {
        $translations = json_decode(file_get_contents($filePath), true);
        return response()->json(['data' => $translations]);
    }
    
    return response()->json(['error' => 'Language file not found'], 404);
});

// Products
Route::get('/products', [App\Http\Controllers\API\ProductController::class, 'index']);
Route::get('/products/{product}', [App\Http\Controllers\API\ProductController::class, 'show']);
Route::get('/product-details', [App\Http\Controllers\API\ProductController::class, 'show']);

// Categories
Route::get('/categories', [App\Http\Controllers\API\CategoryController::class, 'index']);
Route::get('/categories/{category}', [App\Http\Controllers\API\CategoryController::class, 'show']);

// Sub-categories
Route::get('/sub-categories', [App\Http\Controllers\API\SubCategoryController::class, 'index']);
Route::get('/sub-categories/{subCategory}', [App\Http\Controllers\API\SubCategoryController::class, 'show']);

// Shops
Route::get('/shops', [App\Http\Controllers\API\ShopController::class, 'index']);
Route::get('/shops/{shop}', [App\Http\Controllers\API\ShopController::class, 'show']);

// Banners
Route::get('/banners', [App\Http\Controllers\API\BannerController::class, 'index']);

// Blogs
Route::get('/blogs', [App\Http\Controllers\API\BlogController::class, 'index']);
Route::get('/blogs/{blog}', [App\Http\Controllers\API\BlogController::class, 'show']);

// Flash Sales
Route::get('/flash-sales', [App\Http\Controllers\API\FlashSaleController::class, 'index']);
Route::get('/flash-sales/{flashSale}', [App\Http\Controllers\API\FlashSaleController::class, 'show']);

// Countries
Route::get('/countries', [App\Http\Controllers\API\CountryController::class, 'index']);

// Legal Pages
Route::get('/legal-pages', [App\Http\Controllers\API\LegalPageController::class, 'index']);
Route::get('/legal-pages/{page}', [App\Http\Controllers\API\LegalPageController::class, 'show']);

// Support & Ticket Types
Route::get('/support', [App\Http\Controllers\API\SupportController::class, 'index']);
Route::get('/ticket-issue-types', [App\Http\Controllers\API\TicketIssueTypeController::class, 'index']);

// =============================================================================
// CUSTOMER AUTHENTICATION
// =============================================================================
Route::prefix('auth')->group(function () {
    // Registration & Login
    Route::post('/register', [App\Http\Controllers\API\Auth\AuthController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\API\Auth\AuthController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\API\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Social Authentication
    Route::post('/social-login', [App\Http\Controllers\API\SocialAuthController::class, 'login']);

    // Password Reset
    Route::post('/forgot-password', [App\Http\Controllers\API\Auth\ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/reset-password', [App\Http\Controllers\API\Auth\ForgotPasswordController::class, 'reset']);

    // OTP Verification
    Route::post('/verify-otp', [App\Http\Controllers\API\Auth\AuthController::class, 'verifyOTP']);
    Route::post('/resend-otp', [App\Http\Controllers\API\Auth\AuthController::class, 'resendOTP']);
});

// =============================================================================
// CUSTOMER ROUTES (Requires Authentication)
// =============================================================================
Route::middleware(['auth:sanctum'])->group(function () {

    // User Profile
    Route::get('/user', [App\Http\Controllers\API\UserController::class, 'show']);
    Route::post('/user/update', [App\Http\Controllers\API\UserController::class, 'update']);
    Route::post('/user/update-password', [App\Http\Controllers\API\UserController::class, 'updatePassword']);
    Route::post('/user/update-avatar', [App\Http\Controllers\API\UserController::class, 'updateAvatar']);
    Route::delete('/user/delete', [App\Http\Controllers\API\UserController::class, 'destroy']);

    // Addresses
    Route::get('/addresses', [App\Http\Controllers\API\AddressController::class, 'index']);
    Route::post('/addresses', [App\Http\Controllers\API\AddressController::class, 'store']);
    Route::put('/addresses/{address}', [App\Http\Controllers\API\AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [App\Http\Controllers\API\AddressController::class, 'destroy']);
    Route::post('/addresses/{address}/set-default', [App\Http\Controllers\API\AddressController::class, 'setDefault']);

    // Cart
    Route::get('/cart', [App\Http\Controllers\API\CartController::class, 'index']);
    Route::post('/cart', [App\Http\Controllers\API\CartController::class, 'store']);
    Route::put('/cart/{cart}', [App\Http\Controllers\API\CartController::class, 'update']);
    Route::delete('/cart/{cart}', [App\Http\Controllers\API\CartController::class, 'destroy']);
    Route::delete('/cart', [App\Http\Controllers\API\CartController::class, 'clear']);

    // Orders
    Route::get('/orders', [App\Http\Controllers\API\OrderController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\API\OrderController::class, 'store']);
    Route::get('/orders/{order}', [App\Http\Controllers\API\OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\API\OrderController::class, 'cancel']);
    Route::get('/orders/{order}/invoice', [App\Http\Controllers\API\OrderController::class, 'invoice']);

    // Return Orders
    Route::get('/return-orders', [App\Http\Controllers\API\ReturnOrderController::class, 'index']);
    Route::post('/return-orders', [App\Http\Controllers\API\ReturnOrderController::class, 'store']);
    Route::get('/return-orders/{order}', [App\Http\Controllers\API\ReturnOrderController::class, 'show']);

    // Reviews
    Route::post('/products/{product}/reviews', [App\Http\Controllers\API\ReviewController::class, 'store']);
    Route::get('/products/{product}/reviews', [App\Http\Controllers\API\ReviewController::class, 'index']);
    Route::put('/reviews/{review}', [App\Http\Controllers\API\ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [App\Http\Controllers\API\ReviewController::class, 'destroy']);

    // Favorites/Wishlist
    Route::get('/favorites', [App\Http\Controllers\API\ProductController::class, 'favorites']);
    Route::post('/products/{product}/favorite', [App\Http\Controllers\API\ProductController::class, 'addFavorite']);
    Route::delete('/products/{product}/favorite', [App\Http\Controllers\API\ProductController::class, 'removeFavorite']);

    // Coupons
    Route::post('/coupons/apply', [App\Http\Controllers\API\CouponController::class, 'apply']);
    Route::get('/coupons', [App\Http\Controllers\API\CouponController::class, 'index']);

    // Product License (for digital products)
    Route::get('/product-licenses', [App\Http\Controllers\API\ProductLicenseController::class, 'index']);
    Route::get('/product-licenses/{license}', [App\Http\Controllers\API\ProductLicenseController::class, 'show']);
    Route::get('/product-licenses/{license}/download', [App\Http\Controllers\API\ProductLicenseController::class, 'download']);

    // Chat
    Route::get('/chat', [App\Http\Controllers\API\ChatController::class, 'index']);
    Route::get('/chat/{shop}', [App\Http\Controllers\API\ChatController::class, 'show']);
    Route::post('/chat/{shop}/send', [App\Http\Controllers\API\ChatController::class, 'send']);

    // Support Tickets
    Route::get('/support-tickets', [App\Http\Controllers\API\SupportTicketController::class, 'index']);
    Route::post('/support-tickets', [App\Http\Controllers\API\SupportTicketController::class, 'store']);
    Route::get('/support-tickets/{ticket}', [App\Http\Controllers\API\SupportTicketController::class, 'show']);
    Route::post('/support-tickets/{ticket}/messages', [App\Http\Controllers\API\SupportTicketMessageController::class, 'store']);
});

// =============================================================================
// RIDER/DELIVERY ROUTES
// =============================================================================
Route::prefix('rider')->group(function () {
    // Rider Authentication
    Route::post('/login', [App\Http\Controllers\API\Rider\LoginController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\API\Rider\LoginController::class, 'logout'])->middleware('auth:sanctum');

    // Authenticated Rider Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Profile
        Route::get('/profile', [App\Http\Controllers\API\Rider\UserController::class, 'show']);
        Route::post('/profile/update', [App\Http\Controllers\API\Rider\UserController::class, 'update']);
        Route::post('/profile/update-password', [App\Http\Controllers\API\Rider\UserController::class, 'updatePassword']);

        // Orders
        Route::get('/orders', [App\Http\Controllers\API\Rider\OrderController::class, 'index']);
        Route::get('/orders/{order}', [App\Http\Controllers\API\Rider\OrderController::class, 'show']);
        Route::post('/orders/{order}/accept', [App\Http\Controllers\API\Rider\OrderController::class, 'accept']);
        Route::post('/orders/{order}/pickup', [App\Http\Controllers\API\Rider\OrderController::class, 'pickup']);
        Route::post('/orders/{order}/deliver', [App\Http\Controllers\API\Rider\OrderController::class, 'deliver']);
        Route::post('/orders/{order}/cancel', [App\Http\Controllers\API\Rider\OrderController::class, 'cancel']);

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\API\Rider\NotificationController::class, 'index']);
        Route::post('/notifications/mark-as-read', [App\Http\Controllers\API\Rider\NotificationController::class, 'markAsRead']);
    });
});

// =============================================================================
// SELLER/VENDOR API ROUTES
// =============================================================================
Route::prefix('seller')->group(function () {
    // Seller Authentication
    Route::post('/login', [App\Http\Controllers\API\Seller\LoginController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\API\Seller\LoginController::class, 'logout'])->middleware('auth:sanctum');

    // Authenticated Seller Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\API\Seller\DashboardController::class, 'index']);

        // Profile
        Route::get('/profile', [App\Http\Controllers\API\Seller\UserController::class, 'show']);
        Route::post('/profile/update', [App\Http\Controllers\API\Seller\UserController::class, 'update']);
        Route::post('/profile/update-password', [App\Http\Controllers\API\Seller\UserController::class, 'updatePassword']);

        // Products
        Route::get('/products', [App\Http\Controllers\API\Seller\ProductController::class, 'index']);
        Route::post('/products', [App\Http\Controllers\API\Seller\ProductController::class, 'store']);
        Route::get('/products/{product}', [App\Http\Controllers\API\Seller\ProductController::class, 'show']);
        Route::put('/products/{product}', [App\Http\Controllers\API\Seller\ProductController::class, 'update']);
        Route::delete('/products/{product}', [App\Http\Controllers\API\Seller\ProductController::class, 'destroy']);
        Route::post('/products/{product}/status', [App\Http\Controllers\API\Seller\ProductController::class, 'updateStatus']);

        // Orders
        Route::get('/orders', [App\Http\Controllers\API\Seller\OrderController::class, 'index']);
        Route::get('/orders/{order}', [App\Http\Controllers\API\Seller\OrderController::class, 'show']);
        Route::post('/orders/{order}/status', [App\Http\Controllers\API\Seller\OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/assign-rider', [App\Http\Controllers\API\Seller\OrderController::class, 'assignRider']);

        // Return Orders
        Route::get('/return-orders', [App\Http\Controllers\API\Seller\ReturnOrderController::class, 'index']);
        Route::get('/return-orders/{order}', [App\Http\Controllers\API\Seller\ReturnOrderController::class, 'show']);
        Route::post('/return-orders/{order}/status', [App\Http\Controllers\API\Seller\ReturnOrderController::class, 'updateStatus']);

        // Banners
        Route::get('/banners', [App\Http\Controllers\API\Seller\BannerController::class, 'index']);
        Route::post('/banners', [App\Http\Controllers\API\Seller\BannerController::class, 'store']);
        Route::put('/banners/{banner}', [App\Http\Controllers\API\Seller\BannerController::class, 'update']);
        Route::delete('/banners/{banner}', [App\Http\Controllers\API\Seller\BannerController::class, 'destroy']);

        // Wallet
        Route::get('/wallet', [App\Http\Controllers\API\Seller\WalletController::class, 'index']);
        Route::get('/wallet/transactions', [App\Http\Controllers\API\Seller\WalletController::class, 'transactions']);

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\API\Seller\NotificationController::class, 'index']);
        Route::post('/notifications/mark-as-read', [App\Http\Controllers\API\Seller\NotificationController::class, 'markAsRead']);
    });
});

// =============================================================================
// SUBSCRIPTION MANAGEMENT ROUTES (SaaS)
// =============================================================================
Route::prefix('subscription')->middleware(['auth:sanctum'])->group(function () {
    // Public plan listing (no auth required)
    Route::get('/plans', [SubscriptionController::class, 'plans'])->withoutMiddleware('auth:sanctum');

    // Current subscription details
    Route::get('/current', [SubscriptionController::class, 'current']);

    // Subscribe to a plan
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);

    // Upgrade to higher plan
    Route::post('/upgrade', [SubscriptionController::class, 'upgrade']);

    // Downgrade to lower plan
    Route::post('/downgrade', [SubscriptionController::class, 'downgrade']);

    // Cancel subscription
    Route::post('/cancel', [SubscriptionController::class, 'cancel']);

    // Resume canceled subscription
    Route::post('/resume', [SubscriptionController::class, 'resume']);

    // Usage statistics
    Route::get('/usage', [SubscriptionController::class, 'usage']);

    // Subscription history
    Route::get('/history', [SubscriptionController::class, 'history']);

    // Stripe billing portal
    Route::get('/billing-portal', [SubscriptionController::class, 'billingPortal']);
});

// =============================================================================
// WEBHOOKS
// =============================================================================
// Stripe Webhook (no authentication required - verified by signature)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripeWebhook'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['auth:sanctum', 'throttle']);
