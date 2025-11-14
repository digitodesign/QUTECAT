<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the shop user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get existing shop subscriptions (legacy).
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class);
    }

    /**
     * Get the tenant for this shop (if premium vendor).
     */
    public function tenant(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get the current premium tenant (if exists).
     */
    public function currentTenant(): Attribute
    {
        $tenant = $this->tenant()
            ->where('tier', '!=', 'free')
            ->where(function ($q) {
                $q->whereNull('premium_expires_at')
                    ->orWhere('premium_expires_at', '>', now());
            })
            ->first();

        return new Attribute(
            get: fn() => $tenant,
        );
    }

    /**
     * Get the current subscription plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    /**
     * get emploees for this shop
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'shop_id');
    }

    /**
     * get withdraw model for this user.
     */
    public function withdraws(): HasMany
    {
        return $this->hasMany(Withdraw::class, 'shop_id');
    }

    /**
     * Get the logo media for the Shop.
     */
    public function mediaLogo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    /**
     * Retrieve the media banner for this instance.
     */
    public function mediaBanner(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_id');
    }

    /**
     * get all gallery images for this shop
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class, 'shop_id');
    }

    /**
     * Get the logo for the Shop as an attribute.
     */
    public function logo(): Attribute
    {
        $logo = asset('default/default.jpg');
        if ($this->mediaLogo && Storage::exists($this->mediaLogo->src)) {
            $logo = Storage::url($this->mediaLogo->src);
        }

        return Attribute::make(
            get: fn() => $logo
        );
    }

    /**
     * Get the banner for the Shop as an attribute.
     */
    public function banner(): Attribute
    {
        $banner = asset('default/default.jpg');
        if ($this->mediaBanner && Storage::exists($this->mediaBanner->src)) {
            $banner = Storage::url($this->mediaBanner->src);
        }

        return Attribute::make(
            get: fn() => $banner
        );
    }

    /**
     * Get all of the products for the Shop.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Retrieve the categories associated with the shop.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'shop_categories');
    }

    /**
     * Retrieve the sub categories associated with the shop.
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    /**
     * get all of the brands for the shop.
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * Get all of the coupons for the Shop.
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get all of the colors for the Shop.
     */
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }

    /**
     * Get the sizes for the shop.
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(Size::class, 'shop_id');
    }

    /**
     * Get all of the units for the Shop.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get all of the orders for the Shop.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all of the banners for the Shop.
     */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class, 'shop_id');
    }

    /**
     * Scope a query to only include active shops.
     *
     * @param  Builder  $builder  The query builder
     * @return mixed
     */
    public function scopeIsActive(Builder $builder)
    {
        return $builder->whereHas('user', function ($query) {
            $query->where('is_active', 1);
        });
    }

    /**
     * Get all of the reviews for the Shop.
     *
     * @return HasMany.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'shop_id');
    }

    public function currentSubscription(): Attribute
    {
        $subscription = $this->subscriptions()->where('status', SubscriptionStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('remaining_sales')
                    ->orWhere('remaining_sales', '>', 0);
            })
            ->first();

        return new Attribute(
            get: fn() => $subscription,
        );
    }

    /**
     * Calculates the average rating of the reviews.
     *
     * @return Attribute The average rating attribute.
     */
    public function averageRating(): Attribute
    {
        $avgRating = $this->reviews()->avg('rating');

        return new Attribute(
            get: fn() => (float) number_format($avgRating > 0 ? $avgRating : 5, 1, '.', ''),
        );
    }

    public function returnOrders(): HasMany
    {
        return $this->hasMany(ReturnOrder::class);
    }

    /**
     * =======================================================================
     * SaaS / Premium Vendor Methods
     * =======================================================================
     */

    /**
     * Check if this shop has a premium subdomain.
     *
     * @return bool
     */
    public function hasPremiumSubdomain(): bool
    {
        return $this->has_premium_subdomain === true && $this->currentTenant !== null;
    }

    /**
     * Check if this shop is on a free tier.
     *
     * @return bool
     */
    public function isFreeTier(): bool
    {
        return $this->current_plan_id === null || $this->plan?->isFree();
    }

    /**
     * Check if this shop is on a paid tier.
     *
     * @return bool
     */
    public function isPremium(): bool
    {
        return !$this->isFreeTier() && $this->subscription_status === 'active';
    }

    /**
     * Check if shop has exceeded products limit.
     *
     * @return bool
     */
    public function hasExceededProductsLimit(): bool
    {
        return $this->products_count >= $this->products_limit;
    }

    /**
     * Check if shop has exceeded orders limit this month.
     *
     * @return bool
     */
    public function hasExceededOrdersLimit(): bool
    {
        return $this->orders_this_month >= $this->orders_per_month_limit;
    }

    /**
     * Check if shop has exceeded storage limit.
     *
     * @return bool
     */
    public function hasExceededStorageLimit(): bool
    {
        return $this->storage_used_mb >= $this->storage_limit_mb;
    }

    /**
     * Get remaining products allowed.
     *
     * @return int
     */
    public function getRemainingProductsAttribute(): int
    {
        return max(0, $this->products_limit - $this->products_count);
    }

    /**
     * Get remaining orders allowed this month.
     *
     * @return int
     */
    public function getRemainingOrdersAttribute(): int
    {
        return max(0, $this->orders_per_month_limit - $this->orders_this_month);
    }

    /**
     * Get remaining storage in MB.
     *
     * @return int
     */
    public function getRemainingStorageAttribute(): int
    {
        return max(0, $this->storage_limit_mb - $this->storage_used_mb);
    }

    /**
     * Get usage percentage for products.
     *
     * @return float
     */
    public function getProductsUsagePercentAttribute(): float
    {
        if ($this->products_limit <= 0) {
            return 0;
        }

        return min(100, ($this->products_count / $this->products_limit) * 100);
    }

    /**
     * Get usage percentage for orders this month.
     *
     * @return float
     */
    public function getOrdersUsagePercentAttribute(): float
    {
        if ($this->orders_per_month_limit <= 0) {
            return 0;
        }

        return min(100, ($this->orders_this_month / $this->orders_per_month_limit) * 100);
    }

    /**
     * Get usage percentage for storage.
     *
     * @return float
     */
    public function getStorageUsagePercentAttribute(): float
    {
        if ($this->storage_limit_mb <= 0) {
            return 0;
        }

        return min(100, ($this->storage_used_mb / $this->storage_limit_mb) * 100);
    }

    /**
     * Increment products count.
     *
     * @return bool
     */
    public function incrementProductsCount(): bool
    {
        if ($this->hasExceededProductsLimit()) {
            return false;
        }

        $this->increment('products_count');
        return true;
    }

    /**
     * Decrement products count.
     *
     * @return void
     */
    public function decrementProductsCount(): void
    {
        if ($this->products_count > 0) {
            $this->decrement('products_count');
        }
    }

    /**
     * Increment orders count this month.
     *
     * @return bool
     */
    public function incrementOrdersCount(): bool
    {
        if ($this->hasExceededOrdersLimit()) {
            return false;
        }

        $this->increment('orders_this_month');
        return true;
    }

    /**
     * Reset monthly usage counters.
     *
     * @return void
     */
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'orders_this_month' => 0,
            'last_usage_reset_at' => now(),
        ]);
    }

    /**
     * Update subscription from plan limits.
     *
     * @param Plan $plan
     * @return void
     */
    public function updateLimitsFromPlan(Plan $plan): void
    {
        $this->update([
            'products_limit' => $plan->products_limit ?? 25,
            'orders_per_month_limit' => $plan->orders_per_month ?? 100,
            'storage_limit_mb' => $plan->storage_limit_mb ?? 500,
            'priority_support' => $plan->priority_support,
            'analytics_enabled' => $plan->advanced_analytics,
            'custom_branding_enabled' => $plan->remove_branding,
        ]);
    }

    /**
     * Scope to only premium shops.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePremium(Builder $query): Builder
    {
        return $query->whereNotNull('current_plan_id')
            ->where('subscription_status', 'active');
    }

    /**
     * Scope to only free tier shops.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFreeTier(Builder $query): Builder
    {
        return $query->whereNull('current_plan_id')
            ->orWhere('subscription_status', '!=', 'active');
    }

    /**
     * Scope to shops with premium subdomains.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithSubdomain(Builder $query): Builder
    {
        return $query->where('has_premium_subdomain', true);
    }
}
