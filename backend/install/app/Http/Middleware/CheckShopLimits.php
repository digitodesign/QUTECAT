<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckShopLimits Middleware
 *
 * Enforces subscription limits before allowing actions.
 *
 * Usage in routes:
 * Route::post('/products', ...)->middleware('check.limits:products');
 * Route::post('/upload', ...)->middleware('check.limits:storage');
 * Route::post('/orders', ...)->middleware('check.limits:orders');
 */
class CheckShopLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $limit  The limit type to check (products|orders|storage)
     */
    public function handle(Request $request, Closure $next, string $limit): Response
    {
        // Get current shop from context
        $shopId = app('current_shop_id');

        if (!$shopId) {
            // No shop context, allow (marketplace browsing)
            return $next($request);
        }

        $shop = Shop::find($shopId);

        if (!$shop) {
            return response()->json([
                'error' => 'Shop not found',
            ], 404);
        }

        // Check if hard limits are enforced
        $hardLimits = config('saas.enforcement.hard_limits', true);

        if (!$hardLimits) {
            // Soft limits - just warn but allow
            return $next($request);
        }

        // Check the specific limit
        switch ($limit) {
            case 'products':
                if ($shop->hasExceededProductsLimit()) {
                    return $this->limitExceededResponse($shop, 'products', [
                        'limit' => $shop->products_limit,
                        'current' => $shop->products_count,
                        'remaining' => $shop->remaining_products,
                        'upgrade_url' => route('subscription.plans'),
                    ]);
                }
                break;

            case 'orders':
                if ($shop->hasExceededOrdersLimit()) {
                    return $this->limitExceededResponse($shop, 'orders', [
                        'limit' => $shop->orders_per_month_limit,
                        'current' => $shop->orders_this_month,
                        'remaining' => $shop->remaining_orders,
                        'upgrade_url' => route('subscription.plans'),
                    ]);
                }
                break;

            case 'storage':
                // Check if uploading file
                if ($request->hasFile('file') || $request->hasFile('image')) {
                    $files = $request->allFiles();
                    $uploadSize = 0;

                    foreach ($files as $file) {
                        if (is_array($file)) {
                            foreach ($file as $f) {
                                $uploadSize += $f->getSize() / 1024 / 1024; // Convert to MB
                            }
                        } else {
                            $uploadSize += $file->getSize() / 1024 / 1024; // Convert to MB
                        }
                    }

                    $futureStorage = $shop->storage_used_mb + $uploadSize;

                    if ($shop->storage_limit_mb && $futureStorage > $shop->storage_limit_mb) {
                        return $this->limitExceededResponse($shop, 'storage', [
                            'limit_mb' => $shop->storage_limit_mb,
                            'current_mb' => $shop->storage_used_mb,
                            'upload_size_mb' => round($uploadSize, 2),
                            'remaining_mb' => $shop->remaining_storage,
                            'upgrade_url' => route('subscription.plans'),
                        ]);
                    }
                }
                break;

            default:
                // Unknown limit type, allow
                break;
        }

        // Check if approaching limit (warning)
        $this->checkApproachingLimits($shop, $limit);

        return $next($request);
    }

    /**
     * Return limit exceeded response.
     *
     * @param Shop $shop
     * @param string $limitType
     * @param array $details
     * @return Response
     */
    protected function limitExceededResponse(Shop $shop, string $limitType, array $details): Response
    {
        $messages = [
            'products' => 'Product limit reached',
            'orders' => 'Monthly order limit reached',
            'storage' => 'Storage limit exceeded',
        ];

        $message = $messages[$limitType] ?? 'Limit exceeded';

        // Add grace period info if applicable
        if (config('saas.enforcement.grace_buffer_percent')) {
            $gracePercent = config('saas.enforcement.grace_buffer_percent');
            $message .= " (grace buffer: {$gracePercent}% allowed)";
        }

        return response()->json([
            'error' => $message,
            'limit_type' => $limitType,
            'details' => $details,
            'current_plan' => $shop->plan?->name ?? 'Free',
            'message' => 'Upgrade your plan to increase limits',
        ], 403);
    }

    /**
     * Check if approaching limits and fire warning events.
     *
     * @param Shop $shop
     * @param string $limitType
     * @return void
     */
    protected function checkApproachingLimits(Shop $shop, string $limitType): void
    {
        $thresholds = config('saas.usage_tracking.warning_thresholds', []);

        switch ($limitType) {
            case 'products':
                $threshold = $thresholds['products'] ?? 80;
                if ($shop->products_usage_percent >= $threshold && $shop->products_usage_percent < 100) {
                    // Fire event for warning notification
                    event(new \App\Events\UsageLimitApproaching($shop, 'products', $shop->products_usage_percent));
                }
                break;

            case 'orders':
                $threshold = $thresholds['orders'] ?? 90;
                if ($shop->orders_usage_percent >= $threshold && $shop->orders_usage_percent < 100) {
                    event(new \App\Events\UsageLimitApproaching($shop, 'orders', $shop->orders_usage_percent));
                }
                break;

            case 'storage':
                $threshold = $thresholds['storage'] ?? 85;
                if ($shop->storage_usage_percent >= $threshold && $shop->storage_usage_percent < 100) {
                    event(new \App\Events\UsageLimitApproaching($shop, 'storage', $shop->storage_usage_percent));
                }
                break;
        }
    }

    /**
     * Check if shop has grace period active.
     *
     * @param Shop $shop
     * @return bool
     */
    protected function hasGracePeriod(Shop $shop): bool
    {
        if (!config('saas.enforcement.grace_period_hours')) {
            return false;
        }

        // Check if subscription recently expired or downgraded
        if ($shop->subscription_ends_at &&
            $shop->subscription_ends_at->isPast() &&
            $shop->subscription_ends_at->diffInHours(now()) < config('saas.enforcement.grace_period_hours')) {
            return true;
        }

        return false;
    }
}
