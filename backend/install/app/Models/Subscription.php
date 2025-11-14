<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Subscription Model
 *
 * Tracks vendor subscriptions to plans (integrated with Stripe).
 *
 * @property int $id
 * @property string $tenant_id
 * @property int $plan_id
 * @property string $status
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon|null $current_period_start
 * @property \Carbon\Carbon|null $current_period_end
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_price_id',
        'quantity',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'ends_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'plan_id' => 'integer',
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Subscription statuses.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    public const STATUS_UNPAID = 'unpaid';

    /**
     * Get the tenant that owns the subscription.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Get the plan for this subscription.
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Determine if the subscription is on trial.
     *
     * @return bool
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING ||
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Determine if the subscription is canceled.
     *
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED || $this->canceled_at !== null;
    }

    /**
     * Determine if the subscription has ended.
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Determine if the subscription is valid (active or on trial).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isActive() || $this->onTrial();
    }

    /**
     * Get the days remaining in the trial.
     *
     * @return int
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->onTrial() || !$this->trial_ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Get the days remaining in the current billing period.
     *
     * @return int
     */
    public function daysRemainingInPeriod(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Mark the subscription as canceled.
     *
     * @param Carbon|null $endsAt
     * @return self
     */
    public function markAsCanceled(?Carbon $endsAt = null): self
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'canceled_at' => now(),
            'ends_at' => $endsAt ?? now(),
        ]);

        return $this;
    }

    /**
     * Resume a canceled subscription.
     *
     * @return self
     */
    public function resume(): self
    {
        if (!$this->isCanceled()) {
            return $this;
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'canceled_at' => null,
            'ends_at' => null,
        ]);

        return $this;
    }

    /**
     * Scope to only active subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to only trialing subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrialing($query)
    {
        return $query->where('status', self::STATUS_TRIALING);
    }

    /**
     * Scope to only valid subscriptions (active or trialing).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIALING]);
    }

    /**
     * Scope to subscriptions expiring soon.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('current_period_end', [
            now(),
            now()->addDays($days),
        ]);
    }

    /**
     * Get formatted renewal date.
     *
     * @return string|null
     */
    public function getRenewalDateAttribute(): ?string
    {
        if (!$this->current_period_end) {
            return null;
        }

        return $this->current_period_end->format('M d, Y');
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // When a subscription becomes active, update the tenant/shop
        static::updated(function (Subscription $subscription) {
            if ($subscription->isDirty('status') && $subscription->isActive()) {
                // Subscription became active - extend premium access
                if ($subscription->tenant) {
                    $subscription->tenant->update([
                        'premium_expires_at' => $subscription->current_period_end,
                    ]);
                }
            }
        });
    }
}
