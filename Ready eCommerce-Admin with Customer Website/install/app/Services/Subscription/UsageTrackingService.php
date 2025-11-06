<?php

namespace App\Services\Subscription;

use App\Models\Shop;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * UsageTrackingService
 *
 * Tracks and reports vendor resource usage.
 */
class UsageTrackingService
{
    /**
     * Track product creation.
     *
     * @param Shop $shop
     * @return bool
     * @throws \Exception
     */
    public function trackProductCreation(Shop $shop): bool
    {
        if ($shop->hasExceededProductsLimit()) {
            throw new \Exception("Product limit reached ({$shop->products_limit})");
        }

        $shop->incrementProductsCount();

        // Check if approaching limit
        $this->checkApproachingLimit($shop, 'products');

        Log::info('Product created', [
            'shop_id' => $shop->id,
            'products_count' => $shop->products_count,
            'products_limit' => $shop->products_limit,
            'usage_percent' => $shop->products_usage_percent,
        ]);

        return true;
    }

    /**
     * Track product deletion.
     *
     * @param Shop $shop
     * @return void
     */
    public function trackProductDeletion(Shop $shop): void
    {
        $shop->decrementProductsCount();

        Log::info('Product deleted', [
            'shop_id' => $shop->id,
            'products_count' => $shop->products_count,
        ]);
    }

    /**
     * Track order creation.
     *
     * @param Order $order
     * @return bool
     */
    public function trackOrder(Order $order): bool
    {
        if (!$order->shop_id) {
            return true; // Marketplace order, no tracking needed
        }

        $shop = $order->shop;

        if ($shop->hasExceededOrdersLimit()) {
            Log::warning('Shop exceeded order limit', [
                'shop_id' => $shop->id,
                'orders_this_month' => $shop->orders_this_month,
                'limit' => $shop->orders_per_month_limit,
            ]);
            return false;
        }

        $shop->incrementOrdersCount();

        // Check if approaching limit
        $this->checkApproachingLimit($shop, 'orders');

        Log::info('Order tracked', [
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'orders_this_month' => $shop->orders_this_month,
            'limit' => $shop->orders_per_month_limit,
        ]);

        return true;
    }

    /**
     * Calculate and update storage usage for a shop.
     *
     * @param Shop $shop
     * @return int Storage used in MB
     */
    public function calculateStorageUsage(Shop $shop): int
    {
        $totalBytes = 0;

        // Get all media files for this shop's products
        $products = $shop->products()->with('media')->get();

        foreach ($products as $product) {
            // Product thumbnails
            if ($product->mediaLogo && Storage::exists($product->mediaLogo->src)) {
                $totalBytes += Storage::size($product->mediaLogo->src);
            }

            // Product images
            foreach ($product->thumbnails as $thumbnail) {
                if ($thumbnail->media && Storage::exists($thumbnail->media->src)) {
                    $totalBytes += Storage::size($thumbnail->media->src);
                }
            }
        }

        // Shop logo and banner
        if ($shop->mediaLogo && Storage::exists($shop->mediaLogo->src)) {
            $totalBytes += Storage::size($shop->mediaLogo->src);
        }

        if ($shop->mediaBanner && Storage::exists($shop->mediaBanner->src)) {
            $totalBytes += Storage::size($shop->mediaBanner->src);
        }

        // Shop gallery images
        foreach ($shop->galleries as $gallery) {
            if ($gallery->media && Storage::exists($gallery->media->src)) {
                $totalBytes += Storage::size($gallery->media->src);
            }
        }

        // Convert to MB
        $storageMB = (int) ceil($totalBytes / 1024 / 1024);

        // Update shop
        $shop->update([
            'storage_used_mb' => $storageMB,
        ]);

        Log::info('Storage usage calculated', [
            'shop_id' => $shop->id,
            'storage_mb' => $storageMB,
            'limit_mb' => $shop->storage_limit_mb,
        ]);

        return $storageMB;
    }

    /**
     * Reset monthly usage counters for all shops.
     *
     * This should be run as a scheduled task on the first day of each month.
     *
     * @return int Number of shops reset
     */
    public function resetMonthlyUsage(): int
    {
        $count = Shop::query()
            ->where('orders_this_month', '>', 0)
            ->update([
                'orders_this_month' => 0,
                'last_usage_reset_at' => now(),
            ]);

        Log::info('Monthly usage reset', [
            'shops_reset' => $count,
            'date' => now()->toDateString(),
        ]);

        return $count;
    }

    /**
     * Reset monthly usage for a specific shop.
     *
     * @param Shop $shop
     * @return void
     */
    public function resetShopMonthlyUsage(Shop $shop): void
    {
        $shop->resetMonthlyUsage();

        Log::info('Shop monthly usage reset', [
            'shop_id' => $shop->id,
        ]);
    }

    /**
     * Get comprehensive usage report for a shop.
     *
     * @param Shop $shop
     * @return array
     */
    public function getUsageReport(Shop $shop): array
    {
        // Refresh storage calculation
        $this->calculateStorageUsage($shop);
        $shop->refresh();

        return [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'plan' => $shop->plan?->name ?? 'Free',
                'plan_slug' => $shop->plan?->slug ?? 'free',
            ],
            'products' => [
                'current' => $shop->products_count,
                'limit' => $shop->products_limit,
                'remaining' => $shop->remaining_products,
                'percent' => round($shop->products_usage_percent, 1),
                'status' => $this->getUsageStatus($shop->products_usage_percent),
            ],
            'orders' => [
                'current' => $shop->orders_this_month,
                'limit' => $shop->orders_per_month_limit,
                'remaining' => $shop->remaining_orders,
                'percent' => round($shop->orders_usage_percent, 1),
                'status' => $this->getUsageStatus($shop->orders_usage_percent),
                'resets_at' => now()->endOfMonth()->toDateString(),
            ],
            'storage' => [
                'current_mb' => $shop->storage_used_mb,
                'limit_mb' => $shop->storage_limit_mb,
                'remaining_mb' => $shop->remaining_storage,
                'percent' => round($shop->storage_usage_percent, 1),
                'status' => $this->getUsageStatus($shop->storage_usage_percent),
                'current_formatted' => $this->formatBytes($shop->storage_used_mb * 1024 * 1024),
                'limit_formatted' => $this->formatBytes($shop->storage_limit_mb * 1024 * 1024),
            ],
            'subscription' => [
                'status' => $shop->subscription_status,
                'trial_ends_at' => $shop->trial_ends_at?->toDateString(),
                'subscription_ends_at' => $shop->subscription_ends_at?->toDateString(),
            ],
        ];
    }

    /**
     * Get usage statistics for all shops.
     *
     * @return array
     */
    public function getGlobalUsageStats(): array
    {
        return [
            'total_shops' => Shop::count(),
            'active_subscriptions' => Shop::premium()->count(),
            'free_tier_shops' => Shop::freeTier()->count(),
            'total_products' => Product::count(),
            'total_orders_this_month' => Order::whereMonth('created_at', now()->month)->count(),
            'average_usage' => [
                'products_percent' => Shop::avg(DB::raw('(products_count / NULLIF(products_limit, 0)) * 100')),
                'orders_percent' => Shop::avg(DB::raw('(orders_this_month / NULLIF(orders_per_month_limit, 0)) * 100')),
                'storage_percent' => Shop::avg(DB::raw('(storage_used_mb / NULLIF(storage_limit_mb, 0)) * 100')),
            ],
        ];
    }

    /**
     * Check if shop is approaching usage limit.
     *
     * @param Shop $shop
     * @param string $type
     * @return void
     */
    protected function checkApproachingLimit(Shop $shop, string $type): void
    {
        $thresholds = config('saas.usage_tracking.warning_thresholds', []);
        $threshold = $thresholds[$type] ?? 80;

        $percent = match($type) {
            'products' => $shop->products_usage_percent,
            'orders' => $shop->orders_usage_percent,
            'storage' => $shop->storage_usage_percent,
            default => 0,
        };

        if ($percent >= $threshold && $percent < 100) {
            // Fire event for notification
            // event(new UsageLimitApproaching($shop, $type, $percent));

            Log::warning('Shop approaching usage limit', [
                'shop_id' => $shop->id,
                'type' => $type,
                'percent' => $percent,
                'threshold' => $threshold,
            ]);
        }

        if ($percent >= 100) {
            // Fire event for limit exceeded
            // event(new UsageLimitExceeded($shop, $type));

            Log::error('Shop exceeded usage limit', [
                'shop_id' => $shop->id,
                'type' => $type,
                'percent' => $percent,
            ]);
        }
    }

    /**
     * Get usage status string.
     *
     * @param float $percent
     * @return string
     */
    protected function getUsageStatus(float $percent): string
    {
        if ($percent >= 100) {
            return 'exceeded';
        } elseif ($percent >= 90) {
            return 'critical';
        } elseif ($percent >= 75) {
            return 'warning';
        } elseif ($percent >= 50) {
            return 'moderate';
        } else {
            return 'good';
        }
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Synchronize product counts for all shops.
     *
     * Run this periodically to ensure accuracy.
     *
     * @return array
     */
    public function syncProductCounts(): array
    {
        $shops = Shop::all();
        $updated = 0;

        foreach ($shops as $shop) {
            $actualCount = $shop->products()->count();

            if ($shop->products_count !== $actualCount) {
                $shop->update(['products_count' => $actualCount]);
                $updated++;

                Log::info('Product count synced', [
                    'shop_id' => $shop->id,
                    'old_count' => $shop->products_count,
                    'new_count' => $actualCount,
                ]);
            }
        }

        return [
            'total_shops' => $shops->count(),
            'updated_shops' => $updated,
        ];
    }

    /**
     * Recalculate storage for all shops.
     *
     * Run this periodically or on-demand.
     *
     * @return array
     */
    public function recalculateAllStorage(): array
    {
        $shops = Shop::all();
        $totalStorage = 0;

        foreach ($shops as $shop) {
            $storage = $this->calculateStorageUsage($shop);
            $totalStorage += $storage;
        }

        return [
            'total_shops' => $shops->count(),
            'total_storage_mb' => $totalStorage,
            'total_storage_gb' => round($totalStorage / 1024, 2),
        ];
    }
}
