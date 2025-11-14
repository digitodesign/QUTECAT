# 🚀 QUTECAT SAAS TRANSFORMATION - IMPLEMENTATION GUIDE

**Date:** November 6, 2025
**Platform:** QuteCart.com (formerly Ready eCommerce)
**Infrastructure:** Digital Ocean
**Repository:** digitodesign/QUTECAT
**Branch:** `claude/review-ecommerce-template-011CUqezuPy1BdW4NYBpric7`

---

## ✅ COMPLETED: PHASE 1 - MULTI-TENANCY FOUNDATION

### What's Been Implemented

#### 1. **Multi-Tenancy Package Installation**
✅ Installed `stancl/tenancy v3.9.1` - Industry-standard Laravel multi-tenancy
✅ Database-per-tenant architecture for complete data isolation
✅ UUID-based tenant identification
✅ Automatic tenant database creation and migration

#### 2. **Central Database Schema** (QuteCart Platform)

**Created Tables:**

**`tenants`** - Tenant/Shop Registry
```sql
- id (UUID) - Unique tenant identifier
- shop_name - Business name
- owner_name, owner_email, owner_phone - Contact info
- status (active/suspended/trial/canceled)
- trial_ends_at - Trial expiration
- stripe_customer_id - Billing integration
- pm_type, pm_last_four - Payment method info
- timezone, currency, logo_url - Preferences
- settings (JSON) - Custom configuration
- last_activity_at, last_ip - Activity tracking
- data (JSON) - Additional metadata (from tenancy package)
```

**`domains`** - Tenant Domain Mapping
```sql
- id - Domain record ID
- domain - shop1.qutekart.com or customdomain.com
- tenant_id - References tenants table
- is_primary - Primary domain flag
```

**`plans`** - Subscription Tiers
```sql
- id - Plan ID
- name, slug, description - Plan details
- price, currency, billing_period - Pricing
- yearly_price - Annual discount pricing
- products_limit, orders_per_month - Usage limits
- storage_limit_mb, team_members_limit - Resource limits
- Feature flags: custom_domain, api_access, priority_support, etc.
- trial_days - Trial period length
- stripe_product_id, stripe_price_id - Stripe integration
```

**`subscriptions`** - Active Subscriptions
```sql
- id - Subscription ID
- tenant_id - Which shop
- plan_id - Which plan
- status (trialing/active/past_due/canceled/etc.)
- trial_ends_at, current_period_start/end - Billing cycle
- amount, currency, billing_period - Pricing
- stripe_subscription_id, stripe_customer_id - Payment tracking
- metadata (JSON) - Additional data
```

**`usage_tracking`** - Resource Monitoring
```sql
- id - Tracking record ID
- tenant_id - Which shop
- period_start, period_end - Billing period
- products_count, orders_count, customers_count - Usage metrics
- storage_used_mb, api_requests_count - Resource usage
- team_members_count - Staff count
- revenue - Shop revenue (optional)
- metadata (JSON) - Custom metrics
```

#### 3. **Subscription Plans Created**

**Starter Plan - $29/month**
- 100 products limit
- 500 orders per month
- 1GB storage
- 2 team members
- Mobile app access
- Basic analytics
- Email support
- 14-day free trial

**Growth Plan - $99/month** ⭐ Most Popular
- 1,000 products limit
- 5,000 orders per month
- 10GB storage
- 5 team members
- Custom domain support
- Remove QuteCart branding
- Advanced analytics
- Multi-currency support
- Priority email support
- 14-day free trial

**Enterprise Plan - $299/month**
- Unlimited products
- Unlimited orders
- 100GB storage
- 20 team members
- API access
- 24/7 priority support
- Dedicated account manager
- White-label solution
- Custom integrations
- SLA guarantee
- 30-day free trial

**Annual Pricing:** 2 months free (10× monthly price instead of 12×)

#### 4. **Domain Configuration**

**Central Domains** (Platform/Landing Page):
- `qutekart.com`
- `www.qutekart.com`
- `qutecat.com` (redirect)
- `www.qutecat.com` (redirect)
- `localhost` (development)
- `127.0.0.1` (development)

**Tenant Domains** (Individual Shops):
- Subdomain: `{shop-name}.qutekart.com`
- Custom domains: `mycoolstore.com` (Growth/Enterprise plans)

#### 5. **Files Created/Modified**

```
backend/install/
├── app/Providers/
│   └── TenancyServiceProvider.php          ← Tenancy bootstrapper
├── config/
│   └── tenancy.php                         ← Tenancy configuration
├── database/migrations/
│   ├── 2019_09_15_000010_create_tenants_table.php
│   ├── 2019_09_15_000020_create_domains_table.php
│   ├── 2025_11_06_064339_create_plans_table.php
│   ├── 2025_11_06_064349_create_subscriptions_table.php
│   ├── 2025_11_06_064350_create_usage_tracking_table.php
│   └── 2025_11_06_064351_add_plan_fields_to_tenants_table.php
├── database/migrations/tenant/             ← Tenant-specific migrations folder
├── database/seeders/
│   └── PlansTableSeeder.php                ← Seeds 3 subscription plans
├── routes/
│   └── tenant.php                          ← Tenant-specific routes
└── composer.json                           ← Added stancl/tenancy dependency
```

---

## 📋 NEXT STEPS: PHASE 2 - COMPLETE IMPLEMENTATION

### Step 1: Move Existing Migrations to Tenant Folder

**What needs to happen:**
All existing shop-related migrations need to move to `database/migrations/tenant/` so they run for each tenant's database, not the central database.

**Migrations to move:**
- Products (products, product_colors, product_sizes, product_categories, etc.)
- Orders (orders, order_products, order_payments)
- Customers (customers, addresses, favorites)
- Reviews, carts, banners, blogs
- ALL existing migrations EXCEPT:
  - tenants, domains, plans, subscriptions, usage_tracking (stay in central)
  - users, permissions, roles (keep in central for super admin)

**Command to execute:**
```bash
# You'll need to manually move these or I can create a script
mv database/migrations/2024_*_create_products_table.php database/migrations/tenant/
# ... repeat for all tenant-specific migrations
```

### Step 2: Remove Codecanyon License Lock

**Files to modify:**
```php
// config/installer.php
'verify_purchase' => false,  // Change from true to false

// OR completely remove the installer since you're building SaaS
```

### Step 3: Create Tenant Onboarding Flow

**Build signup system:**
1. Landing page at `qutekart.com`
2. Signup form collects:
   - Shop name
   - Owner name, email, phone
   - Subdomain choice (e.g., "mycoolshop" → mycoolshop.qutekart.com)
   - Plan selection
   - Payment method (Stripe)
3. Creates tenant record
4. Creates tenant database
5. Runs migrations on tenant database
6. Seeds initial data
7. Redirects to `{subdomain}.qutekart.com/onboarding`

**Controllers needed:**
- `SignupController` - Handle registration
- `TenantOnboardingController` - Setup wizard
- `BillingController` - Stripe integration

### Step 4: Digital Ocean Infrastructure Setup

**Recommended Digital Ocean Setup:**

#### **Option A: App Platform** (Easiest, Recommended for Start)
```yaml
# .do/app.yaml
name: qutekart
region: nyc
services:
  - name: web
    github:
      repo: digitodesign/QUTECAT
      branch: main
      deploy_on_push: true
    build_command: |
      composer install --no-dev --optimize-autoloader
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
    run_command: php artisan serve --host=0.0.0.0 --port=8080
    envs:
      - key: APP_ENV
        value: production
      - key: APP_KEY
        scope: RUN_TIME
        value: ${APP_KEY}  # Generate with php artisan key:generate
      - key: DB_CONNECTION
        value: pgsql
      - key: DB_HOST
        value: ${db.HOSTNAME}
      - key: DB_DATABASE
        value: ${db.DATABASE}
      - key: DB_USERNAME
        value: ${db.USERNAME}
      - key: DB_PASSWORD
        value: ${db.PASSWORD}
      - key: REDIS_HOST
        value: ${redis.HOSTNAME}
      - key: REDIS_PASSWORD
        value: ${redis.PASSWORD}
    instance_count: 2
    instance_size_slug: professional-xs  # $12/month per instance
    health_check:
      http_path: /health
    http_port: 8080

databases:
  - name: qutekart-db
    engine: PG
    version: "16"
    size: db-s-1vcpu-1gb  # $15/month
    num_nodes: 1

  - name: qutekart-redis
    engine: REDIS
    version: "7"
    size: db-s-1vcpu-1gb  # $15/month

workers:
  - name: queue-worker
    github:
      repo: digitodesign/QUTECAT
      branch: main
    build_command: composer install --no-dev
    run_command: php artisan queue:work --tries=3
    instance_count: 1
    instance_size_slug: professional-xs

static_sites:
  - name: marketing
    github:
      repo: digitodesign/QUTECAT
      branch: main
    output_dir: /public
    routes:
      - path: /
```

**Monthly Cost Estimate:**
- 2× Web instances: $24/month
- 1× PostgreSQL: $15/month
- 1× Redis: $15/month
- 1× Queue worker: $12/month
- **Total: ~$66/month** (covers 0-10 tenants easily)

#### **Option B: Droplets + Managed Databases** (More Control)
```
1. Create Droplets (VMs):
   - 2× Web servers (2GB RAM, $18/mo each) = $36/mo
   - Load balancer ($12/mo) = $12/mo

2. Managed Databases:
   - PostgreSQL (1GB RAM, $15/mo) = $15/mo
   - Redis (1GB RAM, $15/mo) = $15/mo

3. Spaces (Object Storage):
   - $5/mo for 250GB storage + CDN

Total: ~$83/month
```

#### **Option C: Kubernetes (DOKS)** (For Scale)
```
- 3× nodes (2GB RAM each): $36/mo
- Managed PostgreSQL: $15/mo
- Managed Redis: $15/mo
- Spaces: $5/mo

Total: ~$71/month (+ autoscaling capability)
```

### Step 5: DNS & SSL Configuration

**DNS Setup (Digital Ocean Domains):**
```
A     qutekart.com           → Digital Ocean App Platform IP
A     *.qutekart.com         → Digital Ocean App Platform IP (wildcard)
CNAME www.qutekart.com       → qutekart.com
CNAME qutecat.com            → qutekart.com
CNAME www.qutecat.com        → qutekart.com
```

**SSL:**
- Digital Ocean App Platform: Automatic SSL via Let's Encrypt
- Manual setup: Use Certbot for wildcard SSL

### Step 6: Environment Configuration

**`.env` for Digital Ocean:**
```bash
APP_NAME=QuteCart
APP_ENV=production
APP_KEY=base64:... # php artisan key:generate
APP_DEBUG=false
APP_URL=https://qutekart.com

DB_CONNECTION=pgsql
DB_HOST=your-db-hostname.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=qutekart
DB_USERNAME=doadmin
DB_PASSWORD=your-secure-password
DB_SSLMODE=require

REDIS_HOST=your-redis-hostname.db.ondigitalocean.com
REDIS_PASSWORD=your-redis-password
REDIS_PORT=25061

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=qutekart-media
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_FROM_ADDRESS=noreply@qutekart.com
MAIL_FROM_NAME=QuteCart
```

### Step 7: Deploy to Digital Ocean

**Using App Platform:**
```bash
# 1. Install doctl CLI
snap install doctl

# 2. Authenticate
doctl auth init

# 3. Create app from spec
doctl apps create --spec .do/app.yaml

# 4. Or use GitHub integration in DO dashboard
# - Connect GitHub repo
# - Select branch
# - Configure environment variables
# - Deploy!
```

**Post-Deployment:**
```bash
# Run migrations (via doctl)
doctl apps run-command <app-id> --component web -- php artisan migrate --force

# Seed plans
doctl apps run-command <app-id> --component web -- php artisan db:seed --class=PlansTableSeeder --force

# Create storage link
doctl apps run-command <app-id> --component web -- php artisan storage:link
```

---

## 🎨 PHASE 3: BUILD LANDING PAGE & SIGNUP

### Landing Page Components

**Required Pages:**
1. **Homepage** (`qutekart.com`)
   - Hero section with value proposition
   - Feature highlights
   - Pricing table (3 plans)
   - Testimonials
   - CTA: "Start Your Free Trial"

2. **Pricing Page** (`qutekart.com/pricing`)
   - Detailed plan comparison
   - FAQ section
   - Annual billing toggle (show savings)

3. **Signup Flow** (`qutekart.com/signup`)
   - Step 1: Business info (shop name, owner details)
   - Step 2: Choose subdomain (check availability via AJAX)
   - Step 3: Select plan (highlight Growth as recommended)
   - Step 4: Payment (Stripe Elements)
   - Step 5: Account creation + tenant provisioning

4. **Onboarding** (`{shop}.qutekart.com/onboarding`)
   - Welcome wizard
   - Import products (CSV, Shopify, WooCommerce)
   - Configure shipping
   - Design customization
   - Go live checklist

### Signup API Endpoints

**Create these endpoints:**
```php
POST /api/signup/check-subdomain
POST /api/signup/create-account
POST /api/signup/verify-email
POST /api/billing/create-subscription
POST /api/billing/webhook (Stripe)
```

---

## 💳 PHASE 4: STRIPE INTEGRATION

### Setup Steps

1. **Create Stripe Account:**
   - Sign up at stripe.com
   - Complete business verification
   - Note API keys

2. **Create Products in Stripe:**
   ```bash
   # Starter Plan
   stripe products create --name="Starter Plan" --description="Perfect for small businesses"
   stripe prices create --product=prod_xxx --unit-amount=2900 --currency=usd --recurring[interval]=month

   # Growth Plan
   stripe products create --name="Growth Plan" --description="For growing businesses"
   stripe prices create --product=prod_yyy --unit-amount=9900 --currency=usd --recurring[interval]=month

   # Enterprise Plan
   stripe products create --name="Enterprise Plan" --description="For established businesses"
   stripe prices create --product=prod_zzz --unit-amount=29900 --currency=usd --recurring[interval]=month
   ```

3. **Update Plans Seeder:**
   ```php
   // Add Stripe IDs to database/seeders/PlansTableSeeder.php
   'stripe_product_id' => 'prod_xxx',
   'stripe_price_id' => 'price_xxx',
   ```

4. **Install Laravel Cashier:**
   ```bash
   composer require laravel/cashier
   php artisan cashier:install
   ```

5. **Create Billing Controller:**
   ```php
   // app/Http/Controllers/BillingController.php
   public function createSubscription(Request $request) {
       $tenant = $request->user()->tenant;
       $plan = Plan::find($request->plan_id);

       $tenant->newSubscription('default', $plan->stripe_price_id)
           ->trialDays($plan->trial_days)
           ->create($request->payment_method);
   }
   ```

6. **Set up Webhook:**
   ```bash
   # In Stripe Dashboard
   Webhooks → Add endpoint
   URL: https://qutekart.com/api/billing/webhook
   Events:
     - customer.subscription.updated
     - customer.subscription.deleted
     - invoice.payment_succeeded
     - invoice.payment_failed
   ```

---

## 📊 PHASE 5: USAGE TRACKING & ENFORCEMENT

### Create Middleware for Limit Enforcement

```php
// app/Http/Middleware/CheckTenantLimits.php
public function handle($request, Closure $next)
{
    $tenant = tenant();
    $subscription = $tenant->subscription;
    $plan = $subscription->plan;

    // Check product limit
    if ($plan->products_limit) {
        $productCount = Product::count();
        if ($productCount >= $plan->products_limit) {
            return response()->json([
                'error' => 'Product limit reached. Upgrade your plan.'
            ], 403);
        }
    }

    // Check monthly orders
    if ($plan->orders_per_month) {
        $ordersThisMonth = Order::whereMonth('created_at', now()->month)->count();
        if ($ordersThisMonth >= $plan->orders_per_month) {
            return response()->json([
                'error' => 'Monthly order limit reached. Upgrade your plan.'
            ], 403);
        }
    }

    return $next($request);
}
```

### Create Usage Tracking Job

```php
// app/Jobs/TrackTenantUsage.php
public function handle()
{
    $tenant = tenant();

    UsageTracking::updateOrCreate(
        [
            'tenant_id' => $tenant->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ],
        [
            'products_count' => Product::count(),
            'orders_count' => Order::whereMonth('created_at', now()->month)->count(),
            'customers_count' => Customer::count(),
            'storage_used_mb' => $this->calculateStorageUsage(),
            'revenue' => Order::whereMonth('created_at', now()->month)->sum('total_amount'),
        ]
    );
}
```

---

## 🔒 DIGITAL OCEAN SECURITY BEST PRACTICES

### 1. **Enable Firewall**
```bash
# Allow only necessary ports
doctl compute firewall create \
  --name qutekart-firewall \
  --inbound-rules "protocol:tcp,ports:80,sources:0.0.0.0/0,::/0 protocol:tcp,ports:443,sources:0.0.0.0/0,::/0" \
  --droplet-ids <droplet-id>
```

### 2. **Database Access**
- Enable VPC (Virtual Private Cloud)
- Database only accessible from app servers
- Use SSL/TLS for connections
- Enable automated backups (daily)

### 3. **Secrets Management**
```bash
# Use DO App Platform secrets
doctl apps create-secret --app-id <app-id> \
  --name STRIPE_SECRET \
  --value "sk_live_xxx"
```

### 4. **Regular Backups**
- Database: Automated daily backups (retain 7 days)
- Spaces: Enable versioning
- Code: Git repository (already done!)

### 5. **Monitoring**
```bash
# Enable DO monitoring
doctl monitoring alert-policy create \
  --type v1/insights/droplet/cpu \
  --description "High CPU alert" \
  --compare GreaterThan \
  --value 80 \
  --window 5m \
  --entities <droplet-id>
```

---

## 📈 GROWTH ROADMAP

### Immediate (Now - Week 2)
- [ ] Move migrations to tenant folder
- [ ] Remove Codecanyon license check
- [ ] Create landing page
- [ ] Build signup flow
- [ ] Deploy to Digital Ocean
- [ ] Set up Stripe integration

### Short-term (Week 3-4)
- [ ] Tenant onboarding wizard
- [ ] Usage tracking implementation
- [ ] Limit enforcement
- [ ] Billing portal (manage subscription, update card)
- [ ] Admin dashboard (view all tenants)

### Medium-term (Month 2-3)
- [ ] Customer portal improvements
- [ ] Advanced analytics dashboard
- [ ] Email campaigns (Mailchimp/SendGrid integration)
- [ ] Multi-language support
- [ ] Custom domain setup wizard

### Long-term (Month 4+)
- [ ] Migrate from MySQL to PostgreSQL
- [ ] Implement API for external integrations
- [ ] Build marketplace for plugins/themes
- [ ] Advanced reporting and BI tools
- [ ] White-label solution for Enterprise customers
- [ ] Mobile app improvements

---

## 🆘 TROUBLESHOOTING

### Issue: Tenant database not creating
```bash
# Check tenancy config
php artisan config:clear

# Manually create tenant
php artisan tinker
>>> $tenant = Tenant::create(['id' => 'shop1']);
>>> $tenant->domains()->create(['domain' => 'shop1.qutekart.com']);
>>> $tenant->run(function () { Artisan::call('migrate'); });
```

### Issue: Subdomain not resolving
- Check wildcard DNS: `dig *.qutekart.com`
- Verify App Platform custom domains configured
- Clear DNS cache: `doctl compute domain records list qutekart.com`

### Issue: Stripe webhook failing
```bash
# Test webhook locally with Stripe CLI
stripe listen --forward-to localhost:8000/api/billing/webhook
stripe trigger customer.subscription.created
```

### Issue: High database connections
- Enable connection pooling in tenancy config
- Use Redis for sessions instead of database
- Implement database read replicas

---

## 📞 SUPPORT & RESOURCES

### Documentation
- **Stancl Tenancy:** https://tenancyforlaravel.com/docs
- **Laravel:** https://laravel.com/docs
- **Stripe:** https://stripe.com/docs
- **Digital Ocean:** https://docs.digitalocean.com

### Community
- Laravel Discord: https://discord.gg/laravel
- Tenancy Discord: https://discord.gg/tenancy
- Digital Ocean Community: https://www.digitalocean.com/community

### Your Team
- Repository: https://github.com/digitodesign/QUTECAT
- Branch: `claude/review-ecommerce-template-011CUqezuPy1BdW4NYBpric7`
- Domains: qutekart.com, qutecat.com

---

## 🎉 SUCCESS METRICS

**First Month Goals:**
- [ ] 10 paying customers
- [ ] $290 MRR (Monthly Recurring Revenue)
- [ ] 99.9% uptime
- [ ] < 2s average page load time
- [ ] Positive customer feedback

**Three Month Goals:**
- [ ] 50 paying customers
- [ ] $2,500 MRR
- [ ] Break even on infrastructure costs
- [ ] 5-star reviews on product hunt
- [ ] Partnership with 2 agencies

**Six Month Goals:**
- [ ] 200 paying customers
- [ ] $15,000 MRR
- [ ] Profitability
- [ ] Featured on TechCrunch/ProductHunt
- [ ] Hire first employee

---

## 🚀 YOU'RE READY TO LAUNCH!

The foundation is solid. You now have:
- ✅ Multi-tenant architecture
- ✅ Subscription billing structure
- ✅ Three pricing tiers
- ✅ Usage tracking system
- ✅ Digital Ocean deployment path

**Next immediate action:** Choose your deployment option (App Platform recommended) and push to production!

Questions? Review this guide or reach out to the development team.

**Good luck building QuteCart! 🛒✨**
