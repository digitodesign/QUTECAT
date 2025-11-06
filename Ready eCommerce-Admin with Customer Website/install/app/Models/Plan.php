<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subscription Plan Model
 *
 * Represents the different subscription tiers for vendors.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $price
 * @property int|null $products_limit
 * @property int|null $orders_per_month
 * @property bool $custom_domain
 * @property bool $priority_support
 * @property string|null $stripe_product_id
 * @property string|null $stripe_price_id
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_cycle',
        'trial_days',
        'products_limit',
        'orders_per_month',
        'storage_limit_mb',
        'custom_domain',
        'subdomain_enabled',
        'remove_branding',
        'priority_support',
        'advanced_analytics',
        'api_access',
        'multi_location',
        'staff_accounts',
        'transaction_fee_percent',
        'description',
        'features',
        'stripe_product_id',
        'stripe_price_id',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'products_limit' => 'integer',
        'orders_per_month' => 'integer',
        'storage_limit_mb' => 'integer',
        'custom_domain' => 'boolean',
        'subdomain_enabled' => 'boolean',
        'remove_branding' => 'boolean',
        'priority_support' => 'boolean',
        'advanced_analytics' => 'boolean',
        'api_access' => 'boolean',
        'multi_location' => 'boolean',
        'staff_accounts' => 'integer',
        'transaction_fee_percent' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the shops that are subscribed to this plan.
     *
     * @return HasMany
     */
    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'current_plan_id');
    }

    /**
     * Get the subscriptions for this plan.
     *
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if this is the free plan.
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return $this->price == 0 || $this->slug === 'free';
    }

    /**
     * Check if this plan includes a subdomain.
     *
     * @return bool
     */
    public function hasSubdomain(): bool
    {
        return $this->subdomain_enabled === true;
    }

    /**
     * Get the plan by slug.
     *
     * @param string $slug
     * @return self|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get only active plans.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get paid plans only (excluding free).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    /**
     * Get monthly plans.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    /**
     * Get yearly plans.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }

    /**
     * Get the monthly equivalent price.
     *
     * @return float
     */
    public function getMonthlyEquivalentAttribute(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return round($this->price / 12, 2);
        }

        return $this->price;
    }

    /**
     * Get formatted price.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the feature list as an array.
     *
     * @return array
     */
    public function getFeatureListAttribute(): array
    {
        if (is_array($this->features)) {
            return $this->features;
        }

        return [];
    }
}
