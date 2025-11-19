<?php

namespace App\Http\Controllers\API\Traits;

use App\Models\Shop;

/**
 * ContextAware Trait
 *
 * Provides context-aware filtering for API controllers.
 * Automatically filters queries based on the current shop context.
 */
trait ContextAware
{
    /**
     * Get the current shop ID from context.
     *
     * Priority:
     * 1. SetShopContext middleware (subdomain, header, query param)
     * 2. Request shop_id parameter (backward compatibility)
     * 3. Single shop mode setting
     * 4. null (marketplace mode - show all)
     *
     * @param \Illuminate\Http\Request|null $request
     * @return int|null
     */
    protected function getCurrentShopId($request = null): ?int
    {
        // 1. Check context from middleware (subdomain, header, session)
        $shopId = app()->bound('current_shop_id') ? app('current_shop_id') : null;

        if ($shopId) {
            return $shopId;
        }

        // 2. Check request parameter (backward compatibility for mobile app)
        if ($request && $request->has('shop_id')) {
            return (int) $request->get('shop_id');
        }

        // 3. Check if single shop mode
        $generaleSetting = generaleSetting('setting');
        if ($generaleSetting?->shop_type == 'single') {
            $rootShop = generaleSetting('rootShop');
            return $rootShop?->id;
        }

        // 4. Marketplace mode - no specific shop
        return null;
    }

    /**
     * Apply shop context filter to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request|null $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyShopContext($query, $request = null)
    {
        $shopId = $this->getCurrentShopId($request);

        if ($shopId) {
            return $query->where('shop_id', $shopId);
        }

        // Marketplace mode - show all active shops
        return $query->whereHas('shop', function ($q) {
            $q->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('is_active', 1);
                });
            });
        });
    }

    /**
     * Check if we're in shop-specific context.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    protected function isShopContext($request = null): bool
    {
        return $this->getCurrentShopId($request) !== null;
    }

    /**
     * Check if we're in marketplace mode (showing all shops).
     *
     * @param \Illuminate\Http\Request|null $request
     * @return bool
     */
    protected function isMarketplaceMode($request = null): bool
    {
        return !$this->isShopContext($request);
    }

    /**
     * Get the current shop model if in shop context.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return Shop|null
     */
    protected function getCurrentShop($request = null): ?Shop
    {
        $shopId = $this->getCurrentShopId($request);

        if (!$shopId) {
            return null;
        }

        return Shop::find($shopId);
    }

    /**
     * Get the context description for logging/debugging.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return string
     */
    protected function getContextDescription($request = null): string
    {
        $shopId = $this->getCurrentShopId($request);

        if (!$shopId) {
            return 'marketplace';
        }

        $tenant = app('current_tenant');

        if ($tenant) {
            return "premium_subdomain:{$tenant->subdomain}";
        }

        return "shop:{$shopId}";
    }
}
