# 🎯 QUTECAT HYBRID MARKETPLACE ARCHITECTURE

**Vision:** Multi-vendor marketplace with optional premium vendor subdomains
**Domains:** qutekart.com (main) + {vendor}.qutekart.com (premium)
**Date:** November 6, 2025

---

## 📊 CURRENT SYSTEM AUDIT

### ✅ What Already Exists

#### **1. Multi-Vendor Marketplace Infrastructure**
```
qutekart.com
├── Customer Website (Browse ALL products from ALL vendors)
├── Vendor Dashboard at /shop/* (Manage inventory, orders, etc.)
├── Admin Panel at /admin/* (Platform management)
└── Mobile App API at /api/* (Flutter customer app)
```

**Database Structure (Single Database):**
```sql
shops table:
- id, name, user_id, logo, banner
- delivery_charge, min_order_amount
- opening_time, closing_time
- status, description
- All vendors in ONE table

products table:
- id, name, shop_id (which vendor)
- price, quantity, discount_price
- description, is_active
- Products from ALL vendors

orders table:
- id, shop_id (which vendor gets this order)
- customer_id, order_code
- payable_amount, payment_status
- order_status, delivery info

customers table:
- All customers shop across ALL vendors
```

#### **2. Existing Features**
✅ **Vendor Management:**
- Vendor registration & login at `/shop/login`
- Dashboard: Products, orders, inventory, customers, analytics
- Bulk product import/export
- Coupons & vouchers
- Flash sales
- Gallery management
- Employee management
- POS system for physical stores
- Withdrawal requests

✅ **Customer Experience:**
- Browse products from ALL vendors together
- Filter by shop, category, brand, price
- Add to cart from multiple vendors
- Single checkout (orders split by shop)
- Order tracking
- Reviews & ratings
- Favorite products
- Chat with vendors
- Return orders

✅ **Mobile App (Flutter):**
- Customer app connects to `/api/*`
- Shows products from ALL vendors
- Same features as website
- Push notifications (Firebase)
- Real-time chat (Pusher)

✅ **Admin Features:**
- Platform management
- Vendor approval
- Commission settings
- Payment gateway config
- Content management

#### **3. Shop Subscriptions (Already Built!)**
The system ALREADY has shop subscriptions:
```php
ShopSubscription model:
- shop_id
- status (active/inactive)
- remaining_sales
- ends_at
```

This is PERFECT for our hybrid model! We just need to expand it.

#### **4. System Settings**
```php
generate_settings table:
- shop_type: 'multi' (default) | 'single'
  - 'multi' → Shows all vendors' products
  - 'single' → Only one vendor (root user's shop)
```

---

## 🏗️ HYBRID MODEL ARCHITECTURE

### **The Vision**

```
┌─────────────────────────────────────────────────────────┐
│          qutekart.com (Main Marketplace)                │
│  Shows products from ALL vendors (free + premium)       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ John's Shop  │  │ Sarah's Shop │  │  Mike's Shop │ │
│  │ (Free Tier)  │  │ (Premium)    │  │  (Free Tier) │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
                            ↓
                  ┌─────────┴─────────┐
                  │                   │
          FREE VENDORS          PREMIUM VENDORS
          └─ qutekart.com       └─ johns-shop.qutekart.com
             /shop/dashboard       └─ Has own branded store
             └─ Basic features        └─ PLUS appears on main marketplace
                                      └─ White-label option
                                      └─ Custom domain (Growth/Enterprise)
```

### **Two Vendor Tiers**

#### **FREE/STARTER TIER** (Default)
```
What they get:
✅ Sell on qutekart.com marketplace
✅ Access to /shop/dashboard
✅ Basic analytics
✅ Order management
✅ Product listings
✅ Standard support

Where customers find them:
→ qutekart.com (mixed with other vendors)
→ qutekart.com/shop/{shop-id} (shop page)

Cost: Free OR $29/month for enhanced features
```

#### **PREMIUM TIER** ($99-299/month)
```
What they get:
✅ Everything in Free tier
✅ Own subdomain: {shop-slug}.qutekart.com
✅ Branded storefront
✅ Custom domain support (shop.com → CNAME)
✅ Remove QuteCart branding
✅ Advanced analytics
✅ Priority support
✅ API access
✅ Still appears on main marketplace!

Where customers find them:
→ qutekart.com (STILL appears here!)
→ johns-shop.qutekart.com (their branded store)
→ johns-shop.com (if custom domain configured)

Cost: $99/mo (Growth) or $299/mo (Enterprise)
```

---

## 🎨 HOW IT WORKS

### **Scenario 1: Free Vendor (John)**

**John's Experience:**
1. Signs up at `qutekart.com/shop/register`
2. Gets vendor dashboard at `/shop/dashboard`
3. Adds products (iPhones, Macbooks, etc.)
4. Products appear on qutekart.com marketplace
5. Customers can:
   - Find products via qutekart.com search
   - Visit John's shop page: `qutekart.com/shop/johns-electronics`
   - Add to cart and checkout

**Database:**
```sql
shops table:
- id: 1
- name: "John's Electronics"
- user_id: 123
- subscription_tier: 'free'
- subdomain: NULL

products table:
- id: 1, shop_id: 1, name: "iPhone 15"
- id: 2, shop_id: 1, name: "MacBook Pro"
```

**API Response (Mobile App & Web):**
```json
GET /api/products
{
  "products": [
    {"id": 1, "name": "iPhone 15", "shop": {"name": "John's Electronics"}},
    {"id": 5, "name": "Red Dress", "shop": {"name": "Sarah's Fashion"}},
    ...
  ]
}
```

### **Scenario 2: Premium Vendor (Sarah)**

**Sarah's Experience:**
1. Starts as free vendor
2. Upgrades to Growth plan ($99/mo)
3. Chooses subdomain: `sarahs-fashion.qutekart.com`
4. Gets branded storefront at her subdomain
5. Products STILL appear on qutekart.com marketplace
6. Can optionally add custom domain: `sarahsfashion.com`

**Database:**
```sql
shops table:
- id: 2
- name: "Sarah's Fashion"
- user_id: 456
- subscription_tier: 'growth'
- subdomain: 'sarahs-fashion'
- custom_domain: 'sarahsfashion.com'

tenants table: (NEW - only for premium vendors)
- id: uuid-xxx
- shop_id: 2 (references shops table)
- owner_email: sarah@example.com
- status: 'active'

domains table: (NEW - only for premium vendors)
- domain: 'sarahs-fashion.qutekart.com'
- tenant_id: uuid-xxx
```

**Customer Experience:**
```
Option A: Visit qutekart.com
→ Browse ALL products including Sarah's
→ Add Sarah's dress to cart
→ Checkout (may include items from John too!)

Option B: Visit sarahs-fashion.qutekart.com
→ See ONLY Sarah's products
→ Branded experience (Sarah's colors, logo)
→ Checkout (only Sarah's items)

Option C: Visit sarahsfashion.com (custom domain)
→ CNAME points to sarahs-fashion.qutekart.com
→ Same as Option B but on her domain
```

---

## 🔧 TECHNICAL IMPLEMENTATION

### **Architecture Decision: Hybrid Single-Database + Selective Multi-Tenancy**

**DON'T use multi-tenancy for everything. Use it ONLY for premium subdomains.**

```
Main Database (qutekart_main):
├── shops (ALL vendors - free + premium)
├── products (ALL products - visible on main marketplace)
├── orders (ALL orders)
├── customers (ALL customers)
├── tenants (only premium vendors who want subdomains)
├── domains (subdomain mappings)
├── subscriptions (vendor subscription tracking)
└── ... all other tables

Premium Vendor Subdomains:
→ Use tenancy ONLY for request routing
→ Data still in main database, just filtered by shop_id
→ NO separate databases per vendor
```

**Why This Approach:**
1. ✅ Products stay in main database → mobile app & marketplace work seamlessly
2. ✅ No data duplication
3. ✅ Easy to upgrade/downgrade vendors
4. ✅ Simpler to manage
5. ✅ Better performance (one DB connection)

### **Request Flow**

#### **Request to qutekart.com**
```
1. Request: GET qutekart.com/products
2. Middleware: Central context (no tenancy)
3. Query: Product::where('is_active', true)->get()
4. Result: ALL products from ALL vendors
5. Response: Paginated product list
```

#### **Request to johns-shop.qutekart.com**
```
1. Request: GET johns-shop.qutekart.com
2. Middleware: InitializeTenancyByDomain
3. Tenancy: Find shop_id from tenant record
4. Context: Set shop_context = shop_id
5. Query: Product::where('shop_id', $shop_id)->get()
6. Result: ONLY John's products
7. Response: John's branded storefront
```

### **Database Schema Changes**

#### **Enhance `shops` table:**
```php
Schema::table('shops', function (Blueprint $table) {
    // Subscription tier
    $table->enum('subscription_tier', [
        'free', 'starter', 'growth', 'enterprise'
    ])->default('free');

    // Subdomain (only for premium)
    $table->string('subdomain')->nullable()->unique();
    $table->string('custom_domain')->nullable();

    // Branding
    $table->json('branding_settings')->nullable(); // colors, fonts, etc.
    $table->boolean('remove_platform_branding')->default(false);

    // Limits
    $table->integer('products_limit')->nullable();
    $table->integer('orders_per_month')->nullable();
    $table->bigInteger('storage_limit_mb')->default(1024);

    // Features
    $table->boolean('has_subdomain')->default(false);
    $table->boolean('has_custom_domain')->default(false);
    $table->boolean('api_access')->default(false);
    $table->boolean('priority_support')->default(false);

    // Tracking
    $table->integer('current_products_count')->default(0);
    $table->integer('current_month_orders')->default(0);
    $table->bigInteger('storage_used_mb')->default(0);

    $table->index('subdomain');
    $table->index('subscription_tier');
});
```

#### **Keep tenants table (from multi-tenancy package):**
```php
tenants table:
- id (UUID)
- shop_id (references shops - IMPORTANT!)
- owner_email
- status
- trial_ends_at
- created_at, updated_at
- data (JSON - misc settings)

Only created when vendor upgrades to premium tier
```

#### **Enhance `subscriptions` table:**
```php
Schema::table('subscriptions', function (Blueprint $table) {
    // Link to shop instead of tenant
    $table->foreignId('shop_id')->constrained('shops');
    $table->foreignId('plan_id')->constrained('plans');

    // Status
    $table->enum('status', [
        'trialing', 'active', 'past_due',
        'canceled', 'paused'
    ])->default('trialing');

    // Billing
    $table->timestamp('trial_ends_at')->nullable();
    $table->timestamp('current_period_start')->nullable();
    $table->timestamp('current_period_end')->nullable();
    $table->decimal('amount', 10, 2);

    // Stripe
    $table->string('stripe_subscription_id')->nullable();
    $table->string('stripe_customer_id')->nullable();
});
```

---

## 📱 UNIFIED API STRATEGY

### **Key Principle: One API, Multiple Contexts**

**All platforms use the SAME API endpoints:**
- ✅ Mobile app → `/api/*`
- ✅ Main website → `/api/*`
- ✅ Premium subdomains → `/api/*`

**Context is determined by:**
1. **Domain** (qutekart.com vs johns-shop.qutekart.com)
2. **Headers** (optional: X-Shop-ID for mobile app shop filters)
3. **Query params** (optional: ?shop_id=123)

### **API Endpoint Examples**

#### **GET /api/home** (Dashboard/Homepage)
```
Context: qutekart.com
Response: Products from ALL shops

Context: johns-shop.qutekart.com
Response: Products ONLY from John's shop + John's branding
```

#### **GET /api/products**
```
Context: qutekart.com
Response: All active products

Context: johns-shop.qutekart.com
Response: Only John's products

Mobile App (browsing main marketplace):
Request: GET /api/products
Response: All products

Mobile App (viewing specific shop):
Request: GET /api/products?shop_id=1
Response: Only that shop's products
```

#### **POST /api/cart/store**
```
Works the same everywhere!
Cart items track shop_id
Checkout splits orders by shop automatically
```

### **Middleware Stack**

```php
// routes/api.php
Route::middleware(['api'])->group(function () {
    // Public endpoints (no auth)
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/shops', [ShopController::class, 'index']);

    // Authenticated endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/cart/store', [CartController::class, 'store']);
        Route::post('/place-order', [OrderController::class, 'store']);
    });
});

// For premium vendor subdomains
Route::middleware(['web', 'tenant.optional'])->group(function () {
    // If tenant detected → filter by shop_id
    // If no tenant → show all
});
```

---

## 🎯 IMPLEMENTATION ROADMAP

### **PHASE 1: Foundation (Week 1-2)**

#### **Step 1: Modify Tenancy Config**
```php
// config/tenancy.php

// DON'T create separate databases
'database' => [
    'template_tenant_connection' => null, // Use main connection
    'managers' => [
        // Remove database managers - we don't need them!
    ],
],

// Add custom tenant identification
'tenant_model' => App\Models\Tenant::class,
'domain_model' => Stancl\Tenancy\Database\Models\Domain::class,
```

#### **Step 2: Create Custom Tenant Model**
```php
// app/Models/Tenant.php
class Tenant extends Model implements TenantContract
{
    // Link to shop
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Override to NOT create databases
    public function createDatabase() {
        // Do nothing - we use shared database
    }

    // Set shop context instead
    public function run(callable $callback)
    {
        $originalShopId = app('current_shop_id');
        app()->instance('current_shop_id', $this->shop_id);

        try {
            return $callback($this);
        } finally {
            app()->instance('current_shop_id', $originalShopId);
        }
    }
}
```

#### **Step 3: Database Migrations**
```bash
# Modify shops table
php artisan make:migration add_premium_fields_to_shops_table

# Create shop_tiers table (optional - could use plans)
php artisan make:migration create_shop_tiers_table

# Link tenants to shops
php artisan make:migration add_shop_id_to_tenants_table
```

#### **Step 4: Modify Existing Controllers**
```php
// app/Http/Controllers/API/ProductController.php

public function index(Request $request)
{
    $query = Product::query();

    // If on premium subdomain, filter by shop
    if ($currentShopId = app('current_shop_id')) {
        $query->where('shop_id', $currentShopId);
    }

    // If explicit shop filter (mobile app)
    if ($shopId = $request->shop_id) {
        $query->where('shop_id', $shopId);
    }

    // Otherwise show all products (main marketplace)

    return ProductResource::collection(
        $query->where('is_active', true)->paginate()
    );
}
```

### **PHASE 2: Premium Features (Week 3-4)**

#### **Step 5: Vendor Upgrade Flow**
```
1. Vendor logs into /shop/dashboard
2. Sees "Upgrade to Premium" banner
3. Clicks → Pricing page showing tiers
4. Selects plan (Growth $99 or Enterprise $299)
5. Enters subdomain choice (checks availability)
6. Adds payment method (Stripe)
7. Confirms subscription
8. System:
   - Creates tenant record
   - Creates domain record
   - Updates shop.subscription_tier
   - Provisions subdomain
   - Redirects to {subdomain}.qutekart.com
```

#### **Step 6: Subdomain Provisioning**
```php
// app/Services/SubdomainProvisioner.php

public function provisionSubdomain(Shop $shop, string $subdomain)
{
    // 1. Create tenant
    $tenant = Tenant::create([
        'id' => Str::uuid(),
        'shop_id' => $shop->id,
        'owner_email' => $shop->user->email,
        'status' => 'active',
    ]);

    // 2. Create domain
    $tenant->domains()->create([
        'domain' => "{$subdomain}.qutekart.com",
    ]);

    // 3. Update shop
    $shop->update([
        'subdomain' => $subdomain,
        'has_subdomain' => true,
        'subscription_tier' => 'growth',
    ]);

    // 4. Configure DNS (Digital Ocean API)
    $this->createDNSRecord($subdomain);

    // 5. Send welcome email
    Mail::to($shop->user->email)->send(new SubdomainActivated($shop));

    return $tenant;
}

private function createDNSRecord(string $subdomain)
{
    // Use Digital Ocean API to create A record
    // {subdomain}.qutekart.com → Your server IP
    // Or CNAME → qutekart.com
}
```

#### **Step 7: Premium Storefront**
```php
// resources/views/premium/storefront.blade.php

@extends('layouts.premium')

@section('content')
    {{-- Use shop's branding settings --}}
    <div style="
        --primary-color: {{ $shop->branding_settings['primary_color'] ?? '#000' }};
        --font-family: {{ $shop->branding_settings['font'] ?? 'Arial' }};
    ">
        <header>
            <img src="{{ $shop->logo }}" alt="{{ $shop->name }}">
            <nav>
                <a href="/">Home</a>
                <a href="/products">Products</a>
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
            </nav>
        </header>

        {{-- Show ONLY this shop's products --}}
        <div class="products-grid">
            @foreach($products as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>

        @if(!$shop->remove_platform_branding)
            <footer>
                Powered by <a href="https://qutekart.com">QuteCart</a>
            </footer>
        @endif
    </div>
@endsection
```

### **PHASE 3: Mobile App Integration (Week 5)**

#### **Step 8: Flutter App Enhancements**
```dart
// lib/config/app_constants.dart

// Add shop-specific base URL support
static String getBaseUrl({String? shopSubdomain}) {
  if (shopSubdomain != null) {
    return 'https://$shopSubdomain.qutekart.com/api';
  }
  return 'https://qutekart.com/api'; // Main marketplace
}

// Shop discovery
static const String discoverShops = '$baseUrl/shops';

// Shop-specific browsing
static String getShopProducts(int shopId) =>
  '$baseUrl/products?shop_id=$shopId';
```

**New Features:**
- "Browse by Shop" tab in app
- Shop directory/discovery
- Ability to visit premium vendor's branded experience
- Deep links: `qutekart://shop/johns-electronics`

### **PHASE 4: Launch & Scale (Week 6+)**

#### **Step 9: Infrastructure**
- Deploy to Digital Ocean
- Configure wildcard DNS: `*.qutekart.com`
- Set up wildcard SSL certificate
- Configure CDN for media files
- Set up Redis for caching

#### **Step 10: Monitoring**
- Track premium subscription conversions
- Monitor subdomain usage
- Alert on limit overages
- Usage analytics dashboard

---

## 💰 PRICING TIERS (Revised for Hybrid Model)

### **Free Tier** (No cost)
- Sell on qutekart.com marketplace
- Basic dashboard access
- 50 products limit
- Standard support
- QuteCart branding

### **Starter Tier** ($29/month)
- Everything in Free
- 200 products limit
- 500 orders/month
- Basic analytics
- Email support

### **Growth Tier** ($99/month) ⭐
- Everything in Starter
- **Own subdomain: {shop}.qutekart.com**
- **Branded storefront**
- Remove QuteCart branding
- 1,000 products
- 5,000 orders/month
- Advanced analytics
- Priority support

### **Enterprise Tier** ($299/month)
- Everything in Growth
- **Custom domain support**
- **API access**
- Unlimited products/orders
- 24/7 support
- White-label
- Dedicated account manager

---

## 🔒 SECURITY & DATA ISOLATION

**Important:** Even with subdomains, ALL data stays in ONE database.

**Security Measures:**
1. **Query Scoping:** Always filter by `shop_id`
2. **Middleware:** Verify user has access to shop
3. **API Rate Limiting:** Per shop limits
4. **File Storage:** Shop-specific directories in S3
5. **Session Isolation:** Separate sessions per domain

```php
// Global scope for models
class Product extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('shop', function (Builder $builder) {
            if ($shopId = app('current_shop_id')) {
                $builder->where('shop_id', $shopId);
            }
        });
    }
}
```

---

## 📊 SUMMARY

### **What You Get:**

**Main Marketplace (qutekart.com):**
- ✅ Products from ALL vendors
- ✅ Customers shop across vendors
- ✅ Mobile app shows everything
- ✅ Central checkout & payments

**Premium Vendor Subdomains:**
- ✅ johns-shop.qutekart.com (branded experience)
- ✅ ONLY that vendor's products
- ✅ Custom branding, logo, colors
- ✅ Optional custom domain
- ✅ Products STILL appear on main marketplace

**Mobile App:**
- ✅ Browse main marketplace
- ✅ Filter by specific shops
- ✅ Visit premium vendor stores
- ✅ Unified cart & checkout

**Backend:**
- ✅ Single database (easy to manage)
- ✅ Unified API (no duplication)
- ✅ Simple tenant routing
- ✅ No complex multi-tenancy

### **Benefits:**

**For You (Platform Owner):**
- 💰 Recurring revenue from premium vendors
- 📈 Scalable architecture
- 🛠️ Easy to manage (one database)
- 🚀 Fast performance
- 🔒 Secure by design

**For Free Vendors:**
- 🆓 No cost to get started
- 🌐 Access to your marketplace
- 👥 Share customer base
- 📊 Basic tools to grow

**For Premium Vendors:**
- 🏪 Own branded store
- 🎨 Custom appearance
- 🔗 Custom domain option
- 📈 Advanced features
- 🌟 Stand out from crowd

**For Customers:**
- 🛍️ One-stop shopping (main marketplace)
- 🔍 Discover all vendors
- 💳 Easy checkout
- 📱 Mobile app access
- 🎁 Option to visit favorite vendor's store

---

## 🚀 NEXT STEPS

**Immediate Actions:**
1. ✅ Review this architecture
2. ✅ Decide on pricing tiers
3. ✅ Choose which features to implement first
4. ✅ Set up Digital Ocean infrastructure
5. ✅ Start Phase 1 implementation

**Questions to Answer:**
- Do you want to keep the multi-tenancy changes or start fresh?
- Which tier pricing feels right?
- Want to add more features to any tier?
- Timeline for launch?

---

**This hybrid model gives you the best of both worlds: a thriving marketplace + premium SaaS revenue!** 🎯

Ready to start building? Let me know which phase to tackle first!
