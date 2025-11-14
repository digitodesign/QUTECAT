# QuteCart SaaS Codebase Organization

## Project Structure Philosophy

We follow **Laravel conventions** with clear separation of concerns. Every file has a specific purpose and location. No scattered files.

## Directory Structure

```
QUTECAT/
├── Ready eCommerce-Admin with Customer Website/
│   ├── install/                              # Main working directory (our modifications)
│   │   ├── app/
│   │   │   ├── Models/                       # Eloquent models
│   │   │   │   ├── Tenant.php               # Custom Tenant model (links to Shop)
│   │   │   │   ├── Shop.php                 # Enhanced with premium features
│   │   │   │   ├── Plan.php                 # Subscription plans
│   │   │   │   ├── Subscription.php         # Vendor subscriptions
│   │   │   │   └── ShopSubscription.php     # Existing (we'll enhance this)
│   │   │   │
│   │   │   ├── Http/
│   │   │   │   ├── Middleware/
│   │   │   │   │   ├── IdentifyTenant.php   # Detect subdomain tenant
│   │   │   │   │   └── SetShopContext.php   # Set current_shop_id context
│   │   │   │   │
│   │   │   │   └── Controllers/
│   │   │   │       ├── API/                  # Mobile app & web API
│   │   │   │       │   └── (existing controllers - we'll enhance)
│   │   │   │       │
│   │   │   │       └── Admin/
│   │   │   │           ├── SubscriptionController.php   # Manage subscriptions
│   │   │   │           └── PlanController.php           # Manage plans
│   │   │   │
│   │   │   ├── Services/                    # Business logic layer
│   │   │   │   ├── Subscription/
│   │   │   │   │   ├── StripeSubscriptionService.php
│   │   │   │   │   ├── UsageTrackingService.php
│   │   │   │   │   └── PlanUpgradeService.php
│   │   │   │   │
│   │   │   │   └── Tenancy/
│   │   │   │       └── TenantIdentificationService.php
│   │   │   │
│   │   │   ├── Repositories/                # Data access layer (already exists)
│   │   │   │   └── (existing - follow same pattern)
│   │   │   │
│   │   │   ├── Helpers/                     # Utility functions
│   │   │   │   └── DatabaseCompatibility.php  # PostgreSQL helpers
│   │   │   │
│   │   │   └── Providers/
│   │   │       ├── TenancyServiceProvider.php  # Already exists
│   │   │       └── SaasServiceProvider.php     # Our SaaS features
│   │   │
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   │   ├── [timestamp]_create_tenants_table.php           # Tenancy
│   │   │   │   ├── [timestamp]_create_domains_table.php           # Tenancy
│   │   │   │   ├── [timestamp]_create_plans_table.php             # Our SaaS
│   │   │   │   ├── [timestamp]_create_subscriptions_table.php     # Our SaaS
│   │   │   │   ├── [timestamp]_link_tenants_to_shops.php         # Our SaaS
│   │   │   │   └── [timestamp]_add_premium_fields_to_shops.php   # Our SaaS
│   │   │   │
│   │   │   └── seeders/
│   │   │       ├── PlansTableSeeder.php       # Already exists
│   │   │       └── DatabaseSeeder.php         # Existing
│   │   │
│   │   ├── config/
│   │   │   ├── tenancy.php                    # Tenancy config (modified)
│   │   │   ├── installer.php                  # Modified (license disabled)
│   │   │   ├── services.php                   # Add Stripe keys
│   │   │   └── saas.php                       # NEW: Our SaaS settings
│   │   │
│   │   ├── routes/
│   │   │   ├── web.php                        # Central domain routes
│   │   │   ├── api.php                        # API routes (enhanced)
│   │   │   └── tenant.php                     # Premium subdomain routes
│   │   │
│   │   ├── resources/
│   │   │   └── views/
│   │   │       ├── admin/
│   │   │       │   ├── subscriptions/         # Subscription management UI
│   │   │       │   └── plans/                 # Plan management UI
│   │   │       │
│   │   │       └── vendor/                    # Vendor dashboard enhancements
│   │   │
│   │   ├── tests/
│   │   │   ├── Feature/
│   │   │   │   ├── Tenancy/
│   │   │   │   │   └── TenantIdentificationTest.php
│   │   │   │   └── Subscription/
│   │   │   │       └── SubscriptionFlowTest.php
│   │   │   │
│   │   │   └── Unit/
│   │   │       └── Services/
│   │   │           └── StripeSubscriptionServiceTest.php
│   │   │
│   │   ├── docker/                            # NEW: Docker configuration
│   │   │   ├── nginx/
│   │   │   │   └── default.conf
│   │   │   ├── php/
│   │   │   │   └── Dockerfile
│   │   │   └── postgres/
│   │   │       └── init.sql                   # Initial DB setup
│   │   │
│   │   ├── docker-compose.yml                 # NEW: Local development
│   │   ├── .env.example                       # Update with PostgreSQL
│   │   ├── composer.json                      # Already modified (tenancy)
│   │   └── README.md                          # Update with new setup
│   │
│   └── update/                                # Reference only - DO NOT MODIFY
│
├── docs/                                      # NEW: Project documentation
│   ├── QUTECAT_HYBRID_ARCHITECTURE.md        # Move here
│   ├── QUTECAT_SAAS_IMPLEMENTATION_GUIDE.md  # Move here
│   ├── IMPLEMENTATION_PLAN.md                # Move here
│   ├── UPDATE_FOLDER_ANALYSIS.md             # Move here
│   └── api/
│       └── endpoints.md                       # API documentation
│
└── scripts/                                   # NEW: Deployment scripts
    ├── deploy-digital-ocean.sh
    ├── migrate-to-postgres.sh
    └── setup-local-dev.sh
```

## File Naming Conventions

### Migrations
Format: `YYYY_MM_DD_HHMMSS_descriptive_name.php`

**Our SaaS migrations (in order):**
1. `2019_09_15_000010_create_tenants_table.php` - Already exists (tenancy package)
2. `2019_09_15_000020_create_domains_table.php` - Already exists (tenancy package)
3. `2025_11_06_064339_create_plans_table.php` - Already exists
4. `2025_11_06_064349_create_subscriptions_table.php` - Already exists
5. `2025_11_06_100000_link_tenants_to_shops.php` - **TO CREATE**
6. `2025_11_06_110000_add_premium_fields_to_shops.php` - **TO CREATE**

### Models
- PascalCase, singular noun
- Located in `app/Models/`
- Clear, descriptive names

### Services
- Suffix with `Service.php`
- Grouped in subdirectories by domain
- Example: `Subscription/StripeSubscriptionService.php`

### Controllers
- Suffix with `Controller.php`
- RESTful naming when possible
- Group by purpose (Admin, API, Vendor)

### Middleware
- Descriptive action name
- Example: `SetShopContext.php` (not `ShopMiddleware.php`)

## Configuration Files

### New Config: `config/saas.php`
```php
<?php
return [
    // Subscription Plans
    'plans' => [
        'free' => [
            'name' => 'Free Marketplace Vendor',
            'price' => 0,
            'features' => [...],
        ],
        // ...
    ],

    // Feature Limits
    'limits' => [
        'free' => [...],
        'premium' => [...],
    ],

    // Stripe Configuration
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
```

## Service Layer Organization

We use **Service classes** for business logic, keeping controllers thin:

```
app/Services/
├── Subscription/
│   ├── StripeSubscriptionService.php     # Stripe API integration
│   ├── PlanUpgradeService.php            # Upgrade/downgrade logic
│   └── UsageTrackingService.php          # Monitor usage limits
│
└── Tenancy/
    └── TenantIdentificationService.php   # Detect current tenant
```

**Why Services?**
- Controllers stay thin (routing only)
- Business logic is reusable
- Easy to test in isolation
- Clear separation of concerns

## Database Layer

### Existing Pattern (Keep)
The template uses **Repository pattern**. We'll follow the same:

```php
// Existing pattern in codebase
app/Repositories/ProductRepository.php
app/Repositories/ShopRepository.php

// We'll add:
app/Repositories/SubscriptionRepository.php
app/Repositories/PlanRepository.php
```

### Our Enhancements
- Add Eloquent relationships to existing models
- Create new models for Plans, Subscriptions
- Link Tenant model to Shop model

## Docker Organization

```
docker/
├── nginx/
│   └── default.conf              # Nginx virtual host config
├── php/
│   ├── Dockerfile                # PHP 8.2 + extensions
│   └── php.ini                   # Custom PHP settings
└── postgres/
    └── init.sql                  # Initial database setup

docker-compose.yml                # Container orchestration
```

**Why separate docker/ folder?**
- Keeps root directory clean
- Groups all Docker configs together
- Easier to maintain and update

## Documentation Organization

```
docs/
├── architecture/
│   ├── HYBRID_MARKETPLACE.md       # System architecture
│   └── DATABASE_SCHEMA.md          # Database design
│
├── implementation/
│   ├── IMPLEMENTATION_PLAN.md      # Step-by-step plan
│   └── MIGRATION_GUIDE.md          # MySQL → PostgreSQL
│
├── api/
│   ├── REST_API.md                 # API documentation
│   └── MOBILE_APP_INTEGRATION.md   # Flutter app guide
│
└── deployment/
    ├── DIGITAL_OCEAN_SETUP.md      # DO deployment
    └── SSL_CONFIGURATION.md        # HTTPS setup
```

**Why organize docs?**
- Easy to find information
- Logical grouping by purpose
- Professional documentation structure

## Environment Variables Organization

### `.env.example` Structure
```ini
# === Application ===
APP_NAME="QuteCart"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://qutekart.local

# === Database (PostgreSQL) ===
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=qutekart
DB_USERNAME=qutekart
DB_PASSWORD=secret

# === Tenancy ===
CENTRAL_DOMAINS=qutekart.com,qutecat.com,localhost

# === SaaS Subscriptions ===
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# === Storage ===
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# === Existing (keep organized) ===
# Firebase, Pusher, etc.
```

## Git Branch Organization

```
main                                    # Production code
└── claude/review-ecommerce-template-* # Our current work
    ├── Phase 1: Docker + PostgreSQL
    ├── Phase 2: Tenancy + Subscriptions
    ├── Phase 3: Premium Features
    └── Phase 4: Deployment
```

## Testing Organization

```
tests/
├── Feature/                        # Integration tests
│   ├── Tenancy/
│   │   ├── SubdomainRoutingTest.php
│   │   └── ShopContextTest.php
│   │
│   ├── Subscription/
│   │   ├── SubscriptionFlowTest.php
│   │   ├── PlanUpgradeTest.php
│   │   └── UsageLimitTest.php
│   │
│   └── API/
│       └── ContextAwareAPITest.php
│
└── Unit/                           # Unit tests
    └── Services/
        ├── StripeSubscriptionServiceTest.php
        └── TenantIdentificationServiceTest.php
```

## Code Style Guidelines

### PHP (Laravel Standards)
- PSR-12 coding standard
- Type hints everywhere
- DocBlocks for public methods
- Descriptive variable names

### Database
- Snake_case for columns
- Singular table names for pivots
- Descriptive foreign keys: `shop_id`, `user_id`

### Frontend
- Keep existing structure
- Organize by feature, not file type

## Migration Strategy

### From Current State to Organized

1. **Move documentation** to `docs/` folder
2. **Create** `docker/` folder structure
3. **Add** new migrations in correct order
4. **Create** Service classes before Controllers
5. **Test** each component in isolation

### Commit Strategy
- Small, focused commits
- Clear commit messages
- One feature per commit
- Always push after testing

## Key Principles

1. **Follow Laravel Conventions** - Don't reinvent structure
2. **One Responsibility Per File** - Clear, focused files
3. **Logical Grouping** - Related files stay together
4. **Documentation First** - Document before implementing
5. **Test as You Go** - Don't accumulate technical debt

## What NOT to Do

❌ **Don't:**
- Create files in root directory
- Mix concerns in single file
- Use unclear abbreviations
- Scatter related files across directories
- Modify `update/` folder (reference only)

✅ **Do:**
- Follow existing patterns in codebase
- Group related functionality together
- Use descriptive names
- Keep controllers thin, services fat
- Document as you build

## Summary

This organization ensures:
- **Maintainability** - Easy to find and modify code
- **Scalability** - Clear where new features belong
- **Collaboration** - Team can navigate easily
- **Professionalism** - Production-ready structure

Every file has a purpose and a place. No random files.
