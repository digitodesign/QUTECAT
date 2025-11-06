# 🚀 QUTECAT HYBRID - PRAGMATIC IMPLEMENTATION PLAN

**Philosophy:** Refactor & Modernize, Don't Rebuild
**Timeline:** 4 weeks to production-ready
**Approach:** Incremental, tested, organized

---

## 🎯 THE SMART PLAN

### **What We're NOT Doing:**
❌ Rebuilding the multi-vendor system (it works!)
❌ Separate databases per vendor (unnecessary complexity)
❌ Rewriting all controllers (waste of time)
❌ Breaking the mobile app (it works!)

### **What We ARE Doing:**
✅ Adapting tenancy package for premium subdomains only
✅ Adding premium features to existing shops
✅ Migrating MySQL → PostgreSQL (better performance)
✅ Modernizing infrastructure (Docker, Digital Ocean)
✅ Keeping API backward compatible

---

## 📋 PHASE 1: ADAPT TENANCY FOR HYBRID MODEL (Week 1)

### **Step 1: Link Tenants to Shops**

The tenancy package is already installed. We just need to:
1. Make tenants reference shops (not replace them)
2. Only create tenants for premium vendors
3. Use tenancy for subdomain routing, not data isolation

**Migration:**
```php
// database/migrations/2025_11_06_100000_link_tenants_to_shops.php

public function up()
{
    Schema::table('tenants', function (Blueprint $table) {
        // Link to existing shops table
        $table->foreignId('shop_id')
              ->nullable()
              ->after('id')
              ->constrained('shops')
              ->onDelete('cascade');

        // Make data column nullable (we don't need it)
        $table->json('data')->nullable()->change();

        // Add shop-specific fields
        $table->string('subdomain_slug')->nullable()->unique();
        $table->json('branding_config')->nullable();
        $table->timestamp('subdomain_activated_at')->nullable();

        $table->index('shop_id');
        $table->index('subdomain_slug');
    });
}
```

### **Step 2: Add Premium Fields to Shops**

```php
// database/migrations/2025_11_06_110000_add_premium_fields_to_shops.php

public function up()
{
    Schema::table('shops', function (Blueprint $table) {
        // Subscription tier
        $table->enum('tier', ['free', 'starter', 'growth', 'enterprise'])
              ->default('free')
              ->after('status');

        // Premium features flags
        $table->boolean('has_premium_subdomain')->default(false);
        $table->boolean('has_custom_domain')->default(false);
        $table->boolean('can_remove_branding')->default(false);
        $table->boolean('has_api_access')->default(false);
        $table->boolean('has_priority_support')->default(false);

        // Limits (from plan)
        $table->integer('products_limit')->nullable();
        $table->integer('monthly_orders_limit')->nullable();
        $table->bigInteger('storage_limit_mb')->default(1024);

        // Current usage tracking
        $table->integer('current_products_count')->default(0);
        $table->integer('current_month_orders')->default(0);
        $table->bigInteger('current_storage_mb')->default(0);

        // Custom domain (Growth/Enterprise)
        $table->string('custom_domain')->nullable()->unique();
        $table->boolean('custom_domain_verified')->default(false);

        // Branding customization
        $table->json('brand_colors')->nullable(); // primary, secondary, etc.
        $table->string('custom_font')->nullable();
        $table->text('custom_css')->nullable();

        // Analytics
        $table->timestamp('premium_since')->nullable();
        $table->timestamp('last_limit_check_at')->nullable();

        $table->index('tier');
        $table->index('has_premium_subdomain');
    });
}
```

### **Step 3: Modify Tenancy Config**

```php
// config/tenancy.php - Keep it simple!

'database' => [
    'central_connection' => env('DB_CONNECTION', 'pgsql'),

    // DON'T create separate databases - use main connection
    'template_tenant_connection' => null,

    // DISABLE database managers - we're not creating DBs
    'managers' => [],
],

'bootstrappers' => [
    // Only bootstrap what we need for routing
    Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,

    // Remove DatabaseTenancyBootstrapper - we use single DB!
    // Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
],

'filesystem' => [
    'suffix_base' => 'tenant',
    'disks' => [
        'public', // Each premium vendor gets /storage/tenant{id}/
    ],
],
```

### **Step 4: Create Custom Tenant Model**

```php
// app/Models/Tenant.php - Override default behavior

<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant
{
    use HasDomains;

    // DON'T use HasDatabase - we don't create separate DBs

    /**
     * The shop this tenant represents
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Override to NOT create database
     */
    public function database(): never
    {
        throw new \Exception('Tenants do not have separate databases in hybrid mode');
    }

    /**
     * Set shop context when tenant is initialized
     */
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

    /**
     * Get tenant by subdomain slug
     */
    public static function findBySubdomain(string $subdomain)
    {
        return static::whereHas('domains', function ($query) use ($subdomain) {
            $query->where('domain', "{$subdomain}.qutecart.com");
        })->first();
    }
}
```

---

## 📋 PHASE 2: POSTGRESQL MIGRATION (Week 1-2)

### **Step 1: Install PostgreSQL Locally (Docker)**

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    ports:
      - "8000:8000"
    depends_on:
      - pgsql
      - redis
    environment:
      DB_CONNECTION: pgsql
      DB_HOST: pgsql
      DB_DATABASE: qutecart
      DB_USERNAME: qutecart
      DB_PASSWORD: secret

  pgsql:
    image: postgres:16-alpine
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: qutecart
      POSTGRES_USER: qutecart
      POSTGRES_PASSWORD: secret
    volumes:
      - pgsql_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-EXEC", "pg_isready -U qutecart"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  minio:
    image: minio/minio
    command: server /data --console-address ":9001"
    ports:
      - "9000:9000"
      - "9001:9001"
    environment:
      MINIO_ROOT_USER: qutecart
      MINIO_ROOT_PASSWORD: qutecart123
    volumes:
      - minio_data:/data

volumes:
  pgsql_data:
  minio_data:
```

### **Step 2: Update Database Config**

```php
// config/database.php

'default' => env('DB_CONNECTION', 'pgsql'),

'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'qutecart'),
        'username' => env('DB_USERNAME', 'qutecart'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'schema' => 'public',
        'sslmode' => env('DB_SSLMODE', 'prefer'),
    ],
],
```

### **Step 3: Fix MySQL-Specific Queries**

**Identify issues:**
```bash
# Find MySQL-specific syntax
grep -r "CURDATE()" app/
grep -r "CURTIME()" app/
grep -r "CONCAT_WS" app/
grep -r "GROUP_CONCAT" app/
grep -r "IF(" app/
```

**Create compatibility helper:**
```php
// app/Helpers/DatabaseHelper.php

class DatabaseHelper
{
    public static function currentDate()
    {
        return DB::getDriverName() === 'mysql'
            ? DB::raw('CURDATE()')
            : DB::raw('CURRENT_DATE');
    }

    public static function currentTime()
    {
        return DB::getDriverName() === 'mysql'
            ? DB::raw('CURTIME()')
            : DB::raw('CURRENT_TIME');
    }

    public static function ifNull($column, $default)
    {
        return DB::getDriverName() === 'mysql'
            ? DB::raw("IFNULL({$column}, {$default})")
            : DB::raw("COALESCE({$column}, {$default})");
    }
}
```

**Fix ProductController queries:**
```php
// app/Http/Controllers/API/ProductController.php

// BEFORE (MySQL):
->whereRaw('flash_sales.start_date <= CURDATE()')

// AFTER (PostgreSQL compatible):
->whereDate('flash_sales.start_date', '<=', now()->toDateString())
->whereTime('flash_sales.start_time', '<=', now()->toTimeString())
```

### **Step 4: Test Migration**

```bash
# Run in Docker
docker-compose up -d pgsql

# Run migrations
docker-compose exec app php artisan migrate:fresh --seed

# Test queries
docker-compose exec app php artisan tinker
>>> Product::count()
>>> Shop::with('products')->first()
```

---

## 📋 PHASE 3: PREMIUM VENDOR FEATURES (Week 2-3)

### **Step 1: Vendor Upgrade Flow**

```php
// app/Http/Controllers/Shop/SubscriptionController.php

public function showUpgrade()
{
    $shop = auth()->user()->shop;
    $plans = Plan::where('is_active', true)->orderBy('price')->get();

    return view('shop.subscription.upgrade', compact('shop', 'plans'));
}

public function processUpgrade(Request $request)
{
    $request->validate([
        'plan_id' => 'required|exists:plans,id',
        'subdomain' => 'required|alpha_dash|unique:tenants,subdomain_slug',
        'payment_method' => 'required',
    ]);

    $shop = auth()->user()->shop;
    $plan = Plan::findOrFail($request->plan_id);

    DB::transaction(function () use ($shop, $plan, $request) {
        // 1. Create Stripe subscription
        $subscription = $shop->user->newSubscription('default', $plan->stripe_price_id)
            ->trialDays($plan->trial_days)
            ->create($request->payment_method);

        // 2. Create tenant record (for premium only)
        if (in_array($plan->slug, ['growth', 'enterprise'])) {
            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'shop_id' => $shop->id,
                'subdomain_slug' => $request->subdomain,
            ]);

            // 3. Create domain
            $tenant->domains()->create([
                'domain' => "{$request->subdomain}.qutecart.com",
            ]);
        }

        // 4. Update shop
        $shop->update([
            'tier' => $plan->slug,
            'has_premium_subdomain' => in_array($plan->slug, ['growth', 'enterprise']),
            'products_limit' => $plan->products_limit,
            'monthly_orders_limit' => $plan->orders_per_month,
            'storage_limit_mb' => $plan->storage_limit_mb,
            'can_remove_branding' => $plan->remove_branding,
            'has_api_access' => $plan->api_access,
            'premium_since' => now(),
        ]);

        // 5. Create subscription record
        Subscription::create([
            'shop_id' => $shop->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays($plan->trial_days),
            'stripe_subscription_id' => $subscription->id,
            'amount' => $plan->price,
        ]);
    });

    flash()->success('Upgraded successfully!');

    return redirect()->route('shop.dashboard');
}
```

### **Step 2: Subdomain Routing**

```php
// app/Http/Middleware/InitializeTenancyBySubdomain.php

public function handle($request, Closure $next)
{
    $domain = $request->getHost();

    // If on central domain, continue normally
    if (in_array($domain, config('tenancy.central_domains'))) {
        return $next($request);
    }

    // Check if it's a subdomain
    if (str_ends_with($domain, '.qutecart.com')) {
        $subdomain = str_replace('.qutecart.com', '', $domain);

        // Find tenant
        $tenant = Tenant::whereHas('domains', function ($query) use ($domain) {
            $query->where('domain', $domain);
        })->first();

        if ($tenant) {
            // Initialize tenancy (sets shop context)
            tenancy()->initialize($tenant);

            // Set shop ID in app container
            app()->instance('current_shop_id', $tenant->shop_id);
            app()->instance('is_premium_subdomain', true);
        }
    }

    return $next($request);
}
```

### **Step 3: Context-Aware API**

```php
// app/Http/Controllers/API/ProductController.php - MINIMAL CHANGES!

public function index(Request $request)
{
    $query = Product::query();

    // If on premium subdomain, filter by that shop
    if ($shopId = app('current_shop_id')) {
        $query->where('shop_id', $shopId);
    }

    // Allow explicit shop filter (mobile app)
    elseif ($request->shop_id) {
        $query->where('shop_id', $request->shop_id);
    }

    // Otherwise show all products (main marketplace)

    // Rest of the existing code stays the same!
    $products = $query->isActive()
        ->when($request->search, /* existing search logic */)
        ->when($request->category_id, /* existing category logic */)
        ->paginate($request->per_page ?? 20);

    return ProductResource::collection($products);
}

// Same pattern for HomeController, CartController, etc.
// Just add the shop_id filter at the beginning!
```

---

## 📋 PHASE 4: INFRASTRUCTURE & DEPLOYMENT (Week 3-4)

### **Step 1: Environment Setup**

```bash
# .env for local development
APP_NAME=QuteCart
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=qutecart
DB_USERNAME=qutecart
DB_PASSWORD=secret

REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# S3-compatible storage (MinIO locally)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=qutecart
AWS_SECRET_ACCESS_KEY=qutecart123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=qutecart-media
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Tenancy
TENANCY_ENABLED=true
CENTRAL_DOMAINS=localhost,127.0.0.1,qutecart.test
```

### **Step 2: Development Commands**

```bash
# Dockerfile
FROM php:8.2-fpm-alpine

# Install PostgreSQL extension
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# ... rest of Dockerfile

# Makefile for convenience
.PHONY: up down fresh test

up:
	docker-compose up -d

down:
	docker-compose down

fresh:
	docker-compose exec app php artisan migrate:fresh --seed
	docker-compose exec app php artisan db:seed --class=PlansTableSeeder

test:
	docker-compose exec app php artisan test

shell:
	docker-compose exec app sh
```

### **Step 3: Digital Ocean Deployment**

```yaml
# .do/app.yaml
name: qutecart
region: nyc

services:
  - name: web
    github:
      repo: digitodesign/QUTECAT
      branch: main
    build_command: |
      composer install --no-dev --optimize-autoloader
      npm install && npm run build
    run_command: |
      php artisan config:cache
      php artisan route:cache
      php artisan migrate --force
      php-fpm
    envs:
      - key: APP_KEY
        scope: RUN_TIME
        type: SECRET
      - key: DB_CONNECTION
        value: pgsql
      - key: TENANCY_ENABLED
        value: "true"
    routes:
      - path: /
    health_check:
      http_path: /api/health

databases:
  - name: qutecart-db
    engine: PG
    version: "16"
    production: true

  - name: qutecart-redis
    engine: REDIS
    version: "7"
```

---

## 📝 TESTING STRATEGY

### **Local Testing**

```bash
# Test 1: Main marketplace
curl http://localhost:8000/api/products
# Should return products from ALL shops

# Test 2: Premium subdomain (add to /etc/hosts first)
echo "127.0.0.1 johns-shop.qutecart.test" >> /etc/hosts
curl http://johns-shop.qutecart.test:8000/api/products
# Should return ONLY John's products

# Test 3: Mobile app compatibility
curl -H "X-Shop-ID: 1" http://localhost:8000/api/products
# Should return products from shop 1
```

### **Database Tests**

```php
// tests/Feature/HybridModelTest.php

public function test_main_marketplace_shows_all_products()
{
    $products = $this->get('/api/products')->json('products');

    $this->assertCount(100, $products); // All products
}

public function test_premium_subdomain_shows_only_shop_products()
{
    $tenant = Tenant::first();
    $domain = $tenant->domains()->first()->domain;

    $products = $this->get("http://{$domain}/api/products")
        ->json('products');

    // Only products from this tenant's shop
    $this->assertTrue(
        collect($products)->every(fn($p) => $p['shop_id'] === $tenant->shop_id)
    );
}
```

---

## ✅ CHECKLIST

### Week 1:
- [ ] Create tenant-shop linking migration
- [ ] Add premium fields to shops
- [ ] Modify tenancy config
- [ ] Create custom Tenant model
- [ ] Set up Docker PostgreSQL
- [ ] Migrate database schema
- [ ] Fix PostgreSQL compatibility

### Week 2:
- [ ] Create vendor upgrade flow
- [ ] Implement subdomain routing
- [ ] Make API context-aware
- [ ] Test locally with Docker

### Week 3:
- [ ] Premium storefront templates
- [ ] Stripe integration
- [ ] Usage limit tracking
- [ ] Admin dashboard updates

### Week 4:
- [ ] Digital Ocean deployment
- [ ] Wildcard DNS setup
- [ ] SSL certificates
- [ ] Production testing
- [ ] Launch! 🚀

---

## 📚 ORGANIZATION

```
QUTECAT/
├── Documentation/
│   ├── QUTECAT_HYBRID_ARCHITECTURE.md (Complete vision)
│   ├── IMPLEMENTATION_PLAN.md (This file)
│   └── API_DOCUMENTATION.md (Coming soon)
├── Ready eCommerce-Admin with Customer Website/
│   └── install/ (Main application)
│       ├── app/
│       │   ├── Models/
│       │   │   ├── Tenant.php (Custom - linked to Shop)
│       │   │   ├── Shop.php (Enhanced with premium fields)
│       │   │   └── Subscription.php (Enhanced)
│       │   ├── Http/
│       │   │   ├── Controllers/
│       │   │   │   ├── API/ (Minimal changes - add context filter)
│       │   │   │   └── Shop/SubscriptionController.php (New)
│       │   │   └── Middleware/
│       │   │       └── InitializeTenancyBySubdomain.php
│       │   └── Services/
│       │       └── TenantProvisioningService.php (New)
│       ├── config/
│       │   ├── tenancy.php (Modified - no DB creation)
│       │   └── database.php (PostgreSQL)
│       ├── database/
│       │   ├── migrations/ (Central DB)
│       │   │   ├── 2025_11_06_100000_link_tenants_to_shops.php
│       │   │   └── 2025_11_06_110000_add_premium_to_shops.php
│       │   └── seeders/
│       │       └── PlansTableSeeder.php (Already exists!)
│       ├── docker-compose.yml
│       └── Dockerfile
└── FlutterApp/ (No changes needed! API compatible)
```

---

**This plan is pragmatic, incremental, and doesn't break what works!**

Ready to start implementing? 🚀
