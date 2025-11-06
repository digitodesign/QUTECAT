<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetShopContext Middleware
 *
 * Determines the current shop context based on:
 * 1. Subdomain (premium vendors)
 * 2. Query parameter (?shop_id=X)
 * 3. Header (X-Shop-ID)
 * 4. Session (for authenticated vendor)
 *
 * This enables our hybrid marketplace model where:
 * - Main domain (qutecart.com) shows ALL products
 * - Premium subdomains (johns-shop.qutecart.com) show ONLY that vendor's products
 */
class SetShopContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shopId = null;
        $tenant = null;

        // 1. Check if request is from a premium vendor subdomain
        if ($this->isSubdomainRequest($request)) {
            $tenant = $this->getTenantFromSubdomain($request);
            if ($tenant) {
                $shopId = $tenant->shop_id;

                // Store tenant in app container
                app()->instance('current_tenant', $tenant);
            }
        }

        // 2. Check for explicit shop_id parameter (API or testing)
        if (!$shopId && $request->has('shop_id')) {
            $shopId = (int) $request->get('shop_id');
        }

        // 3. Check for X-Shop-ID header (mobile app)
        if (!$shopId && $request->header('X-Shop-ID')) {
            $shopId = (int) $request->header('X-Shop-ID');
        }

        // 4. Check authenticated vendor's shop
        if (!$shopId && auth()->check() && auth()->user()->shop_id) {
            // Only set context if this is a vendor-specific route
            if ($this->isVendorRoute($request)) {
                $shopId = auth()->user()->shop_id;
            }
        }

        // Store shop context in app container
        if ($shopId) {
            app()->instance('current_shop_id', $shopId);

            // Add shop ID to request for easy access
            $request->merge(['_shop_context' => $shopId]);
        }

        // Continue to next middleware
        $response = $next($request);

        // Add context headers to response (useful for debugging)
        if ($shopId) {
            $response->headers->set('X-Shop-Context', $shopId);
        }

        if ($tenant) {
            $response->headers->set('X-Tenant-ID', $tenant->id);
            $response->headers->set('X-Tenant-Subdomain', $tenant->subdomain);
        }

        return $response;
    }

    /**
     * Check if the request is from a subdomain.
     *
     * @param Request $request
     * @return bool
     */
    protected function isSubdomainRequest(Request $request): bool
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // If the host is in central domains list, it's NOT a subdomain
        if (in_array($host, $centralDomains)) {
            return false;
        }

        // Check if host is a subdomain of our main domain
        $mainDomain = config('app.domain', 'qutecart.com');

        return str_ends_with($host, '.' . $mainDomain);
    }

    /**
     * Get tenant from subdomain.
     *
     * @param Request $request
     * @return Tenant|null
     */
    protected function getTenantFromSubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // Try to find tenant by domain
        $tenant = Tenant::whereHas('domains', function ($query) use ($host) {
            $query->where('domain', $host);
        })->first();

        return $tenant;
    }

    /**
     * Check if this is a vendor-specific route.
     *
     * @param Request $request
     * @return bool
     */
    protected function isVendorRoute(Request $request): bool
    {
        $path = $request->path();

        // List of vendor route prefixes
        $vendorPrefixes = [
            'vendor/',
            'seller/',
            'shop/dashboard',
            'api/vendor/',
        ];

        foreach ($vendorPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
