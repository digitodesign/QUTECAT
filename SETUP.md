# QuteCart SaaS - Local Development Setup Guide

## Prerequisites

- Docker Desktop installed and running
- Git
- Terminal access
- At least 4GB free RAM
- At least 10GB free disk space

## Quick Start (5 Minutes)

### 1. Clone and Navigate

```bash
cd "Ready eCommerce-Admin with Customer Website/install"
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Edit .env if needed (defaults work for local development)
# Key settings are already configured for Docker
```

### 3. Add Local Domains

Add these lines to your `/etc/hosts` file:

**macOS/Linux:**
```bash
sudo nano /etc/hosts
```

**Windows:**
```
C:\Windows\System32\drivers\etc\hosts
```

Add:
```
127.0.0.1    qutecart.local
127.0.0.1    shop1.qutecart.local
127.0.0.1    shop2.qutecart.local
```

### 4. Start Docker Services

```bash
# Start all containers
docker-compose up -d

# This will take 2-3 minutes on first run (building PHP image)
# Watch progress:
docker-compose logs -f
```

### 5. Install Dependencies

```bash
# Install Composer dependencies
docker-compose exec php composer install

# Generate application key
docker-compose exec php php artisan key:generate
```

### 6. Run Migrations

```bash
# Run all migrations (creates tables)
docker-compose exec php php artisan migrate

# Seed database with sample data
docker-compose exec php php artisan db:seed --class=PlansTableSeeder
```

### 7. Access the Application

**Main Application:**
- URL: http://qutecart.local
- Admin: (as configured in seeders)

**Development Services:**
- MinIO Console: http://localhost:9001 (minioadmin / minioadmin)
- Mailpit: http://localhost:8025 (email testing)
- PostgreSQL: localhost:5432 (qutecart / secret)

## Verify Setup

### Check All Services Running

```bash
docker-compose ps
```

You should see all services with status "Up":
- qutecart_postgres
- qutecart_redis
- qutecart_php
- qutecart_nginx
- qutecart_queue
- qutecart_scheduler
- qutecart_minio
- qutecart_mailpit

### Test Database Connection

```bash
docker-compose exec php php artisan migrate:status
```

Should show all migrations as "Ran".

### Test Web Server

```bash
curl http://qutecart.local
```

Should return HTML (not an error).

## Common Tasks

### Run Artisan Commands

```bash
# General format
docker-compose exec php php artisan [command]

# Examples:
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
docker-compose exec php php artisan tinker
docker-compose exec php php artisan queue:work
```

### Access PHP Container Shell

```bash
docker-compose exec php sh

# Now you're inside the container
php artisan list
composer --version
exit
```

### View Logs

```bash
# All containers
docker-compose logs -f

# Specific service
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f pgsql

# Laravel logs
docker-compose exec php tail -f storage/logs/laravel.log
```

### Database Operations

```bash
# Access PostgreSQL
docker-compose exec pgsql psql -U qutecart -d qutecart

# Common SQL commands:
\dt              # List tables
\d shops         # Describe shops table
SELECT * FROM shops LIMIT 5;
\q               # Quit

# Fresh migration (CAUTION: Deletes all data)
docker-compose exec php php artisan migrate:fresh --seed
```

### Clear Caches

```bash
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan route:clear
docker-compose exec php php artisan view:clear
```

### Restart Services

```bash
# Restart all
docker-compose restart

# Restart specific service
docker-compose restart nginx
docker-compose restart php
```

## Testing SaaS Features

### 1. Create Subscription Plans

Plans should already be seeded. Verify:

```bash
docker-compose exec php php artisan tinker
```

```php
\App\Models\Plan::all();
// Should show: Free, Starter, Growth, Enterprise
exit
```

### 2. Test Subdomain Routing

**Create a premium tenant:**

```bash
docker-compose exec php php artisan tinker
```

```php
$shop = \App\Models\Shop::first();
$tenant = \App\Models\Tenant::createForShop($shop, 'myshop', 'starter');
echo "Created tenant with subdomain: " . $tenant->subdomain_url;
exit
```

**Add to /etc/hosts:**
```
127.0.0.1    myshop.qutecart.local
```

**Visit:** http://myshop.qutecart.local

### 3. Test Shop Context

```bash
docker-compose exec php php artisan tinker
```

```php
// Test shop methods
$shop = \App\Models\Shop::first();
$shop->isFreeTier();
$shop->isPremium();
$shop->products_limit;
$shop->remaining_products;
exit
```

## Troubleshooting

### Services Won't Start

```bash
# Check what's using ports
lsof -i :80    # Nginx
lsof -i :5432  # PostgreSQL
lsof -i :6379  # Redis

# Stop conflicting services or change ports in docker-compose.yml
```

### Permission Errors

```bash
# Fix Laravel storage permissions
docker-compose exec php chmod -R 775 storage bootstrap/cache
docker-compose exec php chown -R www:www storage bootstrap/cache
```

### Database Connection Failed

```bash
# Verify PostgreSQL is running
docker-compose ps pgsql

# Check logs
docker-compose logs pgsql

# Test connection
docker-compose exec pgsql pg_isready -U qutecart
```

### "Class not found" Errors

```bash
# Regenerate autoload files
docker-compose exec php composer dump-autoload
```

### Docker Build Failing

```bash
# Clean rebuild
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Reset Everything

```bash
# Nuclear option: Delete all data and start fresh
docker-compose down -v
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan migrate:fresh --seed
```

## Development Workflow

### 1. Make Code Changes

Edit files in your local IDE. Changes are immediately reflected (volume mount).

### 2. If You Modify:

**PHP Files:**
- No restart needed (files are mounted)

**Config Files:**
```bash
docker-compose exec php php artisan config:cache
```

**Migrations:**
```bash
docker-compose exec php php artisan migrate
```

**Dependencies:**
```bash
docker-compose exec php composer install
```

**Docker Config:**
```bash
docker-compose restart nginx php
```

### 3. Running Tests

```bash
# Run all tests
docker-compose exec php php artisan test

# Run specific test
docker-compose exec php php artisan test --filter TestName

# With coverage
docker-compose exec php php artisan test --coverage
```

## Next Steps

After successful setup:

1. **Review the architecture:**
   - Read `docs/architecture/QUTECAT_HYBRID_ARCHITECTURE.md`
   - Read `docs/CODEBASE_ORGANIZATION.md`

2. **Explore the models:**
   - `app/Models/Tenant.php` - Subdomain tenants
   - `app/Models/Shop.php` - Enhanced vendor shops
   - `app/Models/Plan.php` - Subscription plans
   - `app/Models/Subscription.php` - Active subscriptions

3. **Test the API:**
   - Review existing endpoints in `routes/api.php`
   - Test context-aware filtering
   - Mobile app integration

4. **Configure Stripe:**
   - Create Stripe test account
   - Add keys to `.env`
   - Create products in Stripe dashboard
   - Update plan seeders with Stripe IDs

## Support

For issues:
1. Check logs: `docker-compose logs -f`
2. Review `docker/README.md` for Docker-specific help
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify environment: `.env` file settings

## Clean Shutdown

```bash
# Stop all containers
docker-compose down

# Stop and remove volumes (deletes all data)
docker-compose down -v
```

---

**You're ready to develop!** 🚀

Main application: http://qutecart.local
Documentation: `docs/` folder
