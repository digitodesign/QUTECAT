# QuteCart - Complete Setup & Deployment Guide

**Version:** 2.0 (SaaS Edition with ZARA Branding)
**Last Updated:** 2025-11-06
**Estimated Time:** 2-3 hours (local) | 4-5 hours (production)

This guide walks you through setting up QuteCart from scratch, including all customizations we've made:
- ✅ Multi-tenant SaaS features with subscriptions
- ✅ ZARA-style minimalist branding
- ✅ Flutter mobile app integration
- ✅ Production deployment configuration

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Part 1: Local Development Setup](#part-1-local-development-setup)
3. [Part 2: SaaS Features Configuration](#part-2-saas-features-configuration)
4. [Part 3: Branding Customization](#part-3-branding-customization)
5. [Part 4: Flutter Mobile App Setup](#part-4-flutter-mobile-app-setup)
6. [Part 5: Production Deployment](#part-5-production-deployment)
7. [Part 6: Testing & Verification](#part-6-testing--verification)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software

**For Backend Development:**
- **Docker Desktop** (recommended) or individual services:
  - PHP 8.2+
  - PostgreSQL 16
  - Redis 7
  - Nginx
- **Git**
- **Composer** (if not using Docker)
- **Node.js 18+** and npm (for frontend assets)

**For Mobile App Development:**
- **Flutter SDK** 3.5.0 or higher
- **Android Studio** (for Android development)
- **Xcode** (for iOS development, macOS only)
- **VS Code** or **Android Studio** with Flutter plugin

**For Production:**
- **Linux server** (Ubuntu 22.04+ recommended)
- **Domain name** (e.g., qutekart.com)
- **SSL certificate** (Let's Encrypt free)
- **Stripe account** (for payment processing)
- **Pusher account** (for real-time features)
- **Firebase account** (for push notifications)

### System Requirements

- **RAM:** 4GB minimum (8GB recommended)
- **Disk Space:** 10GB minimum (20GB recommended)
- **CPU:** 2 cores minimum (4 cores recommended)

### Accounts to Create (Before Starting)

1. **Stripe** - https://stripe.com (free test account)
2. **Pusher** - https://pusher.com (free tier available)
3. **Firebase** - https://console.firebase.google.com (free tier)
4. **Resend** (optional) - https://resend.com (for production emails)

---

## Part 1: Local Development Setup

**Time Required:** 30-45 minutes

### Step 1.1: Clone the Repository

```bash
# Clone the project
git clone <your-repository-url> QuteCart
cd QuteCart
```

### Step 1.2: Navigate to Laravel Directory

```bash
cd "Ready eCommerce-Admin with Customer Website/install"
```

### Step 1.3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env
```

**Edit `.env` file** with these essential settings:

```bash
# Application
APP_NAME="QuteCart"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://qutekart.local

# Database (Docker defaults)
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=qutekart
DB_USERNAME=qutekart
DB_PASSWORD=secret

# Redis (Docker defaults)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (Docker defaults - Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@qutekart.local"
MAIL_FROM_NAME="QuteCart"

# MinIO/S3 (Docker defaults)
FILESYSTEM_DISK=minio
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=qutekart
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=http://localhost:9000/qutekart

# Queue
QUEUE_CONNECTION=redis

# Broadcast (for real-time features)
BROADCAST_DRIVER=pusher

# Pusher (for local development, use these test values)
PUSHER_APP_ID=local
PUSHER_APP_KEY=local_key
PUSHER_APP_SECRET=local_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# Business Model (IMPORTANT!)
BUSINESS_MODEL=multi  # or 'single' for regular multi-vendor

# Stripe (get from https://dashboard.stripe.com/test/apikeys)
STRIPE_KEY=pk_test_your_key_here
STRIPE_SECRET=sk_test_your_secret_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Firebase (for push notifications)
FIREBASE_SERVER_KEY=your_firebase_server_key_here
```

### Step 1.4: Add Local Domains

**macOS/Linux:**
```bash
sudo nano /etc/hosts
```

**Windows:**
Open `C:\Windows\System32\drivers\etc\hosts` as Administrator

**Add these lines:**
```
127.0.0.1    qutekart.local
127.0.0.1    admin.qutekart.local
127.0.0.1    shop1.qutekart.local
127.0.0.1    shop2.qutekart.local
127.0.0.1    testshop.qutekart.local
```

### Step 1.5: Start Docker Services

```bash
# Start all containers (first run takes 3-5 minutes)
docker-compose up -d

# Watch startup logs
docker-compose logs -f
```

**Expected containers:**
- `qutekart_nginx` - Web server
- `qutekart_php` - PHP-FPM application
- `qutekart_pgsql` - PostgreSQL database
- `qutekart_redis` - Redis cache/queue
- `qutekart_queue` - Laravel queue worker
- `qutekart_scheduler` - Laravel scheduler
- `qutekart_minio` - S3-compatible storage
- `qutekart_mailpit` - Email testing

**Verify all services are running:**
```bash
docker-compose ps
# All services should show "Up" status
```

### Step 1.6: Install PHP Dependencies

```bash
# Install Composer packages
docker-compose exec php composer install

# Generate application key
docker-compose exec php php artisan key:generate
```

### Step 1.7: Run Database Migrations

```bash
# Run all migrations (creates all tables)
docker-compose exec php php artisan migrate

# You should see migration confirmations
```

### Step 1.8: Seed the Database

```bash
# Seed base data (categories, settings, etc.)
docker-compose exec php php artisan db:seed

# Seed SaaS subscription plans
docker-compose exec php php artisan db:seed --class=PlansTableSeeder

# Create admin user (if not already created)
docker-compose exec php php artisan db:seed --class=AdminSeeder
```

### Step 1.9: Install Frontend Assets

```bash
# Install npm dependencies
docker-compose exec php npm install

# Build assets
docker-compose exec php npm run dev
# Or for production build: npm run build
```

### Step 1.10: Create MinIO Bucket

```bash
# Access MinIO container
docker-compose exec minio mc alias set minio http://localhost:9000 minioadmin minioadmin

# Create bucket
docker-compose exec minio mc mb minio/qutekart

# Set public read policy
docker-compose exec minio mc anonymous set download minio/qutekart
```

### Step 1.11: Verify Setup

**Access the application:**
- **Main Site:** http://qutekart.local
- **Admin Panel:** http://qutekart.local/admin
- **MinIO Console:** http://localhost:9001 (minioadmin/minioadmin)
- **Mailpit (Email Testing):** http://localhost:8025

**Test database connection:**
```bash
docker-compose exec php php artisan migrate:status
# Should show all migrations as "Ran"
```

**Test queue is running:**
```bash
docker-compose logs queue
# Should show "Processing jobs..."
```

### Step 1.12: Create Test Admin User (if needed)

```bash
docker-compose exec php php artisan tinker
```

```php
$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@qutekart.local',
    'password' => bcrypt('password123'),
    'role' => 'admin'
]);
exit
```

---

## Part 2: SaaS Features Configuration

**Time Required:** 30-45 minutes

Our QuteCart setup includes comprehensive SaaS features for multi-tenant subscription management.

### Step 2.1: Verify Subscription Plans

```bash
docker-compose exec php php artisan tinker
```

```php
// View all plans
App\Models\Plan::all();

// You should see:
// 1. Free Plan (0 products, 10 orders/month)
// 2. Starter Plan - $29/month (100 products, 500 orders/month)
// 3. Growth Plan - $99/month (1000 products, 5000 orders/month)
// 4. Enterprise Plan - $299/month (unlimited)

exit
```

**If plans don't exist, seed them:**
```bash
docker-compose exec php php artisan db:seed --class=PlansTableSeeder
```

### Step 2.2: Configure Stripe Integration

**Get Stripe API Keys:**
1. Go to https://dashboard.stripe.com/test/apikeys
2. Copy **Publishable key** (starts with `pk_test_`)
3. Copy **Secret key** (starts with `sk_test_`)

**Update `.env`:**
```bash
STRIPE_KEY=pk_test_your_actual_key_here
STRIPE_SECRET=sk_test_your_actual_secret_here
```

**Restart PHP container:**
```bash
docker-compose restart php
```

### Step 2.3: Create Stripe Products and Prices

**Option A: Manual (via Stripe Dashboard)**

1. Go to https://dashboard.stripe.com/test/products
2. Create 4 products:
   - **Free Plan** - $0/month
   - **Starter Plan** - $29/month (recurring)
   - **Growth Plan** - $99/month (recurring)
   - **Enterprise Plan** - $299/month (recurring)
3. Copy each **Price ID** (starts with `price_`)

**Option B: Automated (via Artisan command - if available)**

```bash
docker-compose exec php php artisan stripe:sync-plans
```

### Step 2.4: Update Plans with Stripe IDs

```bash
docker-compose exec php php artisan tinker
```

```php
// Update each plan with Stripe price ID
$starter = App\Models\Plan::where('slug', 'starter')->first();
$starter->stripe_price_id = 'price_YOUR_STARTER_PRICE_ID';
$starter->save();

$growth = App\Models\Plan::where('slug', 'growth')->first();
$growth->stripe_price_id = 'price_YOUR_GROWTH_PRICE_ID';
$growth->save();

$enterprise = App\Models\Plan::where('slug', 'enterprise')->first();
$enterprise->stripe_price_id = 'price_YOUR_ENTERPRISE_PRICE_ID';
$enterprise->save();

exit
```

### Step 2.5: Configure Stripe Webhooks

**For local development (using Stripe CLI):**

```bash
# Install Stripe CLI
# macOS: brew install stripe/stripe-cli/stripe
# Windows: Download from https://stripe.com/docs/stripe-cli

# Login to Stripe
stripe login

# Forward webhooks to local server
stripe listen --forward-to http://qutekart.local/api/stripe/webhook
```

**For production (covered in Part 5):**
- Configure webhook endpoint in Stripe Dashboard

### Step 2.6: Test Subscription Flow

**Create a test shop and subscription:**

```bash
docker-compose exec php php artisan tinker
```

```php
// Create a test shop
$shop = App\Models\Shop::create([
    'name' => 'Test Shop',
    'slug' => 'testshop',
    'email' => 'shop@example.com',
    'phone' => '1234567890',
    'status' => 'active',
]);

// Create a free tier subscription (default)
$subscription = $shop->subscriptions()->create([
    'plan_id' => App\Models\Plan::where('slug', 'free')->first()->id,
    'status' => 'active',
    'starts_at' => now(),
]);

// Check shop limits
echo "Products Limit: " . $shop->products_limit . "\n";
echo "Orders Limit: " . $shop->orders_limit . "\n";
echo "Storage Limit: " . $shop->storage_limit_gb . " GB\n";

exit
```

### Step 2.7: Create Premium Tenant with Subdomain

```bash
docker-compose exec php php artisan tinker
```

```php
// Get a shop
$shop = App\Models\Shop::where('slug', 'testshop')->first();

// Create tenant with subdomain
$tenant = App\Models\Tenant::createForShop($shop, 'testshop', 'starter');

echo "Tenant URL: " . $tenant->subdomain_url . "\n";
// Output: http://testshop.qutekart.local

exit
```

**Add to `/etc/hosts`:**
```
127.0.0.1    testshop.qutekart.local
```

**Visit:** http://testshop.qutekart.local

### Step 2.8: Test Usage Tracking

```bash
docker-compose exec php php artisan tinker
```

```php
// Get shop
$shop = App\Models\Shop::where('slug', 'testshop')->first();

// Add a test product
$product = $shop->products()->create([
    'name' => 'Test Product',
    'slug' => 'test-product',
    'price' => 29.99,
    'status' => 'active',
]);

// Check usage
echo "Current Products: " . $shop->current_products_count . "\n";
echo "Remaining: " . $shop->remaining_products . "\n";
echo "Can Add More? " . ($shop->canAddProduct() ? 'Yes' : 'No') . "\n";

exit
```

### Step 2.9: Configure Email Notifications

**Emails are automatically sent for:**
- Subscription created
- Subscription updated/changed
- Payment succeeded
- Payment failed
- Trial ending (7 days before)
- Subscription cancelled

**Test email (view in Mailpit):**

```bash
docker-compose exec php php artisan tinker
```

```php
// Send test subscription email
$shop = App\Models\Shop::first();
$subscription = $shop->subscriptions()->first();

Mail::to($shop->email)->send(
    new App\Mail\Subscription\SubscriptionCreated($shop, $subscription)
);

// View email at: http://localhost:8025
exit
```

### Step 2.10: Review Admin Dashboard

**Access admin panel:**
- URL: http://qutekart.local/admin
- Login with admin credentials

**Navigate to:**
- **Subscription Management** → All Subscriptions
- **Subscription Management** → Subscription Plans
- **Shops** → View shop list (shows Plan and Usage columns)
- **Shops** → Click on a shop → See Subscription Information and Usage & Limits

---

## Part 3: Branding Customization

**Time Required:** 15-30 minutes

We've customized QuteCart with ZARA-style minimalist branding (black/white/gray palette).

### Step 3.1: Understanding the Theme System

QuteCart has a database-driven theme color system:
- Colors stored in `theme_colors` table
- Automatically generates CSS variants (50-950 shades)
- Updates `public/assets/css/style.css` dynamically

### Step 3.2: Apply ZARA Style Theme

**Method A: Using Seeder (Recommended)**

```bash
# Apply ZARA color palette
docker-compose exec php php artisan db:seed --class=ZaraThemeSeeder

# This sets:
# - Primary: #000000 (Pure Black)
# - Secondary: #F5F5F5 (Light Gray)
# - 11 grayscale variants
```

**Method B: Via Admin UI**

1. Login to admin panel: http://qutekart.local/admin
2. Go to **Settings** → **Theme Colors** (or **Appearance**)
3. Set:
   - **Primary Color:** `#000000`
   - **Secondary Color:** `#F5F5F5`
4. Click **Save** (CSS files auto-update)

**Method C: Manual Database Update**

```bash
docker-compose exec php php artisan tinker
```

```php
App\Models\ThemeColor::query()->delete();

App\Models\ThemeColor::create([
    'primary' => '#000000',
    'secondary' => '#F5F5F5',
    'variant_50' => '#FAFAFA',
    'variant_100' => '#F5F5F5',
    'variant_200' => '#E5E5E5',
    'variant_300' => '#D4D4D4',
    'variant_400' => '#A3A3A3',
    'variant_500' => '#000000',
    'variant_600' => '#1A1A1A',
    'variant_700' => '#333333',
    'variant_800' => '#4D4D4D',
    'variant_900' => '#666666',
    'variant_950' => '#808080',
    'is_default' => true,
]);

exit
```

### Step 3.3: Apply Custom ZARA Styles

The ZARA custom CSS is already created at `public/assets/css/custom-zara.css`.

**Include in layout file:**

Edit `resources/views/layouts/app.blade.php` (or your main layout):

```blade
<head>
    ...
    <!-- Default styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <!-- ZARA custom styles (add this line) -->
    <link rel="stylesheet" href="{{ asset('assets/css/custom-zara.css') }}">
</head>
```

**Or publish as default style:**

```bash
# Backup existing style.css
docker-compose exec php cp public/assets/css/style.css public/assets/css/style.css.backup

# Append ZARA styles
docker-compose exec php sh -c "cat public/assets/css/custom-zara.css >> public/assets/css/style.css"
```

### Step 3.4: Update App Logo and Favicon

**Replace logo files:**

```bash
# Your logo files should be:
# - public/assets/images/logo.png (dark logo for light backgrounds)
# - public/assets/images/logo-light.png (white logo for dark backgrounds)
# - public/assets/images/favicon.ico (browser favicon)

# Use your own logo files or simple black text on white
```

**Update in database:**

```bash
docker-compose exec php php artisan tinker
```

```php
DB::table('master_settings')->updateOrInsert(
    ['key' => 'site_logo'],
    ['value' => 'assets/images/logo.png']
);

DB::table('master_settings')->updateOrInsert(
    ['key' => 'site_logo_light'],
    ['value' => 'assets/images/logo-light.png']
);

DB::table('master_settings')->updateOrInsert(
    ['key' => 'site_favicon'],
    ['value' => 'assets/images/favicon.ico']
);

exit
```

### Step 3.5: Verify ZARA Branding

**Clear caches:**
```bash
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan view:clear
docker-compose exec php php artisan config:clear
```

**Visit site:**
- Go to http://qutekart.local
- Colors should be black/white/gray
- Buttons should be black with minimal style
- No box shadows
- Sharp corners (no border-radius)
- Clean typography

**Reference documentation:**
- Complete guide: `docs/branding/ZARA_STYLE_CUSTOMIZATION_GUIDE.md`
- Color palette details included

---

## Part 4: Flutter Mobile App Setup

**Time Required:** 45-60 minutes

### Step 4.1: Navigate to Flutter App Directory

```bash
cd /path/to/QuteCart/FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode
```

### Step 4.2: Install Flutter Dependencies

```bash
# Get all packages
flutter pub get

# Verify Flutter installation
flutter doctor

# Fix any issues shown by flutter doctor
```

### Step 4.3: Configure App Constants

**Edit:** `lib/config/app_constants.dart`

**For local development:**

```dart
class AppConstants {
  // Point to local Laravel API
  static const String baseUrl = 'http://qutekart.local/api';
  // Or use your machine's local IP (for real device testing):
  // static const String baseUrl = 'http://192.168.1.100/api';

  // Pusher (use local/test values)
  static String pusherApiKey = 'local_key';
  static String pusherCluster = 'mt1';

  // Service type
  static String appServiceName = 'ecommerce';
}
```

### Step 4.4: Configure Firebase (Local Testing)

**You can skip Firebase for local development** (push notifications won't work in emulator anyway).

**For real device testing, you need Firebase:**

See detailed guide: `FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/android/app/FIREBASE_SETUP_REQUIRED.md`

**Quick setup:**
1. Create Firebase project: https://console.firebase.google.com/
2. Add Android app with package: `com.readyecommerce.apps`
3. Download `google-services.json`
4. Place in: `android/app/google-services.json`

### Step 4.5: Update App Branding

**Edit:** `lib/config/app_color.dart`

**Change primary color to ZARA black:**

```dart
class EcommerceAppColor {
  static const Color white = Color(0xFFFFFFFF);
  static const Color offWhite = Color(0xFFF1F1F5);
  static const Color black = Color(0xFF000000);
  static const Color gray = Color(0xFF617986);
  static const Color lightGray = Color(0xFF979899);

  // Change this from pink to black
  static Color primary = const Color(0xFF000000);  // ZARA Black

  static const Color red = Color(0xFFFF2424);
  static const Color green = Color(0xFF1EDD31);
}
```

**Update app name:**

Edit `android/app/src/main/AndroidManifest.xml`:

```xml
<application
    android:label="QuteCart"
    ...
```

Edit `ios/Runner/Info.plist`:

```xml
<key>CFBundleName</key>
<string>QuteCart</string>
```

### Step 4.6: Test on Emulator

**Android:**
```bash
# List available emulators
flutter emulators

# Launch emulator
flutter emulators --launch <emulator_id>

# Or just run (auto-launches emulator)
flutter run
```

**iOS (macOS only):**
```bash
# Launch iOS simulator
open -a Simulator

# Run app
flutter run
```

### Step 4.7: Test on Real Device

**Android:**

1. Enable Developer Mode on your Android device
2. Enable USB Debugging
3. Connect via USB
4. Run:
   ```bash
   flutter devices
   flutter run
   ```

**iOS (macOS only):**

1. Connect iPhone via USB
2. Open in Xcode: `open ios/Runner.xcworkspace`
3. Select your device
4. Run from Xcode or:
   ```bash
   flutter run
   ```

### Step 4.8: Build Release APK (Android)

```bash
# Clean build
flutter clean
flutter pub get

# Build release APK
flutter build apk --release

# APK location:
# build/app/outputs/flutter-apk/app-release.apk
```

**Install on device:**
```bash
# Via ADB
adb install build/app/outputs/flutter-apk/app-release.apk

# Or copy APK to device and install manually
```

### Step 4.9: Test App Features

**Test checklist:**
- [ ] App launches successfully
- [ ] Home screen loads products from Laravel API
- [ ] Product images load correctly
- [ ] Product details page shows video player (if product has video)
- [ ] Add to cart works
- [ ] Cart persists offline (Hive local storage)
- [ ] Checkout flow works
- [ ] User registration/login works
- [ ] Real-time chat opens (may not work without Pusher config)
- [ ] Push notifications work (needs Firebase config)

**Documentation:**
- Complete Flutter guide: `docs/mobile-app/FLUTTER_APP_SETUP_GUIDE.md`
- Configuration checklist: `FlutterApp/CONFIGURATION_CHECKLIST.md`

---

## Part 5: Production Deployment

**Time Required:** 2-3 hours

### Step 5.1: Server Requirements

**Recommended Setup:**
- **OS:** Ubuntu 22.04 LTS
- **RAM:** 4GB minimum (8GB recommended)
- **Storage:** 40GB SSD
- **CPU:** 2 cores minimum
- **Domain:** qutekart.com (with DNS configured)

**Software Stack:**
- PHP 8.2+
- PostgreSQL 16
- Redis 7
- Nginx
- Supervisor (for queue workers)
- Certbot (for SSL)

### Step 5.2: Server Initial Setup

**SSH to your server:**
```bash
ssh root@your_server_ip
```

**Update system:**
```bash
apt update && apt upgrade -y
```

**Install required packages:**
```bash
# PHP 8.2 and extensions
apt install -y php8.2-fpm php8.2-cli php8.2-pgsql php8.2-redis php8.2-mbstring \
               php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl

# PostgreSQL
apt install -y postgresql postgresql-contrib

# Redis
apt install -y redis-server

# Nginx
apt install -y nginx

# Supervisor (for queue workers)
apt install -y supervisor

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Certbot (for SSL)
apt install -y certbot python3-certbot-nginx
```

### Step 5.3: Create Database

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE qutekart_prod;
CREATE USER qutekart_user WITH ENCRYPTED PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE qutekart_prod TO qutekart_user;
\q
```

### Step 5.4: Clone and Configure Application

```bash
# Create web directory
mkdir -p /var/www/qutekart
cd /var/www/qutekart

# Clone repository
git clone <your-repository-url> .

# Navigate to Laravel directory
cd "Ready eCommerce-Admin with Customer Website/install"

# Copy environment file
cp .env.example .env
```

**Edit `.env` for production:**

```bash
nano .env
```

```bash
# Application
APP_NAME="QuteCart"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://qutekart.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=qutekart_prod
DB_USERNAME=qutekart_user
DB_PASSWORD=your_secure_password_here

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (use Resend or your SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_your_api_key_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@qutekart.com"
MAIL_FROM_NAME="QuteCart"

# S3/MinIO (use AWS S3 or DigitalOcean Spaces)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=qutekart-prod
AWS_URL=https://your-bucket.s3.amazonaws.com

# Queue
QUEUE_CONNECTION=redis

# Broadcast
BROADCAST_DRIVER=pusher

# Pusher (PRODUCTION CREDENTIALS)
PUSHER_APP_ID=your_production_app_id
PUSHER_APP_KEY=your_production_key
PUSHER_APP_SECRET=your_production_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# Business Model
BUSINESS_MODEL=multi

# Stripe (PRODUCTION CREDENTIALS)
STRIPE_KEY=pk_live_your_production_key
STRIPE_SECRET=sk_live_your_production_secret
STRIPE_WEBHOOK_SECRET=whsec_your_production_webhook_secret

# Firebase
FIREBASE_SERVER_KEY=AAAA_your_production_server_key

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

### Step 5.5: Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm install

# Build production assets
npm run build

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed plans
php artisan db:seed --class=PlansTableSeeder --force

# Apply ZARA branding
php artisan db:seed --class=ZaraThemeSeeder --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage
php artisan storage:link
```

### Step 5.6: Set Permissions

```bash
# Set owner
chown -R www-data:www-data /var/www/qutekart

# Set permissions
chmod -R 755 /var/www/qutekart
chmod -R 775 /var/www/qutekart/storage
chmod -R 775 /var/www/qutekart/bootstrap/cache
```

### Step 5.7: Configure Nginx

```bash
nano /etc/nginx/sites-available/qutekart
```

```nginx
# Main domain and admin
server {
    listen 80;
    server_name qutekart.com www.qutekart.com;
    root /var/www/qutekart/Ready eCommerce-Admin with Customer Website/install/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}

# Wildcard subdomain for tenants
server {
    listen 80;
    server_name *.qutekart.com;
    root /var/www/qutekart/Ready eCommerce-Admin with Customer Website/install/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 100M;
}
```

**Enable site:**
```bash
ln -s /etc/nginx/sites-available/qutekart /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Step 5.8: Configure SSL (Let's Encrypt)

```bash
# Get SSL certificate
certbot --nginx -d qutekart.com -d www.qutekart.com -d *.qutekart.com

# Note: Wildcard certificates require DNS verification
# Follow certbot instructions to add DNS TXT record

# Auto-renewal is configured automatically
```

### Step 5.9: Configure Queue Worker (Supervisor)

```bash
nano /etc/supervisor/conf.d/qutekart-worker.conf
```

```ini
[program:qutekart-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/qutekart/Ready eCommerce-Admin with Customer Website/install/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/qutekart/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
supervisorctl reread
supervisorctl update
supervisorctl start qutekart-worker:*
```

### Step 5.10: Configure Scheduler (Cron)

```bash
crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install && php artisan schedule:run >> /dev/null 2>&1
```

### Step 5.11: Configure Stripe Production Webhooks

1. Go to https://dashboard.stripe.com/webhooks
2. Click **"Add endpoint"**
3. **Endpoint URL:** `https://qutekart.com/api/stripe/webhook`
4. **Events to send:**
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.trial_will_end`
5. Copy **Signing secret** (starts with `whsec_`)
6. Add to `.env`: `STRIPE_WEBHOOK_SECRET=whsec_...`
7. Restart: `php artisan config:cache`

### Step 5.12: Flutter App Production Configuration

**See:** `FlutterApp/PRODUCTION_DEPLOYMENT_STATUS.md`

**Key steps:**

1. **Update API URL** in `lib/config/app_constants.dart`:
   ```dart
   static const String baseUrl = 'https://qutekart.com/api';
   ```

2. **Update Pusher credentials** (get from Laravel `.env`):
   ```dart
   static String pusherApiKey = 'your_production_key';
   static String pusherCluster = 'mt1';
   ```

3. **Create Firebase project** and download config files:
   - Android: `google-services.json` → `android/app/`
   - iOS: `GoogleService-Info.plist` → `ios/Runner/`

4. **Add Firebase Server Key to Laravel** `.env`:
   ```
   FIREBASE_SERVER_KEY=AAAA...your_server_key
   ```

5. **Build release:**
   ```bash
   flutter build apk --release          # Android
   flutter build appbundle --release    # Android (for Play Store)
   flutter build ipa --release          # iOS (macOS only)
   ```

**Detailed guides:**
- `docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md`
- `FlutterApp/CONFIGURATION_CHECKLIST.md`

### Step 5.13: DNS Configuration

**Configure DNS records at your domain registrar:**

```
# A Records
@           A       your_server_ip
www         A       your_server_ip
*           A       your_server_ip  (wildcard for subdomains)

# Optional: CAA record for Let's Encrypt
@           CAA     0 issue "letsencrypt.org"
```

**Wait for DNS propagation** (can take up to 48 hours, usually faster).

### Step 5.14: Production Verification

**Test main site:**
```bash
curl https://qutekart.com
# Should return HTML
```

**Test API:**
```bash
curl https://qutekart.com/api/master
# Should return JSON with app settings
```

**Test subdomain:**
```bash
# Create a tenant first, then test
curl https://testshop.qutekart.com
```

**Test SSL:**
```bash
openssl s_client -connect qutekart.com:443 -servername qutekart.com
# Should show valid certificate
```

**Test queue:**
```bash
supervisorctl status qutekart-worker
# Should show "RUNNING"
```

**Monitor logs:**
```bash
# Laravel logs
tail -f /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/storage/logs/laravel.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# Queue worker logs
tail -f /var/www/qutekart/worker.log
```

---

## Part 6: Testing & Verification

### Backend Testing Checklist

- [ ] Main site loads: https://qutekart.com
- [ ] Admin panel accessible: https://qutekart.com/admin
- [ ] Can login to admin
- [ ] Subscription plans visible in admin
- [ ] Can create a new shop
- [ ] Shop appears in admin shops list with plan info
- [ ] Can create a product for a shop
- [ ] Usage stats update correctly
- [ ] Can create a subdomain tenant
- [ ] Subdomain site loads: https://{subdomain}.qutekart.com
- [ ] Emails are sent (check inbox)
- [ ] Queue is processing jobs
- [ ] Webhooks receive from Stripe
- [ ] Can upgrade a shop's plan
- [ ] Stripe subscription created successfully
- [ ] Usage limits enforced correctly

### Mobile App Testing Checklist

- [ ] App builds successfully
- [ ] App connects to production API
- [ ] Home screen loads products
- [ ] Product images load
- [ ] Product videos play
- [ ] Can add products to cart
- [ ] Cart persists offline
- [ ] Can register new account
- [ ] Can login
- [ ] Can browse by category
- [ ] Can search products
- [ ] Can view shop details
- [ ] Can checkout (test payment)
- [ ] Real-time chat works
- [ ] Push notifications received
- [ ] App branding correct (ZARA black theme)

### Performance Testing

**Test page load speed:**
```bash
curl -o /dev/null -s -w "Time: %{time_total}s\n" https://qutekart.com
# Should be < 2 seconds
```

**Test API response time:**
```bash
curl -o /dev/null -s -w "Time: %{time_total}s\n" https://qutekart.com/api/products
# Should be < 1 second
```

**Monitor server resources:**
```bash
htop
# Check CPU, RAM usage
```

### Security Testing

- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] SSL certificate valid
- [ ] Admin panel requires authentication
- [ ] API endpoints require authentication (where appropriate)
- [ ] File upload restrictions working
- [ ] CSRF protection enabled
- [ ] XSS protection enabled
- [ ] SQL injection protection (via Laravel ORM)
- [ ] Rate limiting configured

---

## Troubleshooting

### Common Issues

#### Issue: "500 Internal Server Error"

**Solution:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx logs
tail -f /var/log/nginx/error.log

# Ensure permissions are correct
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear cache
php artisan cache:clear
php artisan config:cache
```

#### Issue: "Database connection failed"

**Solution:**
```bash
# Test PostgreSQL connection
psql -U qutekart_user -d qutekart_prod -h 127.0.0.1

# Check .env database credentials
cat .env | grep DB_

# Restart PostgreSQL
systemctl restart postgresql
```

#### Issue: "Queue not processing jobs"

**Solution:**
```bash
# Check supervisor status
supervisorctl status qutekart-worker

# Restart workers
supervisorctl restart qutekart-worker:*

# Check worker logs
tail -f /var/www/qutekart/worker.log

# Manually run worker to see errors
php artisan queue:work --tries=1
```

#### Issue: "Subdomain not working"

**Solution:**
```bash
# Verify DNS wildcard record
dig *.qutekart.com

# Check Nginx config
nginx -t

# Verify tenant exists in database
php artisan tinker
App\Models\Tenant::all();

# Restart Nginx
systemctl restart nginx
```

#### Issue: "Stripe webhook not working"

**Solution:**
```bash
# Check webhook secret in .env
cat .env | grep STRIPE_WEBHOOK

# Test webhook endpoint
curl -X POST https://qutekart.com/api/stripe/webhook

# Check Laravel logs for webhook errors
tail -f storage/logs/laravel.log

# Verify webhook in Stripe dashboard
# https://dashboard.stripe.com/webhooks
```

#### Issue: "Flutter app can't connect to API"

**Solution:**
```bash
# Verify API URL in app_constants.dart
cat lib/config/app_constants.dart | grep baseUrl

# Test API from device
# On Android emulator, use: http://10.0.2.2 instead of localhost
# On iOS simulator, use: http://localhost or http://127.0.0.1
# On real device, use: https://qutekart.com/api

# Check API CORS settings in Laravel
cat config/cors.php
```

#### Issue: "Push notifications not working"

**Solution:**
```bash
# Verify Firebase config files exist
ls -la android/app/google-services.json
ls -la ios/Runner/GoogleService-Info.plist

# Check Firebase server key in Laravel .env
cat .env | grep FIREBASE_SERVER_KEY

# Test push notification from Firebase console
# Firebase Console → Cloud Messaging → Send test message

# Check app has notification permissions
# Android: Settings → Apps → QuteCart → Permissions → Notifications
```

### Getting Help

**Documentation:**
- Architecture: `docs/architecture/`
- Features: `docs/features/`
- Mobile App: `docs/mobile-app/`
- Branding: `docs/branding/`

**Logs to check:**
- Laravel: `storage/logs/laravel.log`
- Nginx: `/var/log/nginx/error.log`
- Queue: `/var/www/qutekart/worker.log`
- PostgreSQL: `/var/log/postgresql/postgresql-*.log`

**Useful commands:**
```bash
# Check all services
docker-compose ps                    # Local
systemctl status nginx php8.2-fpm postgresql redis  # Production

# Monitor logs in real-time
tail -f storage/logs/laravel.log

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear

# Restart all services
docker-compose restart               # Local
systemctl restart nginx php8.2-fpm postgresql redis supervisor  # Production
```

---

## Summary Checklist

### Local Development
- [ ] Docker running with 8 containers
- [ ] Laravel accessible at http://qutekart.local
- [ ] Database migrated and seeded
- [ ] Subscription plans created
- [ ] ZARA branding applied
- [ ] Admin panel accessible
- [ ] Flutter app running on emulator
- [ ] Test shop created with products

### Production Deployment
- [ ] Server configured with all requirements
- [ ] Domain DNS configured
- [ ] SSL certificate installed
- [ ] Laravel deployed and configured
- [ ] Database migrated and seeded
- [ ] Stripe production webhooks configured
- [ ] Queue workers running via Supervisor
- [ ] Scheduler configured via cron
- [ ] Nginx serving HTTPS with wildcard subdomain
- [ ] Flutter app configured for production
- [ ] Firebase configured for push notifications
- [ ] All tests passing

---

## Quick Reference

### Common Commands

**Local Development:**
```bash
# Start
docker-compose up -d

# Stop
docker-compose down

# Artisan
docker-compose exec php php artisan [command]

# Logs
docker-compose logs -f [service]

# Shell
docker-compose exec php sh
```

**Production:**
```bash
# Deploy updates
cd /var/www/qutekart
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart qutekart-worker:*
```

### Access Points

**Local:**
- Main: http://qutekart.local
- Admin: http://qutekart.local/admin
- MinIO: http://localhost:9001
- Mailpit: http://localhost:8025
- PostgreSQL: localhost:5432

**Production:**
- Main: https://qutekart.com
- Admin: https://qutekart.com/admin
- API: https://qutekart.com/api
- Subdomain: https://{subdomain}.qutekart.com

### Documentation Index

- **Setup:** `SETUP.md` (basic local setup)
- **This Guide:** `COMPLETE_SETUP_GUIDE.md` (comprehensive)
- **Architecture:** `docs/architecture/QUTECAT_HYBRID_ARCHITECTURE.md`
- **Docker:** `DOCKER_ARCHITECTURE.md`
- **SaaS Features:** `PRODUCTION_READY.md`
- **Branding:** `docs/branding/ZARA_STYLE_CUSTOMIZATION_GUIDE.md`
- **Mobile App:** `docs/mobile-app/FLUTTER_APP_SETUP_GUIDE.md`
- **Production Config:** `docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md`
- **Compatibility:** `COMPATIBILITY_ANALYSIS.md`

---

**🎉 You're all set! QuteCart is ready for development and deployment.**

**Support:** Check documentation in `docs/` folder or review logs for troubleshooting.
