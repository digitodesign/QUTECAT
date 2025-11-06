# Phase 2: API Enhancement & Subscription Management

## Overview

**Duration:** Week 2 of implementation
**Goal:** Make the application subscription-aware and context-aware
**Status:** 🚀 READY TO START

---

## Prerequisites (✅ Complete from Phase 1)

- ✅ Docker environment running
- ✅ PostgreSQL database configured
- ✅ Models created (Tenant, Plan, Subscription, Shop)
- ✅ Migrations ready
- ✅ Middleware created (SetShopContext)
- ✅ Single database architecture confirmed

---

## Phase 2 Objectives

### 1. **Configuration** ⚡
Create centralized SaaS configuration with subscription tiers, limits, and Stripe settings.

### 2. **Middleware Integration** ⚡
Register context middleware globally and create usage limit enforcement.

### 3. **Services Layer** ⚡
Build business logic for subscriptions, usage tracking, and Stripe integration.

### 4. **API Enhancement** ⚡
Make existing API controllers context-aware for hybrid marketplace.

### 5. **Subscription Management** ⚡
Create endpoints for vendors to upgrade, downgrade, and manage subscriptions.

### 6. **Testing** ⚡
Verify hybrid marketplace works with both free and premium vendors.

---

## Implementation Tasks

### Task 1: SaaS Configuration File
**File:** `config/saas.php`

**Purpose:** Centralize all SaaS-related configuration

**Contents:**
```php
<?php
return [
    // Subscription Plans
    'plans' => [
        'free' => [
            'name' => 'Free Marketplace Vendor',
            'price' => 0,
            'products_limit' => 25,
            'orders_per_month' => 100,
            'storage_mb' => 500,
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => false,
                'custom_branding' => false,
                'priority_support' => false,
            ],
        ],
        'starter' => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.00,
            'products_limit' => 100,
            'orders_per_month' => 500,
            'storage_mb' => 5120, // 5GB
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => true,
                'custom_branding' => false,
                'priority_support' => false,
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'slug' => 'growth',
            'price' => 99.00,
            'products_limit' => 1000,
            'orders_per_month' => null, // unlimited
            'storage_mb' => 51200, // 50GB
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => true,
                'custom_branding' => true,
                'priority_support' => true,
                'advanced_analytics' => true,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 299.00,
            'products_limit' => null, // unlimited
            'orders_per_month' => null,
            'storage_mb' => null, // unlimited
            'features' => [
                'marketplace_presence' => true,
                'premium_subdomain' => true,
                'custom_branding' => true,
                'priority_support' => true,
                'advanced_analytics' => true,
                'api_access' => true,
                'multi_location' => true,
            ],
        ],
    ],

    // Stripe Configuration
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // Default Limits (Free Tier)
    'default_limits' => [
        'products' => 25,
        'orders_per_month' => 100,
        'storage_mb' => 500,
    ],

    // Usage Tracking
    'usage_tracking' => [
        'enabled' => true,
        'reset_on' => 'first_day_of_month', // When to reset monthly counters
    ],

    // Trial Settings
    'trial' => [
        'enabled' => true,
        'days' => 14,
        'plan' => 'starter', // Default trial plan
    ],
];
```

**Why Important:**
- Single source of truth for limits
- Easy to adjust pricing/features
- No hardcoded values in controllers

---

### Task 2: Register Middleware
**File:** `app/Http/Kernel.php`

**Changes:**
```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\SetShopContext::class, // ADD THIS
    ],

    'api' => [
        // ... existing middleware
        \App\Http\Middleware\SetShopContext::class, // ADD THIS
    ],
];
```

**Why Important:**
- Automatically sets shop context on every request
- Works for web, API, and mobile app
- Enables hybrid marketplace filtering

---

### Task 3: Usage Limit Middleware
**File:** `app/Http/Middleware/CheckShopLimits.php`

**Purpose:** Enforce subscription limits before actions

**Example:**
```php
public function handle(Request $request, Closure $next, string $limit)
{
    $shop = Shop::find(app('current_shop_id'));

    if (!$shop) {
        return $next($request);
    }

    switch ($limit) {
        case 'products':
            if ($shop->hasExceededProductsLimit()) {
                return response()->json([
                    'error' => 'Product limit reached',
                    'limit' => $shop->products_limit,
                    'current' => $shop->products_count,
                    'upgrade_url' => route('subscription.upgrade'),
                ], 403);
            }
            break;

        case 'orders':
            if ($shop->hasExceededOrdersLimit()) {
                return response()->json([
                    'error' => 'Monthly order limit reached',
                    'limit' => $shop->orders_per_month_limit,
                    'current' => $shop->orders_this_month,
                ], 403);
            }
            break;

        case 'storage':
            if ($shop->hasExceededStorageLimit()) {
                return response()->json([
                    'error' => 'Storage limit exceeded',
                    'limit' => $shop->storage_limit_mb . 'MB',
                    'current' => $shop->storage_used_mb . 'MB',
                ], 403);
            }
            break;
    }

    return $next($request);
}
```

**Usage in Routes:**
```php
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('check.limits:products');

Route::post('/upload', [MediaController::class, 'upload'])
    ->middleware('check.limits:storage');
```

---

### Task 4: Stripe Subscription Service
**File:** `app/Services/Subscription/StripeSubscriptionService.php`

**Purpose:** Handle all Stripe subscription operations

**Methods:**
- `createSubscription($shop, $plan)` - Create new subscription
- `updateSubscription($subscription, $newPlan)` - Upgrade/downgrade
- `cancelSubscription($subscription)` - Cancel at period end
- `resumeSubscription($subscription)` - Resume canceled subscription
- `syncWithStripe($stripeSubscriptionId)` - Sync status from Stripe

**Example:**
```php
public function createSubscription(Shop $shop, Plan $plan): Subscription
{
    // Create Stripe customer if doesn't exist
    if (!$shop->stripe_customer_id) {
        $customer = $this->stripe->customers->create([
            'email' => $shop->user->email,
            'name' => $shop->name,
            'metadata' => ['shop_id' => $shop->id],
        ]);

        $shop->update(['stripe_customer_id' => $customer->id]);
    }

    // Create Stripe subscription
    $stripeSubscription = $this->stripe->subscriptions->create([
        'customer' => $shop->stripe_customer_id,
        'items' => [['price' => $plan->stripe_price_id]],
        'trial_period_days' => config('saas.trial.days'),
        'metadata' => [
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
        ],
    ]);

    // Create local subscription record
    $subscription = Subscription::create([
        'tenant_id' => $shop->tenant?->id,
        'plan_id' => $plan->id,
        'stripe_subscription_id' => $stripeSubscription->id,
        'stripe_customer_id' => $shop->stripe_customer_id,
        'status' => $stripeSubscription->status,
        'trial_ends_at' => $stripeSubscription->trial_end,
        'current_period_start' => $stripeSubscription->current_period_start,
        'current_period_end' => $stripeSubscription->current_period_end,
    ]);

    // Update shop with plan limits
    $shop->updateLimitsFromPlan($plan);
    $shop->update([
        'current_plan_id' => $plan->id,
        'subscription_status' => $stripeSubscription->status,
    ]);

    return $subscription;
}
```

---

### Task 5: Usage Tracking Service
**File:** `app/Services/Subscription/UsageTrackingService.php`

**Purpose:** Monitor and report vendor usage

**Methods:**
- `trackProductCreation($shop)` - Increment product count
- `trackProductDeletion($shop)` - Decrement product count
- `trackOrder($order)` - Increment order count
- `trackStorageUsage($shop)` - Calculate storage used
- `resetMonthlyUsage()` - Reset monthly counters (scheduled task)
- `getUsageReport($shop)` - Get current usage statistics

**Example:**
```php
public function trackProductCreation(Shop $shop): bool
{
    if ($shop->hasExceededProductsLimit()) {
        throw new ProductLimitExceededException(
            "Product limit reached ({$shop->products_limit})"
        );
    }

    $shop->incrementProductsCount();

    // Log for analytics
    Log::info("Product created", [
        'shop_id' => $shop->id,
        'products_count' => $shop->products_count,
        'products_limit' => $shop->products_limit,
    ]);

    // Alert if approaching limit
    if ($shop->products_usage_percent >= 90) {
        event(new UsageLimitApproaching($shop, 'products', 90));
    }

    return true;
}

public function getUsageReport(Shop $shop): array
{
    return [
        'products' => [
            'current' => $shop->products_count,
            'limit' => $shop->products_limit,
            'remaining' => $shop->remaining_products,
            'percent' => $shop->products_usage_percent,
        ],
        'orders' => [
            'current' => $shop->orders_this_month,
            'limit' => $shop->orders_per_month_limit,
            'remaining' => $shop->remaining_orders,
            'percent' => $shop->orders_usage_percent,
        ],
        'storage' => [
            'current_mb' => $shop->storage_used_mb,
            'limit_mb' => $shop->storage_limit_mb,
            'remaining_mb' => $shop->remaining_storage,
            'percent' => $shop->storage_usage_percent,
        ],
    ];
}
```

---

### Task 6: Make API Controllers Context-Aware
**Files:** Existing controllers in `app/Http/Controllers/API/`

**Changes:**

#### ProductController.php
```php
public function index(Request $request)
{
    $query = Product::query();

    // Context-aware filtering
    if ($shopId = app('current_shop_id')) {
        // Premium subdomain or vendor dashboard
        $query->where('shop_id', $shopId);
    } else {
        // Main marketplace - show all products
        $query->whereHas('shop', function($q) {
            $q->where('is_active', true);
        });
    }

    // Existing filters still work
    if ($request->has('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    return $query->paginate(20);
}

public function store(Request $request)
{
    $shop = Shop::find(app('current_shop_id'));

    // Check limit before creating
    if ($shop->hasExceededProductsLimit()) {
        return response()->json([
            'error' => 'Product limit reached',
            'limit' => $shop->products_limit,
            'upgrade_url' => route('api.subscription.plans'),
        ], 403);
    }

    // Create product
    $product = $shop->products()->create($request->validated());

    // Track usage
    app(UsageTrackingService::class)->trackProductCreation($shop);

    return response()->json($product, 201);
}
```

#### OrderController.php
```php
public function index(Request $request)
{
    $query = Order::query();

    // Context-aware filtering
    if ($shopId = app('current_shop_id')) {
        $query->where('shop_id', $shopId);
    }

    return $query->latest()->paginate(20);
}

public function store(Request $request)
{
    // ... order creation logic

    // Track usage for vendor
    if ($order->shop_id) {
        $shop = $order->shop;
        if (!$shop->incrementOrdersCount()) {
            Log::warning("Shop exceeded order limit", [
                'shop_id' => $shop->id,
            ]);
        }
    }

    return response()->json($order, 201);
}
```

---

### Task 7: Vendor Subscription Management Endpoints
**File:** `app/Http/Controllers/API/SubscriptionController.php`

**Routes:**
```php
// routes/api.php
Route::prefix('subscription')->group(function () {
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/current', [SubscriptionController::class, 'current']);
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('/downgrade', [SubscriptionController::class, 'downgrade']);
    Route::post('/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/resume', [SubscriptionController::class, 'resume']);
    Route::get('/usage', [SubscriptionController::class, 'usage']);
});
```

**Methods:**
```php
public function plans()
{
    $plans = Plan::active()->get();

    return response()->json([
        'plans' => $plans,
        'current_plan' => auth()->user()->shop->plan,
    ]);
}

public function current()
{
    $shop = auth()->user()->shop;

    return response()->json([
        'plan' => $shop->plan,
        'subscription' => $shop->subscriptions()->latest()->first(),
        'status' => $shop->subscription_status,
        'trial_ends_at' => $shop->trial_ends_at,
        'subscription_ends_at' => $shop->subscription_ends_at,
    ]);
}

public function subscribe(Request $request)
{
    $request->validate([
        'plan_id' => 'required|exists:plans,id',
        'payment_method' => 'required|string',
    ]);

    $shop = auth()->user()->shop;
    $plan = Plan::findOrFail($request->plan_id);

    $subscriptionService = app(StripeSubscriptionService::class);
    $subscription = $subscriptionService->createSubscription($shop, $plan);

    return response()->json([
        'message' => 'Subscription created successfully',
        'subscription' => $subscription,
        'trial_days' => config('saas.trial.days'),
    ], 201);
}

public function usage()
{
    $shop = auth()->user()->shop;
    $usageService = app(UsageTrackingService::class);

    return response()->json($usageService->getUsageReport($shop));
}
```

---

### Task 8: Testing Checklist

**Test Scenarios:**

#### 1. Context-Aware API
- [ ] Main marketplace shows all products
- [ ] Premium subdomain shows only that shop's products
- [ ] API with X-Shop-ID header filters correctly
- [ ] Mobile app with ?shop_id= parameter works
- [ ] Vendor dashboard shows only their products

#### 2. Usage Limits
- [ ] Free vendor can create 25 products (26th fails)
- [ ] Premium vendor can create 100+ products
- [ ] Monthly order limit enforced
- [ ] Storage limit checked on upload
- [ ] Upgrade increases limits immediately

#### 3. Subscriptions
- [ ] Create subscription with Stripe
- [ ] Trial period works (14 days)
- [ ] Upgrade from Starter to Growth works
- [ ] Downgrade scheduled for next period
- [ ] Cancel subscription works
- [ ] Resume subscription works

#### 4. Subdomain Routing
- [ ] johns-shop.qutecart.com routes to correct tenant
- [ ] Tenant middleware sets shop context
- [ ] Products filtered to that shop only
- [ ] Shop still visible on main marketplace

---

## Development Workflow

### Step 1: Create Configuration
```bash
# Create config file
touch config/saas.php

# Edit and add configuration
# (Use the config template above)
```

### Step 2: Register Middleware
```bash
# Edit app/Http/Kernel.php
# Add SetShopContext to web and api groups
```

### Step 3: Create Services
```bash
# Create service files
mkdir -p app/Services/Subscription
touch app/Services/Subscription/StripeSubscriptionService.php
touch app/Services/Subscription/UsageTrackingService.php
```

### Step 4: Create Middleware
```bash
php artisan make:middleware CheckShopLimits
```

### Step 5: Create Controller
```bash
php artisan make:controller API/SubscriptionController
```

### Step 6: Update Existing Controllers
```bash
# Edit existing API controllers
# Add context-aware filtering
```

### Step 7: Test Locally
```bash
# Start Docker
docker-compose up -d

# Run migrations
docker-compose exec php php artisan migrate

# Test API endpoints
curl http://qutecart.local/api/products
curl http://qutecart.local/api/subscription/plans
```

---

## Success Criteria

Phase 2 is complete when:
- [x] config/saas.php exists with all plan definitions
- [x] SetShopContext middleware registered globally
- [x] CheckShopLimits middleware created
- [x] StripeSubscriptionService implemented
- [x] UsageTrackingService implemented
- [x] All API controllers are context-aware
- [x] Subscription management endpoints work
- [x] Tests pass for all scenarios
- [x] Hybrid marketplace works end-to-end

---

## Estimated Time

| Task | Time | Complexity |
|------|------|------------|
| Configuration | 30 min | Easy |
| Middleware registration | 15 min | Easy |
| CheckShopLimits middleware | 1 hour | Medium |
| StripeSubscriptionService | 3 hours | Hard |
| UsageTrackingService | 2 hours | Medium |
| Update API controllers | 2 hours | Medium |
| Subscription endpoints | 2 hours | Medium |
| Testing | 2 hours | Medium |

**Total:** ~13 hours (1.5-2 days)

---

## Next Phase Preview

### Phase 3: Premium Features & Admin Dashboard
- Stripe webhook handlers
- Admin subscription dashboard
- Vendor analytics
- Premium storefront templates
- Custom branding settings

---

**Status:** 📋 READY TO IMPLEMENT
**Prerequisites:** ✅ ALL MET
**Start:** Phase 2 implementation begins now! 🚀
