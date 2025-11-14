# QuteCart Docker Development Environment

This directory contains Docker configuration files for local development.

## Directory Structure

```
docker/
├── nginx/
│   └── default.conf         # Nginx virtual host configuration
├── php/
│   ├── Dockerfile           # PHP 8.2 FPM with PostgreSQL extensions
│   └── php.ini              # Custom PHP settings
├── postgres/
│   └── init.sql             # PostgreSQL initialization script
└── README.md                # This file
```

## Services

The `docker-compose.yml` in the parent directory defines these services:

### Core Services
- **pgsql** - PostgreSQL 16 database
- **redis** - Redis 7 for caching and queues
- **php** - PHP 8.2-FPM application container
- **nginx** - Nginx web server

### Additional Services
- **queue** - Laravel queue worker
- **scheduler** - Laravel task scheduler
- **minio** - S3-compatible local object storage
- **mailpit** - Email testing (SMTP server + web UI)

## Quick Start

### 1. Initial Setup

```bash
# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Generate application key
docker-compose exec php php artisan key:generate

# Run migrations
docker-compose exec php php artisan migrate --seed
```

### 2. Add Local Domains

Add these to your `/etc/hosts` file:

```
127.0.0.1    qutekart.local
127.0.0.1    shop.qutekart.local
127.0.0.1    premium-vendor.qutekart.local
```

### 3. Access Services

- **Web Application**: http://qutekart.local
- **MinIO Console**: http://localhost:9001 (minioadmin / minioadmin)
- **Mailpit UI**: http://localhost:8025
- **PostgreSQL**: localhost:5432 (qutekart / secret)
- **Redis**: localhost:6379

## Common Commands

### Container Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# Restart a service
docker-compose restart nginx
```

### Application Commands

```bash
# Run artisan commands
docker-compose exec php php artisan [command]

# Run composer
docker-compose exec php composer [command]

# Run tests
docker-compose exec php php artisan test

# Access PHP container shell
docker-compose exec php sh

# Access PostgreSQL shell
docker-compose exec pgsql psql -U qutekart -d qutekart
```

### Database Operations

```bash
# Run migrations
docker-compose exec php php artisan migrate

# Fresh database with seeders
docker-compose exec php php artisan migrate:fresh --seed

# Create migration
docker-compose exec php php artisan make:migration [name]

# Database backup
docker-compose exec pgsql pg_dump -U qutekart qutekart > backup.sql

# Restore database
docker-compose exec -T pgsql psql -U qutekart qutekart < backup.sql
```

### Queue and Scheduler

```bash
# View queue worker logs
docker-compose logs -f queue

# Restart queue worker
docker-compose restart queue

# View scheduler logs
docker-compose logs -f scheduler
```

### MinIO (Local S3)

```bash
# Create bucket
docker-compose exec minio mc mb local/qutekart

# List buckets
docker-compose exec minio mc ls local
```

## Configuration Details

### Nginx (nginx/default.conf)

- Supports main domain and wildcard subdomains
- Configured for Laravel's `public/` directory
- PHP-FPM integration
- Security headers enabled
- Static asset caching (30 days)
- 50MB file upload limit

### PHP (php/Dockerfile & php/php.ini)

**Extensions Installed:**
- pdo_pgsql, pgsql (PostgreSQL)
- redis (Redis client)
- gd (Image manipulation)
- zip (Archive handling)
- intl (Internationalization)
- bcmath (Precision math)
- soap (SOAP protocol)

**Custom Settings:**
- Memory limit: 512M
- Upload max filesize: 50M
- Max execution time: 300s
- OPcache enabled
- Timezone: UTC

### PostgreSQL (postgres/init.sql)

**Automatic Setup:**
- Creates required extensions (uuid-ossp, pg_trgm, unaccent)
- Creates MySQL compatibility functions (CURDATE(), CURTIME())
- Optimizes performance settings
- Sets timezone to UTC

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs [service-name]

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d
```

### Permission errors

```bash
# Fix storage permissions
docker-compose exec php chmod -R 775 storage bootstrap/cache
docker-compose exec php chown -R www:www storage bootstrap/cache
```

### Database connection failed

```bash
# Check PostgreSQL is healthy
docker-compose ps

# View PostgreSQL logs
docker-compose logs pgsql

# Test connection
docker-compose exec pgsql pg_isready -U qutekart
```

### Clear caches

```bash
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan route:clear
docker-compose exec php php artisan view:clear
```

## Development Workflow

### Making Changes

1. Edit code in your local IDE (files are mounted as volumes)
2. Changes are immediately reflected (no rebuild needed for PHP)
3. Restart services if you modify Docker configs:
   ```bash
   docker-compose restart nginx php
   ```

### Adding Composer Packages

```bash
docker-compose exec php composer require vendor/package
```

### Running Migrations

```bash
# Create migration
docker-compose exec php php artisan make:migration create_example_table

# Run migrations
docker-compose exec php php artisan migrate
```

### Testing

```bash
# Run all tests
docker-compose exec php php artisan test

# Run specific test
docker-compose exec php php artisan test --filter TestName

# Run with coverage
docker-compose exec php php artisan test --coverage
```

## Production Notes

⚠️ **This Docker setup is for LOCAL DEVELOPMENT ONLY**

For production deployment on Digital Ocean:
- Use managed PostgreSQL database
- Use DigitalOcean Spaces (not MinIO)
- Use managed Redis (or ElastiCache)
- Configure proper SSL certificates
- Disable debug mode
- Use production-grade SMTP service
- Set strong passwords

See `/docs/deployment/` for production deployment guides.

## Volumes

Persistent data is stored in Docker volumes:

- `pgsql_data` - PostgreSQL database files
- `redis_data` - Redis persistence
- `minio_data` - MinIO object storage
- `nginx_logs` - Nginx access and error logs

### Backup Volumes

```bash
# List volumes
docker volume ls

# Backup a volume
docker run --rm -v qutekart_pgsql_data:/data -v $(pwd):/backup alpine tar czf /backup/pgsql_backup.tar.gz /data
```

### Clean Up

```bash
# Remove all containers, networks, and volumes
docker-compose down -v

# Remove unused volumes
docker volume prune
```

## Support

For issues or questions:
- Check application logs: `docker-compose logs -f php`
- Check Laravel logs: `docker-compose exec php tail -f storage/logs/laravel.log`
- Review configuration in `docker-compose.yml`
