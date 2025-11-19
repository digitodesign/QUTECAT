<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Subscription\StripeSubscriptionService;
use App\Services\Subscription\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * SubscriptionController
 *
 * Handles vendor subscription management via API.
 */
class SubscriptionController extends Controller
{
    protected StripeSubscriptionService $stripeService;
    protected UsageTrackingService $usageService;

    public function __construct(
        StripeSubscriptionService $stripeService,
        UsageTrackingService $usageService
    ) {
        $this->middleware('auth:sanctum');
        $this->stripeService = $stripeService;
        $this->usageService = $usageService;
    }

    /**
     * Get all available subscription plans.
     *
     * GET /api/subscription/plans
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function plans()
    {
        $plans = Plan::active()->orderBy('sort_order')->get();
        $user = auth('sanctum')->user();
        $currentShop = $user ? $user->shop : null;

        return response()->json([
            'plans' => $plans,
            'current_plan' => $currentShop?->plan,
            'current_plan_slug' => $currentShop?->plan?->slug ?? 'free',
        ]);
    }

    /**
     * Get current subscription details.
     *
     * GET /api/subscription/current
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function current()
    {
        $shop = auth()->user()->shop;

        if (!$shop) {
            return response()->json([
                'error' => 'No shop associated with this user',
            ], 404);
        }

        $subscription = Subscription::where('tenant_id', $shop->tenant?->id)
            ->latest()
            ->first();

        return response()->json([
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
            ],
            'plan' => $shop->plan ?? [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
            ],
            'subscription' => $subscription,
            'status' => $shop->subscription_status ?? 'free',
            'trial_ends_at' => $shop->trial_ends_at,
            'subscription_ends_at' => $shop->subscription_ends_at,
            'is_premium' => $shop->isPremium(),
            'has_subdomain' => $shop->hasPremiumSubdomain(),
            'subdomain_url' => $shop->currentTenant?->subdomain_url,
        ]);
    }

    /**
     * Subscribe to a plan.
     *
     * POST /api/subscription/subscribe
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method_id' => 'required|string',
        ]);

        $shop = auth()->user()->shop;

        if (!$shop) {
            return response()->json([
                'error' => 'No shop associated with this user',
            ], 404);
        }

        // Check if already subscribed
        if ($shop->isPremium()) {
            return response()->json([
                'error' => 'Already subscribed to a plan',
                'message' => 'Please upgrade or downgrade instead',
                'current_plan' => $shop->plan->name,
            ], 400);
        }

        $plan = Plan::findOrFail($request->plan_id);

        // Don't allow subscribing to free plan
        if ($plan->isFree()) {
            return response()->json([
                'error' => 'Cannot subscribe to free plan',
            ], 400);
        }

        try {
            $subscription = $this->stripeService->createSubscription(
                $shop,
                $plan,
                $request->payment_method_id
            );

            Log::info('Subscription created via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription' => $subscription,
                'trial_days' => $plan->trial_days,
                'trial_ends_at' => $subscription->trial_ends_at,
                'subdomain' => $shop->fresh()->currentTenant?->subdomain,
            ], 201);

        } catch (Exception $e) {
            Log::error('Subscription creation failed via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Subscription creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upgrade to a higher plan.
     *
     * POST /api/subscription/upgrade
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $shop = auth()->user()->shop;
        $newPlan = Plan::findOrFail($request->plan_id);
        $currentSubscription = Subscription::where('tenant_id', $shop->tenant?->id)
            ->valid()
            ->latest()
            ->first();

        if (!$currentSubscription) {
            return response()->json([
                'error' => 'No active subscription found',
                'message' => 'Please subscribe first',
            ], 400);
        }

        $currentPlan = $currentSubscription->plan;

        // Validate it's actually an upgrade
        if ($newPlan->price <= $currentPlan->price) {
            return response()->json([
                'error' => 'Not an upgrade',
                'message' => 'New plan must be higher priced than current plan',
            ], 400);
        }

        try {
            $subscription = $this->stripeService->updateSubscription($currentSubscription, $newPlan);

            Log::info('Subscription upgraded via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'from_plan' => $currentPlan->slug,
                'to_plan' => $newPlan->slug,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plan upgraded successfully',
                'subscription' => $subscription->fresh(),
                'new_limits' => [
                    'products' => $shop->fresh()->products_limit,
                    'orders' => $shop->fresh()->orders_per_month_limit,
                    'storage_mb' => $shop->fresh()->storage_limit_mb,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Subscription upgrade failed via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Upgrade failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Downgrade to a lower plan.
     *
     * POST /api/subscription/downgrade
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function downgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $shop = auth()->user()->shop;
        $newPlan = Plan::findOrFail($request->plan_id);
        $currentSubscription = Subscription::where('tenant_id', $shop->tenant?->id)
            ->valid()
            ->latest()
            ->first();

        if (!$currentSubscription) {
            return response()->json([
                'error' => 'No active subscription found',
            ], 400);
        }

        $currentPlan = $currentSubscription->plan;

        // Validate it's actually a downgrade
        if ($newPlan->price >= $currentPlan->price) {
            return response()->json([
                'error' => 'Not a downgrade',
                'message' => 'New plan must be lower priced than current plan',
            ], 400);
        }

        try {
            $subscription = $this->stripeService->updateSubscription($currentSubscription, $newPlan);

            $effectiveDate = config('saas.plan_changes.downgrade') === 'end_of_period'
                ? $subscription->current_period_end
                : now();

            Log::info('Subscription downgraded via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'from_plan' => $currentPlan->slug,
                'to_plan' => $newPlan->slug,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plan downgraded successfully',
                'subscription' => $subscription->fresh(),
                'effective_date' => $effectiveDate,
                'note' => config('saas.plan_changes.downgrade') === 'end_of_period'
                    ? 'Downgrade will take effect at the end of current billing period'
                    : 'Downgrade is effective immediately',
            ]);

        } catch (Exception $e) {
            Log::error('Subscription downgrade failed via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Downgrade failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription.
     *
     * POST /api/subscription/cancel
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'immediately' => 'sometimes|boolean',
        ]);

        $shop = auth()->user()->shop;
        $subscription = Subscription::where('tenant_id', $shop->tenant?->id)
            ->valid()
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'error' => 'No active subscription found',
            ], 400);
        }

        $immediately = $request->get('immediately', false);

        try {
            $this->stripeService->cancelSubscription($subscription, $immediately);

            Log::info('Subscription canceled via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'subscription_id' => $subscription->id,
                'immediately' => $immediately,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully',
                'ends_at' => $subscription->fresh()->ends_at,
                'note' => $immediately
                    ? 'Subscription canceled immediately. You are now on the free plan.'
                    : 'Subscription will remain active until the end of current billing period.',
            ]);

        } catch (Exception $e) {
            Log::error('Subscription cancellation failed via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Cancellation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume a canceled subscription.
     *
     * POST /api/subscription/resume
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume()
    {
        $shop = auth()->user()->shop;
        $subscription = Subscription::where('tenant_id', $shop->tenant?->id)
            ->where('status', 'canceled')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'error' => 'No canceled subscription found',
            ], 400);
        }

        try {
            $this->stripeService->resumeSubscription($subscription);

            Log::info('Subscription resumed via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'subscription' => $subscription->fresh(),
            ]);

        } catch (Exception $e) {
            Log::error('Subscription resume failed via API', [
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Resume failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get usage statistics for current shop.
     *
     * GET /api/subscription/usage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function usage()
    {
        $shop = auth()->user()->shop;

        if (!$shop) {
            return response()->json([
                'error' => 'No shop associated with this user',
            ], 404);
        }

        $usageReport = $this->usageService->getUsageReport($shop);

        return response()->json($usageReport);
    }

    /**
     * Get subscription history.
     *
     * GET /api/subscription/history
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function history()
    {
        $shop = auth()->user()->shop;

        if (!$shop || !$shop->tenant) {
            return response()->json([
                'subscriptions' => [],
            ]);
        }

        $subscriptions = Subscription::where('tenant_id', $shop->tenant->id)
            ->with('plan')
            ->latest()
            ->get();

        return response()->json([
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Get billing portal URL (for Stripe customer portal).
     *
     * GET /api/subscription/billing-portal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function billingPortal()
    {
        $shop = auth()->user()->shop;

        if (!$shop || !$shop->stripe_customer_id) {
            return response()->json([
                'error' => 'No billing account found',
            ], 404);
        }

        try {
            \Stripe\Stripe::setApiKey(config('saas.stripe.secret'));

            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $shop->stripe_customer_id,
                'return_url' => config('app.url') . '/vendor/subscription',
            ]);

            return response()->json([
                'url' => $session->url,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to create billing portal session',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
