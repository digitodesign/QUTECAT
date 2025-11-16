#!/bin/bash
set -e

echo "🚀 Seeding remaining production data..."

cd backend/install

# Seed WalletSeeder (skipped due to previous crash)
echo "  → WalletSeeder"
php artisan db:seed --class=WalletSeeder --force

# Seed demo content
echo "🛍️  Seeding demo content..."
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
echo "🎨 Applying ZARA theme..."
php artisan db:seed --class=ZaraThemeSeeder --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo "✅ Production data seeded successfully!"
