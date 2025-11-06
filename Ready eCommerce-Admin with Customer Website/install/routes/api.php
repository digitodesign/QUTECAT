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

// Subscription Management Routes
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

// Stripe Webhook (no authentication required - verified by signature)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripeWebhook'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['auth:sanctum', 'throttle']);
