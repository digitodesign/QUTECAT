<?php

namespace App\Services\Subscription;

use App\Models\Plan;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\Tenant;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Stripe\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * StripeSubscriptionService
 *
 * Handles all Stripe subscription operations for vendors.
 */
class StripeSubscriptionService
{
    /**
     * Initialize Stripe with secret key.
     */
    public function __construct()
    {
        Stripe::setApiKey(config('saas.stripe.secret'));
    }

    /**
     * Create a new subscription for a shop.
     *
     * @param Shop $shop
     * @param Plan $plan
     * @param string $paymentMethodId
     * @return Subscription
     * @throws Exception
     */
    public function createSubscription(Shop $shop, Plan $plan, string $paymentMethodId): Subscription
    {
        try {
            // Create or retrieve Stripe customer
            $stripeCustomer = $this->getOrCreateCustomer($shop);

            // Attach payment method to customer
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomer->id]);

            // Set as default payment method
            Customer::update($stripeCustomer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // Create Stripe subscription
            $trialDays = config('saas.trial.enabled') ? $plan->trial_days ?? config('saas.trial.days') : 0;

            $stripeSubscription = StripeSubscription::create([
                'customer' => $stripeCustomer->id,
                'items' => [
                    ['price' => $plan->stripe_price_id],
                ],
                'trial_period_days' => $trialDays,
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => [
                    'shop_id' => $shop->id,
                    'plan_id' => $plan->id,
                    'shop_name' => $shop->name,
                ],
            ]);

            // Create local subscription record
            $subscription = Subscription::create([
                'tenant_id' => $shop->tenant?->id,
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_customer_id' => $stripeCustomer->id,
                'stripe_price_id' => $plan->stripe_price_id,
                'status' => $stripeSubscription->status,
                'quantity' => 1,
                'trial_ends_at' => $stripeSubscription->trial_end ? now()->timestamp($stripeSubscription->trial_end) : null,
                'current_period_start' => now()->timestamp($stripeSubscription->current_period_start),
                'current_period_end' => now()->timestamp($stripeSubscription->current_period_end),
            ]);

            // Update shop with subscription details
            $this->updateShopFromSubscription($shop, $plan, $stripeSubscription);

            Log::info('Subscription created', [
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);

            return $subscription;

        } catch (Exception $e) {
            Log::error('Subscription creation failed', [
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update subscription to a new plan (upgrade/downgrade).
     *
     * @param Subscription $subscription
     * @param Plan $newPlan
     * @return Subscription
     * @throws Exception
     */
    public function updateSubscription(Subscription $subscription, Plan $newPlan): Subscription
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);

            // Determine if upgrade or downgrade
            $currentPlan = $subscription->plan;
            $isUpgrade = $newPlan->price > $currentPlan->price;

            // Update Stripe subscription
            $prorationBehavior = $isUpgrade
                ? (config('saas.plan_changes.prorate_upgrade') ? 'always_invoice' : 'none')
                : (config('saas.plan_changes.prorate_downgrade') ? 'create_prorations' : 'none');

            $updateParams = [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price' => $newPlan->stripe_price_id,
                    ],
                ],
                'proration_behavior' => $prorationBehavior,
                'metadata' => [
                    'previous_plan_id' => $currentPlan->id,
                    'new_plan_id' => $newPlan->id,
                ],
            ];

            // If downgrade and end of period policy
            if (!$isUpgrade && config('saas.plan_changes.downgrade') === 'end_of_period') {
                $updateParams['proration_behavior'] = 'none';
                $updateParams['billing_cycle_anchor'] = 'unchanged';
            }

            $stripeSubscription = $stripeSubscription->update($updateParams);

            // Update local subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'stripe_price_id' => $newPlan->stripe_price_id,
                'status' => $stripeSubscription->status,
                'current_period_start' => now()->timestamp($stripeSubscription->current_period_start),
                'current_period_end' => now()->timestamp($stripeSubscription->current_period_end),
            ]);

            // Update shop limits
            $this->updateShopFromSubscription($subscription->tenant->shop, $newPlan, $stripeSubscription);

            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'from_plan' => $currentPlan->slug,
                'to_plan' => $newPlan->slug,
                'type' => $isUpgrade ? 'upgrade' : 'downgrade',
            ]);

            return $subscription->fresh();

        } catch (Exception $e) {
            Log::error('Subscription update failed', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription
     * @param bool $immediately
     * @return Subscription
     * @throws Exception
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);

            if ($immediately) {
                // Cancel immediately
                $stripeSubscription->cancel();

                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'ends_at' => now(),
                ]);

                // Revert shop to free tier
                $freePlan = Plan::where('slug', 'free')->first();
                if ($freePlan) {
                    $this->updateShopFromPlan($subscription->tenant->shop, $freePlan);
                }
            } else {
                // Cancel at period end
                $stripeSubscription->update([
                    'cancel_at_period_end' => true,
                ]);

                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'ends_at' => now()->timestamp($stripeSubscription->current_period_end),
                ]);
            }

            Log::info('Subscription canceled', [
                'subscription_id' => $subscription->id,
                'immediately' => $immediately,
            ]);

            return $subscription->fresh();

        } catch (Exception $e) {
            Log::error('Subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resume a canceled subscription.
     *
     * @param Subscription $subscription
     * @return Subscription
     * @throws Exception
     */
    public function resumeSubscription(Subscription $subscription): Subscription
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);

            // Only can resume if cancel_at_period_end is true
            if (!$stripeSubscription->cancel_at_period_end) {
                throw new Exception('Subscription is not scheduled for cancellation');
            }

            $stripeSubscription->update([
                'cancel_at_period_end' => false,
            ]);

            $subscription->update([
                'status' => 'active',
                'canceled_at' => null,
                'ends_at' => null,
            ]);

            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
            ]);

            return $subscription->fresh();

        } catch (Exception $e) {
            Log::error('Subscription resume failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync subscription with Stripe.
     *
     * @param string $stripeSubscriptionId
     * @return Subscription|null
     */
    public function syncWithStripe(string $stripeSubscriptionId): ?Subscription
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($stripeSubscriptionId);
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if (!$subscription) {
                Log::warning('Subscription not found for Stripe sync', [
                    'stripe_subscription_id' => $stripeSubscriptionId,
                ]);
                return null;
            }

            // Update subscription status
            $subscription->update([
                'status' => $stripeSubscription->status,
                'current_period_start' => now()->timestamp($stripeSubscription->current_period_start),
                'current_period_end' => now()->timestamp($stripeSubscription->current_period_end),
            ]);

            // Update shop status if subscription status changed
            if ($subscription->tenant && $subscription->tenant->shop) {
                $subscription->tenant->shop->update([
                    'subscription_status' => $stripeSubscription->status,
                ]);
            }

            Log::info('Subscription synced with Stripe', [
                'subscription_id' => $subscription->id,
                'status' => $stripeSubscription->status,
            ]);

            return $subscription->fresh();

        } catch (Exception $e) {
            Log::error('Subscription sync failed', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get or create Stripe customer for shop.
     *
     * @param Shop $shop
     * @return Customer
     * @throws Exception
     */
    protected function getOrCreateCustomer(Shop $shop): Customer
    {
        if ($shop->stripe_customer_id) {
            try {
                return Customer::retrieve($shop->stripe_customer_id);
            } catch (Exception $e) {
                // Customer not found, create new one
                Log::warning('Stripe customer not found, creating new', [
                    'shop_id' => $shop->id,
                    'old_customer_id' => $shop->stripe_customer_id,
                ]);
            }
        }

        // Create new customer
        $customer = Customer::create([
            'email' => $shop->user->email,
            'name' => $shop->name,
            'metadata' => [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'user_id' => $shop->user_id,
            ],
        ]);

        // Save customer ID to shop
        $shop->update([
            'stripe_customer_id' => $customer->id,
        ]);

        Log::info('Stripe customer created', [
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
        ]);

        return $customer;
    }

    /**
     * Update shop with subscription details.
     *
     * @param Shop $shop
     * @param Plan $plan
     * @param StripeSubscription $stripeSubscription
     * @return void
     */
    protected function updateShopFromSubscription(Shop $shop, Plan $plan, StripeSubscription $stripeSubscription): void
    {
        $shop->update([
            'current_plan_id' => $plan->id,
            'subscription_status' => $stripeSubscription->status,
            'stripe_subscription_id' => $stripeSubscription->id,
            'trial_ends_at' => $stripeSubscription->trial_end ? now()->timestamp($stripeSubscription->trial_end) : null,
            'subscription_started_at' => now()->timestamp($stripeSubscription->created),
            'subscription_ends_at' => now()->timestamp($stripeSubscription->current_period_end),
        ]);

        // Update limits from plan
        $shop->updateLimitsFromPlan($plan);

        // Create tenant if premium plan with subdomain
        if ($plan->subdomain_enabled && !$shop->tenant()->exists()) {
            $subdomain = $this->generateSubdomain($shop);
            Tenant::createForShop($shop, $subdomain, $plan->slug);
        }
    }

    /**
     * Update shop limits from plan.
     *
     * @param Shop $shop
     * @param Plan $plan
     * @return void
     */
    protected function updateShopFromPlan(Shop $shop, Plan $plan): void
    {
        $shop->update([
            'current_plan_id' => $plan->id,
        ]);

        $shop->updateLimitsFromPlan($plan);
    }

    /**
     * Generate subdomain for shop.
     *
     * @param Shop $shop
     * @return string
     */
    protected function generateSubdomain(Shop $shop): string
    {
        $base = strtolower(str_replace(' ', '-', $shop->name));
        $base = preg_replace('/[^a-z0-9-]/', '', $base);

        $subdomain = $base;
        $counter = 1;

        // Ensure unique subdomain
        while (Tenant::where('subdomain', $subdomain)->exists()) {
            $subdomain = $base . '-' . $counter;
            $counter++;
        }

        return $subdomain;
    }
}
