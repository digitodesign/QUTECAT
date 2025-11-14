# QuteCart Docker Architecture

**Complete Container-Based Development & Production Environment**

**Date:** November 6, 2025
**Status:** Production Ready ✅
**Question:** "Are the old implementations in Docker too?"

---

## 🎯 Answer: YES - Everything Runs in Docker

**Short Answer:** YES, both the "old" (original eCommerce template) AND the "new" (SaaS features) run in the SAME Docker environment. They are fully integrated into one unified system.

**There is NO separation** - the Docker setup includes:
- ✅ Original eCommerce features (products, orders, cart, customers)
- ✅ New SaaS features (subscriptions, webhooks, emails, subdomains)
- ✅ Admin dashboard (original + enhanced with subscription info)
- ✅ Mobile/Web APIs (original + context-aware enhancements)
- ✅ All services (database, cache, queue, scheduler, storage)

---

## 🏗️ Docker Architecture Overview

### **8-Container Microservices Architecture**

```
┌──────────────────────────────────────────────────────────┐
│              QUTECAT DOCKER ENVIRONMENT                   │
│                                                           │
│  ┌─────────────────────────────────────────────────────┐ │
│  │  Nginx (Port 80)                                    │ │
│  │  Web Server & Reverse Proxy                         │ │
│  └──────────────┬──────────────────────────────────────┘ │
│                 │                                         │
│  ┌──────────────▼──────────────────────────────────────┐ │
│  │  PHP-FPM                                            │ │
│  │  Laravel Application (Original + SaaS)              │ │
│  │  - Controllers (Admin, API, Subscription)           │ │
│  │  - Models (Shop, Product, Subscription, Plan)       │ │
│  │  - Services (Stripe, Usage Tracking)                │ │
│  │  - Middleware (Context, Limits)                     │ │
│  └──┬──────────┬────────────┬───────────────────────────┘ │
│     │          │            │                             │
│  ┌──▼──────┐ ┌─▼────────┐ ┌▼────────┐ ┌───────────────┐ │
│  │ Postgres│ │  Redis   │ │  MinIO  │ │    Mailpit    │ │
│  │ (Data)  │ │ (Cache)  │ │ (Files) │ │    (Email)    │ │
│  └─────────┘ └──┬───────┘ └─────────┘ └───────────────┘ │
│                 │                                         │
│  ┌──────────────▼──────────────┐  ┌──────────────────┐  │
│  │  Queue Worker               │  │   Scheduler      │  │
│  │  (Async Jobs, Emails)       │  │   (Cron Tasks)   │  │
│  └─────────────────────────────┘  └──────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

---

## 📦 Container Details

### 1. **Nginx** (Web Server)
**Container:** `qutekart_nginx`
**Image:** `nginx:alpine`
**Port:** 80 (configurable via APP_PORT)

**Purpose:**
- Serves HTTP requests
- Reverse proxy to PHP-FPM
- Serves static files (CSS, JS, images)
- Handles SSL/TLS (in production with certbot)

**Handles:**
- ✅ Original admin dashboard requests
- ✅ Original web marketplace requests
- ✅ New premium subdomain requests
- ✅ API requests (original + subscription APIs)

**Config:** `docker/nginx/default.conf`

---

### 2. **PHP-FPM** (Application Server)
**Container:** `qutekart_php`
**Image:** Custom (built from `docker/php/Dockerfile`)
**PHP Version:** 8.2+

**Purpose:**
- Executes Laravel application code
- Processes all HTTP requests
- Runs original AND new SaaS features

**Runs:**
- ✅ **Original Features:**
  - Admin controllers (Shop, Product, Order, Customer, etc.)
  - API controllers (Product, Cart, Order, Auth, etc.)
  - Web routes (marketplace browsing)
  - Payment processing (original payment gateways)

- ✅ **New SaaS Features:**
  - SubscriptionController (10 new API endpoints)
  - WebhookController (Stripe events)
  - StripeSubscriptionService
  - UsageTrackingService
  - CheckShopLimits middleware
  - ContextAware trait
  - Email notifications (Mailables + Listeners)

**Dependencies:**
```dockerfile
# From docker/php/Dockerfile
- PHP 8.2+
- Composer
- Laravel dependencies
- PHP extensions (pdo_pgsql, redis, gd, zip, etc.)
- Stripe PHP SDK
- Tenancy package
```

---

### 3. **PostgreSQL** (Database)
**Container:** `qutekart_postgres`
**Image:** `postgres:16-alpine`
**Port:** 5432

**Purpose:**
- Single database for ALL data (original + SaaS)
- Persistent storage for shops, products, orders, subscriptions

**Schema:**
```sql
-- Original Tables (from template):
shops, products, orders, categories, customers, users, etc.

-- New SaaS Tables (Phase 1):
plans, subscriptions, tenants, domains

-- Enhanced Tables (Phase 1):
shops (added subscription columns)
```

**Volume:** `pgsql_data` (persistent across container restarts)

**Key Point:**
- ✅ One database for EVERYTHING
- ✅ Original tables untouched (only extended)
- ✅ New tables added for SaaS features
- ✅ All data lives together (shops + subscriptions)

---

### 4. **Redis** (Cache & Queue)
**Container:** `qutekart_redis`
**Image:** `redis:7-alpine`
**Port:** 6379

**Purpose:**
- Session storage
- Application caching
- Queue backend for async jobs

**Handles:**
- ✅ Original session data
- ✅ Cache for product queries
- ✅ Queue jobs for emails (NEW)
- ✅ Queue jobs for webhooks (NEW)

**Volume:** `redis_data`

---

### 5. **MinIO** (Object Storage)
**Container:** `qutekart_minio`
**Image:** `minio/minio:latest`
**Ports:** 9000 (API), 9001 (Console)

**Purpose:**
- S3-compatible local file storage
- Stores uploaded media files

**Stores:**
- ✅ Original product images
- ✅ Original shop logos/banners
- ✅ User avatars
- ✅ Order attachments
- ✅ Any file uploads

**Volume:** `minio_data`

**Admin Access:** http://localhost:9001
- Username: minioadmin (default)
- Password: minioadmin (default)

---

### 6. **Queue Worker** (Background Jobs)
**Container:** `qutekart_queue`
**Image:** Same as PHP-FPM
**Command:** `php artisan queue:work --sleep=3 --tries=3`

**Purpose:**
- Processes background jobs asynchronously
- Prevents blocking HTTP requests

**Processes:**
- ✅ Original email sending (order confirmations, OTP)
- ✅ **NEW:** Subscription confirmation emails
- ✅ **NEW:** Payment failed notifications
- ✅ **NEW:** Trial ending reminders
- ✅ **NEW:** Usage limit warnings
- ✅ Image processing (if configured)
- ✅ Report generation

**Key Point:**
- Runs continuously
- Picks up jobs from Redis queue
- Essential for email delivery (old + new)

---

### 7. **Scheduler** (Cron Jobs)
**Container:** `qutekart_scheduler`
**Image:** Same as PHP-FPM
**Command:** Runs `php artisan schedule:run` every 60 seconds

**Purpose:**
- Executes scheduled tasks (Laravel cron)

**Scheduled Tasks:**
- ✅ **NEW:** Monthly usage reset (first day of month)
- ✅ **NEW:** Usage calculation (daily)
- ✅ **NEW:** Subscription sync checks
- ✅ Original cleanup tasks (temp files, sessions)
- ✅ Database backups (if configured)
- ✅ Cache clearing

---

### 8. **Mailpit** (Email Testing)
**Container:** `qutekart_mailpit`
**Image:** `axllent/mailpit:latest`
**Ports:** 1025 (SMTP), 8025 (Web UI)

**Purpose:**
- Development email inbox
- Catches ALL emails sent by application
- Prevents sending real emails in development

**Captures:**
- ✅ Original order confirmation emails
- ✅ Original OTP emails
- ✅ **NEW:** Subscription confirmation emails
- ✅ **NEW:** Payment failed alerts
- ✅ **NEW:** Trial ending reminders
- ✅ **NEW:** Usage limit warnings

**Access:** http://localhost:8025
- View all sent emails
- Test email templates
- Debug email delivery

**Production:** Replace with Resend (configured in `.env`)

---

## 🔄 Data Flow: Original vs New Features

### **Original eCommerce Flow (Still Works Identically):**

```
1. Customer browses marketplace
   ↓ Nginx → PHP → PostgreSQL
2. Views product details
   ↓ ProductController (enhanced with ContextAware)
3. Adds to cart
   ↓ CartController (unchanged)
4. Places order
   ↓ OrderController (unchanged)
5. Receives order email
   ↓ Queue Worker → Mailpit/Resend
```

**Status:** ✅ Works exactly as before, no changes

---

### **New SaaS Flow (Integrated):**

```
1. Vendor subscribes to paid plan
   ↓ SubscriptionController → StripeSubscriptionService
2. Stripe processes payment
   ↓ Creates subscription
3. Laravel creates local subscription record
   ↓ PostgreSQL (subscriptions table)
4. Subdomain auto-created
   ↓ PostgreSQL (tenants, domains tables)
5. Welcome email queued
   ↓ Queue Worker (background)
6. Email sent via Resend
   ↓ Async, non-blocking

Continuous:
7. Vendor creates products
   ↓ CheckShopLimits middleware checks limit
8. Stripe sends webhook when payment renews
   ↓ WebhookController → StripeSubscriptionService
9. Subscription status synced
   ↓ PostgreSQL updated
```

**Status:** ✅ Fully integrated, uses same containers

---

## 🚀 How to Start the Environment

### **Development (Local):**

```bash
cd "Ready eCommerce-Admin with Customer Website/install"

# Start all containers
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f php
docker-compose logs -f queue
docker-compose logs -f scheduler

# Stop all containers
docker-compose down
```

**Access:**
- **Web App:** http://localhost (or APP_PORT)
- **MinIO Console:** http://localhost:9001
- **Mailpit UI:** http://localhost:8025
- **Database:** localhost:5432 (via pgAdmin or DBeaver)

---

### **Production Deployment:**

```bash
# 1. Clone repository
git clone https://github.com/digitodesign/QUTECAT.git
cd QUTECAT/"Ready eCommerce-Admin with Customer Website/install"

# 2. Configure environment
cp .env.example .env
nano .env  # Edit configuration

# 3. Build and start containers
docker-compose -f docker-compose.prod.yml up -d

# 4. Run migrations
docker-compose exec php php artisan migrate --force

# 5. Seed plans
docker-compose exec php php artisan db:seed --class=PlanSeeder --force

# 6. Optimize
docker-compose exec php php artisan config:cache
docker-compose exec php php artisan route:cache
docker-compose exec php php artisan view:cache
```

---

## 📊 Container Resource Usage

### **Typical Resource Allocation:**

| Container | CPU | Memory | Storage |
|-----------|-----|--------|---------|
| Nginx | 0.5% | 10MB | Minimal |
| PHP-FPM | 5-10% | 256MB | Minimal |
| PostgreSQL | 2-5% | 512MB | 5GB+ (data) |
| Redis | 1% | 128MB | 100MB |
| MinIO | 1% | 128MB | 10GB+ (files) |
| Queue Worker | 2-5% | 128MB | Minimal |
| Scheduler | <1% | 64MB | Minimal |
| Mailpit | <1% | 32MB | Minimal |

**Total (Development):** ~1GB RAM, 15GB+ storage

---

## 🔧 Environment Variables

### **Docker-Specific:**

```env
# Ports
APP_PORT=80
DB_PORT=5432
REDIS_PORT=6379
MINIO_PORT=9000
MINIO_CONSOLE_PORT=9001
MAILPIT_SMTP_PORT=1025
MAILPIT_UI_PORT=8025

# Database
DB_CONNECTION=pgsql
DB_HOST=pgsql  # ← Docker service name
DB_PORT=5432
DB_DATABASE=qutekart
DB_USERNAME=qutekart
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis  # ← Docker service name
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# MinIO (S3)
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=qutekart
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Mail (Development)
MAIL_MAILER=smtp
MAIL_HOST=mailpit  # ← Docker service name
MAIL_PORT=1025

# Mail (Production - Resend)
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxx
```

---

## ⚙️ Customization

### **Scaling Queue Workers:**

```yaml
# docker-compose.yml
queue:
  # ...
  deploy:
    replicas: 3  # Run 3 queue workers
```

### **Custom PHP Configuration:**

Edit `docker/php/php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```

### **Nginx Tuning:**

Edit `docker/nginx/default.conf`:
```nginx
client_max_body_size 100M;
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
```

---

## 🐛 Debugging

### **View Container Logs:**

```bash
# All containers
docker-compose logs -f

# Specific container
docker-compose logs -f php
docker-compose logs -f queue
docker-compose logs -f nginx

# Last 100 lines
docker-compose logs --tail=100 php
```

### **Execute Commands in Container:**

```bash
# Laravel commands
docker-compose exec php php artisan migrate
docker-compose exec php php artisan queue:work
docker-compose exec php php artisan tinker

# Shell access
docker-compose exec php sh
docker-compose exec pgsql psql -U qutekart qutekart
```

### **Restart Specific Container:**

```bash
docker-compose restart php
docker-compose restart queue
docker-compose restart nginx
```

---

## 🔐 Security (Production)

### **1. Change Default Passwords:**

```env
DB_PASSWORD=strong_random_password_here
MINIO_ROOT_USER=custom_admin
MINIO_ROOT_PASSWORD=strong_password_here
```

### **2. Limit Exposed Ports:**

```yaml
# Only expose Nginx (remove other port mappings)
nginx:
  ports:
    - "80:80"
    - "443:443"

# Don't expose database to host
pgsql:
  # ports:  # ← Comment out
  #   - "5432:5432"
```

### **3. Use Docker Secrets (Production):**

```yaml
secrets:
  db_password:
    file: ./secrets/db_password.txt
  stripe_secret:
    file: ./secrets/stripe_secret.txt
```

### **4. Enable SSL/TLS:**

```bash
# Install certbot
docker run -it --rm \
  -v /etc/letsencrypt:/etc/letsencrypt \
  certbot/certbot certonly --standalone \
  -d qutekart.com -d *.qutekart.com
```

---

## 📋 Container Health Checks

All critical containers have health checks:

```yaml
# PostgreSQL
healthcheck:
  test: ["CMD-SHELL", "pg_isready -U qutekart"]
  interval: 10s
  timeout: 5s
  retries: 5

# Redis
healthcheck:
  test: ["CMD", "redis-cli", "ping"]
  interval: 10s

# Nginx
healthcheck:
  test: ["CMD", "wget", "--spider", "http://localhost/"]
  interval: 30s
```

**Check Health:**
```bash
docker-compose ps
# Shows "healthy" status for each container
```

---

## 🎯 Key Takeaways

### **1. Everything is Integrated**
- ✅ Original eCommerce features run in Docker
- ✅ New SaaS features run in the SAME Docker
- ✅ Single unified environment
- ✅ No separation between "old" and "new"

### **2. Same Database, Same Codebase**
- ✅ PostgreSQL holds ALL data (original + SaaS)
- ✅ PHP container runs ALL code (original + SaaS)
- ✅ Queue worker processes ALL jobs (original + SaaS)

### **3. Backward Compatible**
- ✅ Existing admin routes work
- ✅ Existing API endpoints work
- ✅ Existing mobile app works
- ✅ New features are additive, not destructive

### **4. Production Ready**
- ✅ Health checks configured
- ✅ Persistent volumes for data
- ✅ Scalable architecture
- ✅ Easy to deploy

---

## 🚀 Next Steps

### **Development:**
1. Start Docker: `docker-compose up -d`
2. Run migrations: `docker-compose exec php php artisan migrate`
3. Seed data: `docker-compose exec php php artisan db:seed`
4. Access app: http://localhost
5. Check emails: http://localhost:8025

### **Production:**
1. Configure `.env` with production values
2. Set up SSL certificates
3. Deploy with `docker-compose up -d`
4. Run migrations: `docker-compose exec php php artisan migrate --force`
5. Cache config: `docker-compose exec php php artisan config:cache`

---

## ❓ FAQ

**Q: Are old and new features in different Docker containers?**
**A:** NO. Everything runs in the same containers. The PHP container executes both original and new code.

**Q: Do I need to run two Docker environments?**
**A:** NO. One `docker-compose up` starts everything (original + SaaS).

**Q: Can I use the original features without SaaS?**
**A:** YES. SaaS is opt-in. Shops default to free plan. Everything original still works.

**Q: How do I switch between development and production?**
**A:** Change `.env` file. Same Docker setup works for both.

**Q: How do I scale for more traffic?**
**A:** Add more queue worker replicas, use load balancer for Nginx, increase PHP worker count.

---

**Docker Architecture Complete!** 🐳

**The answer is clear:** Both original and new features run together in ONE unified Docker environment.

---

**Documentation Date:** November 6, 2025
**Docker Compose Version:** 3.8
**Total Containers:** 8
**Status:** Production Ready ✅
