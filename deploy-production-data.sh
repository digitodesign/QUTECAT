#!/bin/bash

# Deploy Production Data Script for QuteCart
# This script runs on Railway to seed the database with initial data

set -e  # Exit on error

echo "========================================"
echo "QuteCart Production Data Deployment"
echo "========================================"

cd backend/install

# Check if already seeded
echo "Checking if database is already seeded..."
php artisan tinker --execute="echo App\\Models\\User::count() > 0 ? 'seeded' : 'empty';" > /tmp/db_status.txt || true
DB_STATUS=$(cat /tmp/db_status.txt | grep -o -E "seeded|empty" || echo "unknown")

if [ "$DB_STATUS" = "seeded" ]; then
    echo "✓ Database already contains data"
    echo "Applying ZARA theme if not already applied..."
    php artisan db:seed --class=ZaraThemeSeeder --force
    echo "✓ ZARA theme applied"
else
    echo "Database is empty. Running full seed..."
    
    # Run migrations
    echo "Running migrations..."
    php artisan migrate --force
    
    # Seed essential data
    echo "Seeding essential system data..."
    php artisan db:seed --class=RoleSeeder --force
    php artisan db:seed --class=PermissionSeeder --force
    php artisan db:seed --class=CurrencySeeder --force
    php artisan db:seed --class=GeneraleSettingSeeder --force
    php artisan db:seed --class=LegalPageSeeder --force
    php artisan db:seed --class=PaymentGatewaySeeder --force
    php artisan db:seed --class=SocialLinkSeeder --force
    php artisan db:seed --class=ThemeColorSeeder --force
    php artisan db:seed --class=SocialAuthSeeder --force
    php artisan db:seed --class=VerifyManageSeeder --force
    php artisan db:seed --class=PageSeeder --force
    php artisan db:seed --class=MenuSeeder --force
    php artisan db:seed --class=CountrySeeder --force
    php artisan db:seed --class=FooterSeeder --force
    php artisan db:seed --class=PlansTableSeeder --force
    php artisan db:seed --class=WalletSeeder --force
    
    # Seed demo data for production
    echo "Seeding demo content..."
    php artisan db:seed --class=UserSeeder --force
    php artisan db:seed --class=CustomerSeeder --force
    php artisan db:seed --class=RiderSeeder --force
    php artisan db:seed --class=ShopSeeder --force
    php artisan db:seed --class=CategorySeeder --force
    php artisan db:seed --class=BrandSeeder --force
    php artisan db:seed --class=SizeSeeder --force
    php artisan db:seed --class=ColorSeeder --force
    php artisan db:seed --class=UnitSeeder --force
    php artisan db:seed --class=ProductSeeder --force
    php artisan db:seed --class=BannerSeeder --force
    php artisan db:seed --class=CouponSeeder --force
    php artisan db:seed --class=AddressSeeder --force
    php artisan db:seed --class=BlogSeeder --force
    php artisan db:seed --class=RootAdminShopSeeder --force
    
    # Apply ZARA theme
    echo "Applying ZARA theme..."
    php artisan db:seed --class=ZaraThemeSeeder --force
    
    echo "✓ Database seeded successfully"
fi

# Clear all caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache for production
echo "Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "========================================"
echo "Deployment Complete!"
echo "========================================"
echo ""
echo "Login Credentials:"
echo "  Root Admin:"
echo "    Email: root@qutekart.com"
echo "    Password: secret"
echo ""
echo "  Demo Shop:"
echo "    Email: shop@qutekart.com"
echo "    Password: secret"
echo ""
echo "Visit: https://qutecat.up.railway.app"
echo "Admin Panel: https://qutecat.up.railway.app/admin"
echo "========================================"
