#!/bin/bash

################################################################################
# QuteKart Production Deployment Script for DigitalOcean
#
# Run this script ON YOUR DROPLET after:
# 1. Cloning your repository
# 2. Installing all dependencies (PHP, PostgreSQL, Redis, etc.)
# 3. Creating the database
#
# Usage: bash deploy-script.sh
################################################################################

set -e  # Exit on any error

echo "======================================"
echo "QuteKart Production Deployment Script"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_PATH="/var/www/qutekart/Ready eCommerce-Admin with Customer Website/install"
WEB_USER="www-data"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root (use sudo)${NC}"
    exit 1
fi

echo -e "${GREEN}Step 1: Navigating to project directory...${NC}"
cd "$PROJECT_PATH"

echo -e "${GREEN}Step 2: Installing Composer dependencies...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction

echo -e "${GREEN}Step 3: Setting up environment file...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${YELLOW}⚠️  Please edit .env file with your production credentials${NC}"
    echo -e "${YELLOW}   Run: nano .env${NC}"
    read -p "Press Enter after you've configured .env..."
fi

echo -e "${GREEN}Step 4: Generating application key...${NC}"
php artisan key:generate --force

echo -e "${GREEN}Step 5: Running database migrations...${NC}"
php artisan migrate --force

echo -e "${GREEN}Step 6: Seeding database...${NC}"
php artisan db:seed --force
php artisan db:seed --class=PlansTableSeeder --force
php artisan db:seed --class=ZaraThemeSeeder --force

echo -e "${GREEN}Step 7: Creating storage link...${NC}"
php artisan storage:link

echo -e "${GREEN}Step 8: Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}Step 9: Setting file permissions...${NC}"
chown -R $WEB_USER:$WEB_USER /var/www/qutekart
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}Step 10: Creating installed flag...${NC}"
echo "Installed on $(date)" > storage/installed
chmod 664 storage/installed

echo -e "${GREEN}Step 11: Restarting services...${NC}"
systemctl restart php8.2-fpm
systemctl restart nginx

echo -e "${GREEN}Step 12: Starting queue workers...${NC}"
supervisorctl reread
supervisorctl update
supervisorctl restart qutekart-worker:*

echo ""
echo -e "${GREEN}======================================"
echo "✅ Deployment Complete!"
echo "======================================${NC}"
echo ""
echo -e "Your site should now be live at: ${GREEN}https://qutekart.com${NC}"
echo ""
echo "Next steps:"
echo "1. Create admin user: php artisan tinker"
echo "2. Test the site in your browser"
echo "3. Check logs: tail -f storage/logs/laravel.log"
echo ""
echo -e "${YELLOW}Don't forget to configure:${NC}"
echo "  - Stripe webhooks"
echo "  - DigitalOcean Spaces"
echo "  - SSL certificate (certbot)"
echo ""
