# Phase 2 Testing Plan

**Task 8: Testing Hybrid Marketplace Functionality**

**Date:** November 6, 2025
**Branch:** `claude/review-ecommerce-template-011CUqezuPy1BdW4NYBpric7`
**Status:** In Progress

---

## Overview

This document provides a comprehensive testing plan for Phase 2 implementation. All features must be tested to ensure the hybrid marketplace model works correctly with both free and premium vendors.

---

## Testing Environment Setup

### Prerequisites

1. **Docker Environment Running:**
   ```bash
   cd "/home/user/QUTECAT/backend/install"
   docker-compose up -d
   ```

2. **Database Migrations:**
   ```bash
   docker-compose exec app php artisan migrate:fresh --seed
   ```

3. **Stripe Test Keys:**
   ```env
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_test_...
   ```

4. **Test DNS/Hosts:**
   ```bash
   # Add to /etc/hosts
   127.0.0.1 qutekart.local
   127.0.0.1 premium-shop.qutekart.local
   127.0.0.1 johns-shop.qutekart.local
   ```

---

## Test Categories

### 1. Context-Aware API Testing

#### 1.1 Marketplace Mode (No Shop Context)

**Test:** Products endpoint returns all shops' products
```bash
curl -X GET http://qutekart.local/api/products \
  -H "Accept: application/json"
```

**Expected:**
- Status: 200
- Response contains products from all active shops
- `filters` includes sizes, colors, brands from all shops

**Test:** Categories endpoint returns all categories
```bash
curl -X GET http://qutekart.local/api/categories \
  -H "Accept: application/json"
```

**Expected:**
- Status: 200
- All marketplace categories visible

---

#### 1.2 Shop Context via Subdomain

**Test:** Premium shop subdomain filters products
```bash
curl -X GET http://johns-shop.qutekart.local/api/products \
  -H "Accept: application/json"
```

**Expected:**
- Status: 200
- Only products from John's Shop
- `filters` only includes John's Shop brands/sizes/colors
- No products from other shops

**Test:** Non-existent subdomain
```bash
curl -X GET http://fake-shop.qutekart.local/api/products \
  -H "Accept: application/json"
```

**Expected:**
- Status: 200
- Returns marketplace products (context not set for invalid subdomain)
- OR: Returns empty results if subdomain validation is strict

---

#### 1.3 Shop Context via Header

**Test:** X-Shop-ID header filters products
```bash
curl -X GET http://qutekart.local/api/products \
  -H "Accept: application/json" \
  -H "X-Shop-ID: 1"
```

**Expected:**
- Status: 200
- Only products from shop ID 1
- Filters specific to that shop

**Test:** Invalid shop ID
```bash
curl -X GET http://qutekart.local/api/products \
  -H "Accept: application/json" \
  -H "X-Shop-ID: 99999"
```

**Expected:**
- Status: 200
- Empty products array (shop doesn't exist)

---

#### 1.4 Shop Context via Query Parameter

**Test:** shop_id query param (backward compatibility)
```bash
curl -X GET "http://qutekart.local/api/products?shop_id=1" \
  -H "Accept: application/json"
```

**Expected:**
- Status: 200
- Only products from shop ID 1
- Maintains mobile app backward compatibility

---

#### 1.5 Context Priority Order

**Test:** Subdomain overrides query param
```bash
curl -X GET "http://johns-shop.qutekart.local/api/products?shop_id=2" \
  -H "Accept: application/json"
```

**Expected:**
- Subdomain context takes priority
- Returns John's Shop products (not shop ID 2)

**Test:** Header overrides query param
```bash
curl -X GET "http://qutekart.local/api/products?shop_id=1" \
  -H "Accept: application/json" \
  -H "X-Shop-ID: 2"
```

**Expected:**
- Header context takes priority
- Returns shop ID 2 products

---

### 2. Subscription Management API Testing

#### 2.1 List Available Plans

**Test:** Get all plans (unauthenticated)
```bash
curl -X GET http://qutekart.local/api/subscription/plans \
  -H "Accept: application/json"
```

**Expected:**
- Status: 200
- Returns all 4 plans: free, starter, growth, enterprise
- Each plan has: name, slug, price, features, limits

---

#### 2.2 Get Current Subscription

**Test:** Free vendor subscription
```bash
curl -X GET http://qutekart.local/api/subscription/current \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}"
```

**Expected:**
- Status: 200
- `plan.slug: 'free'`
- `status: 'free'`
- `is_premium: false`
- `has_subdomain: false`

**Test:** Premium vendor subscription
```bash
curl -X GET http://johns-shop.qutekart.local/api/subscription/current \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {premium_vendor_token}"
```

**Expected:**
- Status: 200
- `plan.slug: 'starter'` (or current plan)
- `status: 'active'`
- `is_premium: true`
- `has_subdomain: true`
- `subdomain_url: 'http://johns-shop.qutekart.local'`

---

#### 2.3 Subscribe to Plan

**Test:** Free vendor subscribes to Starter plan
```bash
curl -X POST http://qutekart.local/api/subscription/subscribe \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 2,
    "payment_method_id": "pm_card_visa"
  }'
```

**Expected:**
- Status: 201
- `success: true`
- `subscription` object created
- `trial_days: 14` (if trial enabled)
- `subdomain` generated (e.g., "johns-shop")
- Stripe subscription created
- Shop upgraded to premium

**Test:** Already subscribed vendor
```bash
curl -X POST http://qutekart.local/api/subscription/subscribe \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {premium_vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 2,
    "payment_method_id": "pm_card_visa"
  }'
```

**Expected:**
- Status: 400
- Error: "Already subscribed to a plan"
- Message: "Please upgrade or downgrade instead"

---

#### 2.4 Upgrade Subscription

**Test:** Starter to Growth upgrade
```bash
curl -X POST http://qutekart.local/api/subscription/upgrade \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {starter_vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 3
  }'
```

**Expected:**
- Status: 200
- `success: true`
- `new_limits.products: 1000`
- `new_limits.orders: 500`
- Stripe subscription updated
- Prorated charge if configured

**Test:** Downgrade attempt via upgrade endpoint
```bash
curl -X POST http://qutekart.local/api/subscription/upgrade \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {growth_vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 2
  }'
```

**Expected:**
- Status: 400
- Error: "Not an upgrade"
- Message: "New plan must be higher priced"

---

#### 2.5 Downgrade Subscription

**Test:** Growth to Starter downgrade
```bash
curl -X POST http://qutekart.local/api/subscription/downgrade \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {growth_vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 2
  }'
```

**Expected:**
- Status: 200
- `success: true`
- `effective_date` (end of period if configured)
- `note` explaining when downgrade takes effect

---

#### 2.6 Cancel Subscription

**Test:** Cancel at period end
```bash
curl -X POST http://qutekart.local/api/subscription/cancel \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "immediately": false
  }'
```

**Expected:**
- Status: 200
- `success: true`
- `ends_at` timestamp (end of billing period)
- Note: "Subscription will remain active until..."

**Test:** Cancel immediately
```bash
curl -X POST http://qutekart.local/api/subscription/cancel \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "immediately": true
  }'
```

**Expected:**
- Status: 200
- `ends_at` is now
- Shop reverted to free plan
- Note: "Subscription canceled immediately"

---

#### 2.7 Resume Subscription

**Test:** Resume before period end
```bash
curl -X POST http://qutekart.local/api/subscription/resume \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {canceled_vendor_token}"
```

**Expected:**
- Status: 200
- `success: true`
- Subscription status: 'active'
- `cancel_at_period_end` false in Stripe

---

#### 2.8 Usage Statistics

**Test:** Get current usage
```bash
curl -X GET http://qutekart.local/api/subscription/usage \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}"
```

**Expected:**
- Status: 200
- `products.current`, `products.limit`, `products.percent`, `products.status`
- `orders.current`, `orders.limit`, etc.
- `storage.current_mb`, `storage.limit_mb`, etc.
- `subscription` details

---

#### 2.9 Subscription History

**Test:** Get all subscriptions
```bash
curl -X GET http://qutekart.local/api/subscription/history \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}"
```

**Expected:**
- Status: 200
- Array of all subscription records
- Includes canceled, active, expired
- Each with plan details

---

#### 2.10 Billing Portal

**Test:** Get Stripe portal URL
```bash
curl -X GET http://qutekart.local/api/subscription/billing-portal \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {vendor_token}"
```

**Expected:**
- Status: 200
- `url: "https://billing.stripe.com/session/..."`
- Redirects to Stripe customer portal

---

### 3. Usage Limit Enforcement Testing

#### 3.1 Product Limit

**Test:** Create product when under limit
```bash
curl -X POST http://qutekart.local/api/vendor/products \
  -H "Authorization: Bearer {vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Product",
    "price": 29.99,
    ...
  }'
```

**Expected:**
- Status: 201
- Product created successfully

**Test:** Create product when at limit
```bash
# After creating 25 products on free plan
curl -X POST http://qutekart.local/api/vendor/products \
  -H "Authorization: Bearer {free_vendor_token}" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

**Expected:**
- Status: 403
- Error: "Product limit reached"
- Message: "You've reached your plan limit of 25 products"
- Upgrade prompt

**Test:** Warning at 80% threshold
```bash
# After creating 20 products on free plan (80% of 25)
curl -X POST http://qutekart.local/api/vendor/products \
  -H "Authorization: Bearer {free_vendor_token}" \
  -d '{...}'
```

**Expected:**
- Status: 201
- Product created
- Event fired: `LimitApproachingEvent`
- (Optional) Warning in response

---

#### 3.2 Order Limit

**Test:** Process order when under limit
```bash
curl -X POST http://qutekart.local/api/orders \
  -H "Authorization: Bearer {customer_token}" \
  -d '{...}'
```

**Expected:**
- Status: 201
- Order created

**Test:** Process order when at monthly limit
```bash
# After 100 orders this month on free plan
curl -X POST http://qutekart.local/api/orders \
  -H "Authorization: Bearer {customer_token}" \
  -d '{...}'
```

**Expected:**
- Status: 403
- Error: "Monthly order limit reached"
- Shop cannot accept new orders until limit resets

---

#### 3.3 Storage Limit

**Test:** Upload image when under limit
```bash
curl -X POST http://qutekart.local/api/vendor/products/upload \
  -H "Authorization: Bearer {vendor_token}" \
  -F "image=@product.jpg"
```

**Expected:**
- Status: 201
- Image uploaded

**Test:** Upload when at storage limit
```bash
# After using 500MB on free plan
curl -X POST http://qutekart.local/api/vendor/products/upload \
  -H "Authorization: Bearer {free_vendor_token}" \
  -F "image=@large_image.jpg"
```

**Expected:**
- Status: 403
- Error: "Storage limit exceeded"
- Current usage shown

---

### 4. Subdomain Routing Testing

#### 4.1 Premium Subdomain Access

**Test:** Access via subdomain
```bash
curl -X GET http://johns-shop.qutekart.local/ \
  -H "Accept: text/html"
```

**Expected:**
- Status: 200
- Shop storefront rendered
- Branded with John's Shop theme
- Only John's products visible

---

#### 4.2 Free Vendor Subdomain

**Test:** Free vendor has no subdomain
```bash
# Check tenant record
SELECT * FROM tenants WHERE shop_id = {free_shop_id};
```

**Expected:**
- No tenant record for free shop
- Accessing fake subdomain returns marketplace or 404

---

#### 4.3 Subdomain Creation on Upgrade

**Test:** Subdomain created on subscription
```bash
# Subscribe free vendor
POST /api/subscription/subscribe

# Then check
SELECT subdomain FROM tenants WHERE shop_id = {shop_id};
```

**Expected:**
- Tenant record created
- Subdomain auto-generated from shop name
- Unique subdomain (no conflicts)

---

### 5. Stripe Integration Testing

#### 5.1 Customer Creation

**Test:** Stripe customer created on first subscription
```bash
# Check after subscribe API call
SELECT stripe_customer_id FROM shops WHERE id = {shop_id};
```

**Expected:**
- `stripe_customer_id` populated
- Customer exists in Stripe dashboard
- Metadata includes shop_id, shop_name

---

#### 5.2 Payment Method Attachment

**Test:** Payment method attached and set as default
```bash
# Use Stripe API to check
curl https://api.stripe.com/v1/customers/{customer_id}/payment_methods \
  -u sk_test_...
```

**Expected:**
- Payment method attached
- Set as default for invoices

---

#### 5.3 Trial Period

**Test:** Trial subscription created
```bash
POST /api/subscription/subscribe
# With trial enabled in config
```

**Expected:**
- `trial_ends_at` set to 14 days in future
- No immediate charge
- Subscription status: 'trialing'

---

#### 5.4 Prorations

**Test:** Immediate upgrade proration
```bash
POST /api/subscription/upgrade
# From $29 to $99 plan mid-cycle
```

**Expected:**
- Immediate invoice created
- Prorated charge for unused time
- Visible in Stripe dashboard

**Test:** End-of-period downgrade
```bash
POST /api/subscription/downgrade
# With downgrade: 'end_of_period' config
```

**Expected:**
- No immediate charge
- Downgrade scheduled for period end
- Current plan remains active

---

### 6. Backward Compatibility Testing

#### 6.1 Mobile App Compatibility

**Test:** Old mobile app with shop_id param
```bash
curl -X GET "http://qutekart.local/api/products?shop_id=1" \
  -H "Authorization: Bearer {customer_token}"
```

**Expected:**
- Status: 200
- Products filtered to shop_id=1
- No breaking changes to response format

---

#### 6.2 Existing API Responses

**Test:** Product response structure unchanged
```bash
curl -X GET "http://qutekart.local/api/products" \
  -H "Accept: application/json"
```

**Expected:**
- Same response structure as before
- Additional fields optional
- No removed fields

---

### 7. Security Testing

#### 7.1 Authorization

**Test:** Unauthenticated subscription access
```bash
curl -X GET http://qutekart.local/api/subscription/current
```

**Expected:**
- Status: 401
- Error: "Unauthenticated"

---

#### 7.2 Cross-Shop Data Access

**Test:** Vendor A accessing Vendor B's data
```bash
curl -X GET http://qutekart.local/api/vendor/products \
  -H "Authorization: Bearer {vendor_a_token}"
```

**Expected:**
- Only Vendor A's products returned
- No access to other vendors' data

---

#### 7.3 Context Injection Attack

**Test:** Attempt to bypass context via header manipulation
```bash
curl -X GET http://johns-shop.qutekart.local/api/products \
  -H "X-Shop-ID: 999" \
  -H "Accept: application/json"
```

**Expected:**
- Subdomain context takes priority
- Returns John's Shop products (ignores header)

---

### 8. Performance Testing

#### 8.1 Query Efficiency

**Test:** Products query with context
```sql
EXPLAIN ANALYZE
SELECT * FROM products
WHERE shop_id = 1 AND status = 'active';
```

**Expected:**
- Index used on `shop_id`
- Query time < 10ms for 1000 products

---

#### 8.2 Context Detection Overhead

**Test:** Measure middleware execution time
```php
// Add timing to SetShopContext middleware
$start = microtime(true);
// ... context detection
$elapsed = microtime(true) - $start;
Log::info("Context detection: {$elapsed}ms");
```

**Expected:**
- Context detection < 1ms
- Negligible overhead

---

## Test Execution Checklist

### Manual Testing

- [ ] Context-aware API (marketplace mode)
- [ ] Context-aware API (subdomain mode)
- [ ] Context-aware API (header mode)
- [ ] Context-aware API (query param mode)
- [ ] Context priority order
- [ ] List subscription plans
- [ ] Get current subscription (free vendor)
- [ ] Get current subscription (premium vendor)
- [ ] Subscribe to plan (success)
- [ ] Subscribe to plan (already subscribed)
- [ ] Upgrade subscription (success)
- [ ] Upgrade subscription (not an upgrade)
- [ ] Downgrade subscription
- [ ] Cancel subscription (end of period)
- [ ] Cancel subscription (immediately)
- [ ] Resume subscription
- [ ] Get usage statistics
- [ ] Get subscription history
- [ ] Get billing portal URL
- [ ] Product limit enforcement (under limit)
- [ ] Product limit enforcement (at limit)
- [ ] Product limit enforcement (warning threshold)
- [ ] Order limit enforcement
- [ ] Storage limit enforcement
- [ ] Premium subdomain access
- [ ] Free vendor no subdomain
- [ ] Subdomain creation on upgrade
- [ ] Stripe customer creation
- [ ] Payment method attachment
- [ ] Trial period functionality
- [ ] Upgrade prorations
- [ ] Downgrade scheduling
- [ ] Mobile app backward compatibility
- [ ] API response structure unchanged
- [ ] Unauthenticated access blocked
- [ ] Cross-shop data isolation
- [ ] Context injection prevention
- [ ] Query performance acceptable
- [ ] Minimal middleware overhead

---

## Automated Test Suite

### PHPUnit Tests to Create

```bash
tests/Feature/API/
├── ContextAwareApiTest.php
├── SubscriptionManagementTest.php
├── UsageLimitEnforcementTest.php
├── SubdomainRoutingTest.php
└── StripeIntegrationTest.php

tests/Unit/
├── Services/StripeSubscriptionServiceTest.php
├── Services/UsageTrackingServiceTest.php
├── Middleware/SetShopContextTest.php
└── Middleware/CheckShopLimitsTest.php
```

### Example Test: Context-Aware API

```php
<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\Shop;
use App\Models\Product;

class ContextAwareApiTest extends TestCase
{
    /** @test */
    public function it_returns_all_products_in_marketplace_mode()
    {
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        Product::factory()->create(['shop_id' => $shop1->id]);
        Product::factory()->create(['shop_id' => $shop2->id]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('products'));
    }

    /** @test */
    public function it_filters_products_by_shop_id_query_param()
    {
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        Product::factory()->create(['shop_id' => $shop1->id]);
        Product::factory()->create(['shop_id' => $shop2->id]);

        $response = $this->getJson("/api/products?shop_id={$shop1->id}");

        $response->assertStatus(200);
        $products = $response->json('products');
        $this->assertCount(1, $products);
        $this->assertEquals($shop1->id, $products[0]['shop_id']);
    }

    /** @test */
    public function it_filters_products_by_x_shop_id_header()
    {
        $shop = Shop::factory()->create();
        Product::factory()->create(['shop_id' => $shop->id]);

        $response = $this->getJson('/api/products', [
            'X-Shop-ID' => $shop->id
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('products'));
    }
}
```

---

## Success Criteria

Phase 2 is considered complete when:

### Functionality
- ✅ All API endpoints return correct responses
- ✅ Context detection works in all modes
- ✅ Subscription lifecycle (subscribe, upgrade, downgrade, cancel) functional
- ✅ Usage limits enforced correctly
- ✅ Stripe integration working
- ✅ Subdomain routing operational

### Security
- ✅ Authentication required for protected endpoints
- ✅ Cross-shop data access prevented
- ✅ Context injection attacks blocked

### Performance
- ✅ API response times < 200ms
- ✅ Context detection overhead < 1ms
- ✅ Database queries optimized

### Compatibility
- ✅ Backward compatible with mobile app
- ✅ Existing API contracts maintained

### Documentation
- ✅ All endpoints documented
- ✅ Test results recorded
- ✅ Known issues documented

---

## Known Issues / Limitations

Document any issues discovered during testing:

1. **Issue:** [Description]
   - **Impact:** [Low/Medium/High]
   - **Workaround:** [If available]
   - **Fix planned for:** [Phase 3 / Future]

---

## Next Steps (Phase 3 Preview)

After Phase 2 testing is complete:

1. **Stripe Webhooks** - Handle subscription events
2. **Admin Dashboard** - Subscription management UI
3. **Vendor Analytics** - Usage insights and reporting
4. **Premium Templates** - Customizable storefront themes
5. **Custom Branding** - Logo, colors, favicon for subdomains

---

## Test Execution Log

**Date:** November 6, 2025
**Tester:** Claude
**Environment:** Docker (local development)

| Test Category | Status | Notes |
|---------------|--------|-------|
| Context-Aware API | ⏳ Pending | |
| Subscription API | ⏳ Pending | |
| Usage Limits | ⏳ Pending | |
| Subdomain Routing | ⏳ Pending | |
| Stripe Integration | ⏳ Pending | Requires test keys |
| Backward Compat | ⏳ Pending | |
| Security | ⏳ Pending | |
| Performance | ⏳ Pending | |

Legend:
- ⏳ Pending
- 🔄 In Progress
- ✅ Passed
- ❌ Failed
- ⚠️ Partial / Issues Found

---

**End of Testing Plan**
