# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

**QuteCart** is a multi-tenant SaaS e-commerce platform with:
- **Backend**: Laravel 11 (PHP 8.2+) with PostgreSQL and Redis
- **Mobile App**: Flutter (Dart 3.5+) for Android/iOS
- **Architecture**: Multi-tenant with subdomain-based tenant identification
- **Key Features**: Subscription management (Stripe), real-time chat (Pusher), video uploads, multi-vendor marketplace

## Development Commands

### Backend (Laravel)

**Working Directory**: Always run Laravel commands from `backend/install/`

```powershell
# Development with Docker (recommended)
cd backend/install
docker-compose up -d                                    # Start all services
docker-compose exec php composer install                # Install dependencies
docker-compose exec php php artisan migrate             # Run migrations
docker-compose exec php php artisan db:seed             # Seed database
docker-compose exec php php artisan config:cache        # Cache config
docker-compose exec php php artisan route:cache         # Cache routes
docker-compose exec php php artisan config:clear        # Clear config cache
docker-compose down                                     # Stop all services

# Local development without Docker
cd backend/install
composer install                                        # Install dependencies
php artisan serve --host=0.0.0.0 --port=8000           # Start dev server
php artisan queue:work redis --sleep=3 --tries=3       # Start queue worker

# Testing
cd backend/install
php artisan test                                        # Run all tests
php artisan test --filter=SubscriptionTest              # Run specific test
vendor/bin/phpunit tests/Feature/                       # Run feature tests
vendor/bin/phpunit tests/Unit/                          # Run unit tests

# Code Quality
cd backend/install
vendor/bin/pint                                         # Format code (Laravel Pint)
vendor/bin/pint --test                                  # Check formatting without changes

# Database
cd backend/install
php artisan migrate:fresh --seed                        # Fresh database with seeds
php artisan migrate:rollback                            # Rollback last migration
php artisan migrate:status                              # Check migration status
php artisan db:seed --class=ZaraThemeSeeder            # Apply ZARA branding

# View all routes
cd backend/install
php artisan route:list                                  # All routes
php artisan route:list --path=api                       # API routes only
php artisan route:list --path=admin                     # Admin routes only

# Debugging
cd backend/install
php artisan tinker                                      # Interactive shell
```

### Frontend (Flutter)

**Working Directory**: All Flutter commands run from `FlutterApp/`

```powershell
# Development
cd FlutterApp
flutter pub get                                         # Install dependencies
flutter run                                             # Run on connected device/emulator
flutter run -d chrome                                   # Run on web browser
flutter run --release                                   # Run in release mode

# Building
cd FlutterApp
flutter build apk --release                             # Build Android APK
flutter build appbundle --release                       # Build Android App Bundle (Play Store)
flutter build ios --release                             # Build iOS app

# Testing
cd FlutterApp
flutter test                                            # Run all tests
flutter test test/unit/                                 # Run unit tests
flutter test test/widget/                               # Run widget tests

# Code Quality
cd FlutterApp
flutter analyze                                         # Analyze code for issues
flutter format .                                        # Format all Dart files
flutter format --set-exit-if-changed .                  # Check formatting

# Cleaning
cd FlutterApp
flutter clean                                           # Clean build artifacts
flutter pub cache repair                                # Repair pub cache
```

### Production Deployment

```powershell
# Backend deployment (Railway/DigitalOcean)
cd backend/install
composer install --optimize-autoloader --no-dev         # Production dependencies
php artisan config:cache                                # Cache config
php artisan route:cache                                 # Cache routes
php artisan view:cache                                  # Cache views
php artisan migrate --force                             # Run migrations

# Start production services
cd backend/install
php -S 0.0.0.0:$PORT server.php                        # Start web server
php artisan queue:work redis --sleep=3 --tries=3 --timeout=60 --memory=512 --verbose  # Queue worker
```

## Project Architecture

### Multi-Tenant Structure

This platform uses **subdomain-based multi-tenancy**:
- Central domain: `qutekart.com` (admin, landing page)
- Tenant subdomains: `{shop-slug}.qutekart.com` (vendor stores)
- Tenancy managed via `stancl/tenancy` package
- Tenant identification: `IdentifyTenant` middleware detects shop from subdomain, header (`X-Shop-Id`), or query param

### Backend Structure (Laravel)

```
backend/install/
├── app/
│   ├── Models/                    # Eloquent models
│   │   ├── Shop.php              # Enhanced with SaaS methods (isFreeTier, canAddProduct)
│   │   ├── Plan.php              # Subscription plans
│   │   ├── Subscription.php      # Shop subscriptions
│   │   ├── Tenant.php            # Multi-tenant model
│   │   └── Product.php           # Products with video support
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/              # Mobile app API
│   │   │   │   ├── SubscriptionController.php
│   │   │   │   ├── ProductController.php (context-aware)
│   │   │   │   └── [15+ more controllers]
│   │   │   ├── Admin/            # Admin dashboard
│   │   │   ├── Seller/           # Vendor dashboard
│   │   │   └── Rider/            # Delivery rider
│   │   └── Middleware/
│   │       ├── SetShopContext.php        # Auto-detect shop context
│   │       ├── CheckShopLimits.php       # Enforce subscription limits
│   │       └── ShopAuthenticate.php      # Shop-specific auth
│   ├── Services/                  # Business logic layer
│   │   └── Subscription/
│   │       ├── StripeSubscriptionService.php    # Stripe integration
│   │       └── UsageTrackingService.php         # Usage monitoring
│   └── Events/                    # Domain events
│       └── [Subscription events: Created, Updated, Cancelled]
├── routes/
│   ├── api.php                   # Customer/mobile API routes
│   ├── web.php                   # Central domain (admin) routes
│   └── tenant.php                # Subdomain routes (vendor stores)
└── database/
    └── migrations/               # Database schema (PostgreSQL)
```

**Key Architectural Patterns:**
1. **Service Layer Pattern**: Business logic in `app/Services/`, controllers stay thin
2. **Repository Pattern**: Data access via repositories in `app/Repositories/`
3. **Event-Driven**: Domain events in `app/Events/`, listeners in `app/Listeners/`
4. **Middleware-based Context**: `SetShopContext` middleware auto-detects current shop
5. **Multi-database**: Central DB for global data, tenant DB isolation via `stancl/tenancy`

### Frontend Structure (Flutter)

```
FlutterApp/
├── lib/
│   ├── models/              # Data models
│   ├── services/            # API clients (Dio HTTP)
│   ├── controllers/         # Business logic (Riverpod state management)
│   ├── views/               # UI screens
│   ├── components/          # Reusable widgets
│   ├── utils/               # Helper functions
│   └── config/              # App configuration
│       └── app_constants.dart  # API URLs, Pusher keys
├── assets/                  # Images, fonts, JSON
└── test/                    # Tests
```

**Key Dependencies:**
- State management: `flutter_riverpod`
- HTTP client: `dio` with `pretty_dio_logger`
- Local storage: `hive` (offline cart)
- Real-time: `pusher_channels_flutter`
- Video player: `chewie` + `video_player`
- Push notifications: `firebase_messaging`

### Database Structure

**PostgreSQL** with Redis cache/queue

**SaaS Tables:**
- `plans` - Subscription plans (Free, Starter, Growth, Enterprise)
- `subscriptions` - Shop subscriptions with usage tracking
- `tenants` - Subdomain tenant metadata
- `domains` - Custom domain mappings

**Core Tables:**
- `shops` - Vendor shops (enhanced with SaaS fields: limits, usage)
- `products` - Products with `video_id` foreign key
- `orders`, `carts`, `users`, `categories`, `media`
- 50+ additional tables for full e-commerce functionality

### API Structure

All APIs follow RESTful conventions:

**Public APIs** (no auth):
- `GET /api/products` - List products (shop context filtering)
- `GET /api/categories`, `GET /api/shops`, `GET /api/flash-sales`

**Authenticated APIs** (`auth:sanctum` middleware):
- `POST /api/auth/login`, `POST /api/auth/register`
- `GET /api/user`, `POST /api/user/update`
- Cart: `GET /api/cart`, `POST /api/cart`
- Orders: `GET /api/orders`, `POST /api/orders`
- Subscriptions: `GET /api/subscription/plans`, `POST /api/subscription/subscribe`

**Vendor APIs** (`/api/seller/*`):
- Dashboard, products, orders management

**Webhooks**:
- `POST /api/webhooks/stripe` - Stripe subscription events

## Environment Configuration

### Backend (.env essentials)

```bash
# App
APP_ENV=local
APP_URL=http://qutekart.local

# Database
DB_CONNECTION=pgsql
DB_HOST=pgsql  # or localhost
DB_PORT=5432
DB_DATABASE=qutekart
DB_USERNAME=qutekart
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis  # or localhost
REDIS_PORT=6379

# Stripe (production)
STRIPE_KEY=pk_live_xxxxx
STRIPE_SECRET=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

# Pusher (real-time chat)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1

# Firebase (push notifications)
FIREBASE_SERVER_KEY=AAAA...

# Email (production - use Resend)
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_your_api_key

# Storage (production - use DigitalOcean Spaces)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=qutekart-prod
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
```

### Flutter (lib/config/app_constants.dart)

```dart
static String baseUrl = 'https://qutekart.com/api';
static String pusherApiKey = 'your_pusher_key';
static String pusherCluster = 'mt1';
```

## Important Implementation Details

### Multi-Tenant Context Detection

The `SetShopContext` middleware automatically detects the current shop via:
1. Subdomain: `shopname.qutekart.com` → finds shop by slug
2. Header: `X-Shop-Id: 123` → finds shop by ID
3. Query param: `?shop_id=123` → finds shop by ID

**Always ensure shop context is set before filtering products/orders.**

### Subscription Limits Enforcement

The `CheckShopLimits` middleware enforces plan limits:
- Free tier: 50 products, no videos, no subdomain
- Paid tiers: Higher limits, premium features

**When adding products/features, check limits via:**
```php
$shop->canAddProduct()
$shop->canUploadVideo()
$shop->hasSubdomainAccess()
```

### Video Upload Architecture

Products can have videos via two methods:
1. **File upload**: Stored in S3/Spaces, referenced via `media` table
2. **External URL**: YouTube/Vimeo/Dailymotion embed links

**Relationship**: `products.video_id` → `media.id`

### Queue Workers

Background jobs run via Redis queue:
- Email notifications (subscription events, orders)
- Image processing
- Report generation

**Always ensure queue worker is running in production.**

### Docker Services

8 containers for local development:
- `nginx` - Web server (port 80)
- `php` - PHP-FPM application
- `pgsql` - PostgreSQL 16
- `redis` - Cache & queue
- `minio` - S3-compatible storage (port 9000)
- `mailpit` - Email testing (port 8025)
- `queue` - Laravel queue worker
- `scheduler` - Cron scheduler

## Common Gotchas

1. **Always work in `backend/install/`, not `backend/update/`** - The `update/` folder is reference only
2. **Shop context required for product queries** - Use `SetShopContext` middleware or manually set `current_shop_id`
3. **PostgreSQL not MySQL** - Some Laravel features differ (e.g., `fulltext` indexes)
4. **Predis v3.2.0** - Redis client library version is pinned
5. **Firebase config in mobile app uses DEMO project** - Replace `google-services.json` and `GoogleService-Info.plist` with your own
6. **Pusher credentials in Flutter are test keys** - Update in production
7. **Stripe webhooks must be configured** - Subscription updates won't work without webhooks
8. **Queue workers needed** - Emails and notifications won't send without queue worker running

## Documentation References

Additional documentation in the `docs/` directory:
- `PRODUCTION_READINESS_REPORT.md` - Complete production checklist
- `docs/CODEBASE_ORGANIZATION.md` - Detailed file organization
- `docs/architecture/QUTECAT_HYBRID_ARCHITECTURE.md` - System architecture
- `docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md` - Mobile app setup
- `docs/branding/ZARA_STYLE_CUSTOMIZATION_GUIDE.md` - UI/UX styling

## Code Style & Conventions

### Laravel (PHP)
- Follow PSR-12 coding standard
- Use Laravel Pint for formatting: `vendor/bin/pint`
- Controllers should be thin - move logic to Services
- Use type hints for parameters and return types
- Use strict comparison (`===`) over loose (`==`)

### Flutter (Dart)
- Follow official Dart style guide
- Use `flutter format` before committing
- Prefer `const` constructors where possible
- Use Riverpod for state management (not Provider or Bloc)
- Keep widgets focused and composable

## Testing Strategy

### Laravel Tests
- Feature tests in `tests/Feature/` - Test HTTP endpoints
- Unit tests in `tests/Unit/` - Test Services and Models
- Database tests use in-memory SQLite when possible

### Flutter Tests
- Widget tests for UI components
- Unit tests for controllers/services
- Integration tests for full user flows

## Production Checklist

Before deploying to production:
- [ ] Update Stripe live keys and create webhook
- [ ] Replace Firebase config files (Android & iOS)
- [ ] Update Pusher production credentials
- [ ] Configure email service (Resend recommended)
- [ ] Setup S3/Spaces storage
- [ ] Run `php artisan config:cache` and `route:cache`
- [ ] Ensure queue worker and scheduler are running
- [ ] Enable SSL/TLS on domain
- [ ] Test subscription flow end-to-end
