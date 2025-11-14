<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDataColumn;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * Custom Tenant Model for QuteCart Hybrid Marketplace
 *
 * This model extends the base Tenant model but DOES NOT create separate databases.
 * Instead, it links to the Shop model and is used ONLY for subdomain routing.
 *
 * @property int $shop_id
 * @property string|null $subdomain
 * @property string $tier
 * @property \Carbon\Carbon|null $premium_since
 * @property \Carbon\Carbon|null $premium_expires_at
 * @property Shop $shop
 */
class Tenant extends BaseTenant
{
    use HasDataColumn, HasDomains;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'shop_id',
        'subdomain',
        'tier',
        'premium_since',
        'premium_expires_at',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shop_id' => 'integer',
        'premium_since' => 'datetime',
        'premium_expires_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Get the shop that owns this tenant.
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Check if the tenant has an active premium subscription.
     *
     * @return bool
     */
    public function isPremium(): bool
    {
        if ($this->tier === 'free') {
            return false;
        }

        if (!$this->premium_expires_at) {
            return true; // Lifetime or no expiration set
        }

        return $this->premium_expires_at->isFuture();
    }

    /**
     * Check if the premium subscription is expired.
     *
     * @return bool
     */
    public function isPremiumExpired(): bool
    {
        return $this->premium_expires_at && $this->premium_expires_at->isPast();
    }

    /**
     * Get the full subdomain URL.
     *
     * @return string|null
     */
    public function getSubdomainUrlAttribute(): ?string
    {
        if (!$this->subdomain) {
            return null;
        }

        $domain = config('app.domain', 'qutekart.com');
        return "https://{$this->subdomain}.{$domain}";
    }

    /**
     * Override run method to NOT switch database connections.
     * We use a single database for all tenants.
     *
     * @param callable $callback
     * @return mixed
     */
    public function run(callable $callback)
    {
        // Set the current shop context instead of switching databases
        $originalShopId = app('current_shop_id', null);

        app()->instance('current_shop_id', $this->shop_id);
        app()->instance('current_tenant', $this);

        try {
            return $callback($this);
        } finally {
            if ($originalShopId) {
                app()->instance('current_shop_id', $originalShopId);
            } else {
                app()->forgetInstance('current_shop_id');
            }
            app()->forgetInstance('current_tenant');
        }
    }

    /**
     * Create a new tenant for a shop (when upgrading to premium).
     *
     * @param Shop $shop
     * @param string $subdomain
     * @param string $tier
     * @return self
     */
    public static function createForShop(Shop $shop, string $subdomain, string $tier = 'starter'): self
    {
        $tenant = static::create([
            'shop_id' => $shop->id,
            'subdomain' => $subdomain,
            'tier' => $tier,
            'premium_since' => now(),
        ]);

        // Create the domain record for subdomain routing
        $domain = config('app.domain', 'qutekart.com');
        $tenant->domains()->create([
            'domain' => "{$subdomain}.{$domain}",
        ]);

        return $tenant;
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // When a tenant is created, update the shop
        static::created(function (Tenant $tenant) {
            if ($tenant->shop_id) {
                $tenant->shop->update([
                    'has_premium_subdomain' => true,
                ]);
            }
        });

        // When a tenant is deleted, update the shop
        static::deleted(function (Tenant $tenant) {
            if ($tenant->shop_id) {
                $tenant->shop->update([
                    'has_premium_subdomain' => false,
                ]);
            }
        });
    }
}
