# Deploy QuteKart to DigitalOcean with Docker

**Estimated Time:** 30-45 minutes ⚡
**Cost:** ~$24/month (Droplet) + $5/month (Spaces)
**Difficulty:** Easy (just run Docker commands!)

---

## 🎯 Why Docker on DigitalOcean?

✅ **Same environment** as local development
✅ **One command** starts all services
✅ **No manual installation** of PHP, PostgreSQL, Redis, etc.
✅ **Easy to update** - just pull and restart
✅ **Portable** - works anywhere Docker runs
✅ **Less error-prone** - tested configuration

---

## 📋 What You'll Need

- DigitalOcean account
- Domain: qutekart.com (DNS access)
- Your QuteKart code on GitHub/GitLab
- 30-45 minutes

**Credentials to prepare:**
- Stripe production keys
- Pusher credentials
- Firebase server key
- Resend API key (for emails)

---

## 🚀 Part 1: Create DigitalOcean Droplet (5 min)

### Step 1: Create Droplet

1. **Login:** https://cloud.digitalocean.com/

2. **Create → Droplets**

3. **Configuration:**
   - **Image:** Ubuntu 22.04 LTS x64
   - **Plan:** Basic
   - **CPU:** Regular - $24/month (4GB RAM, 2 CPU, 80GB SSD) ✅
   - **Region:** Choose closest to your users
   - **Authentication:** SSH Key (recommended) or Password
   - **Hostname:** `qutekart-production`

4. **Create Droplet**

5. **Copy IP address** (e.g., 164.92.123.45)

---

## 🌐 Part 2: Configure DNS (5 min)

### In DigitalOcean Networking:

1. **Networking → Domains → Add Domain**
   - Domain: `qutekart.com`
   - Select droplet: `qutekart-production`

2. **Add these DNS records:**

| Type | Hostname | Value | TTL |
|------|----------|-------|-----|
| A | @ | [Droplet IP] | 3600 |
| A | www | [Droplet IP] | 3600 |
| A | * | [Droplet IP] | 3600 |

**The wildcard (*) is critical** for tenant subdomains!

3. **If domain is elsewhere:**
   - Update nameservers to:
     ```
     ns1.digitalocean.com
     ns2.digitalocean.com
     ns3.digitalocean.com
     ```

**Wait 5-30 minutes for DNS to propagate**

---

## 💻 Part 3: Setup Docker on Droplet (10 min)

### Step 1: SSH to Droplet

```bash
ssh root@YOUR_DROPLET_IP
```

### Step 2: Update System

```bash
apt update && apt upgrade -y
```

### Step 3: Install Docker

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Verify Docker
docker --version
# Should show: Docker version 24.x.x

# Start Docker
systemctl start docker
systemctl enable docker
```

### Step 4: Install Docker Compose

```bash
# Install Docker Compose
apt install -y docker-compose

# Verify
docker-compose --version
# Should show: docker-compose version 1.29.x or 2.x.x
```

### Step 5: Install Git & Basic Tools

```bash
apt install -y git curl wget nano
```

---

## 📦 Part 4: Deploy Application (15 min)

### Step 1: Clone Repository

```bash
# Create directory
mkdir -p /var/www
cd /var/www

# Clone your repo
git clone YOUR_REPOSITORY_URL qutekart

# Navigate to Laravel directory
cd qutekart/"Ready eCommerce-Admin with Customer Website/install"
```

### Step 2: Create Production Environment File

```bash
# Copy example
cp .env.example .env

# Edit for production
nano .env
```

**Update with these production values:**

```env
# === Application ===
APP_NAME="QuteKart"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://qutekart.com

# === Database (Docker PostgreSQL) ===
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=qutekart_prod
DB_USERNAME=qutekart_user
DB_PASSWORD=CHANGE_THIS_SECURE_PASSWORD_123!

# === Redis (Docker) ===
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# === Mail (Use Resend for production) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_YOUR_RESEND_API_KEY_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@qutekart.com"
MAIL_FROM_NAME="QuteKart"

# === Storage (DigitalOcean Spaces) ===
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_SPACES_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SPACES_SECRET_KEY
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=qutekart-prod
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://qutekart-prod.nyc3.cdn.digitaloceanspaces.com

# === Queue ===
QUEUE_CONNECTION=redis

# === Broadcast ===
BROADCAST_DRIVER=pusher

# === Pusher (Real-time chat) ===
PUSHER_APP_ID=YOUR_PUSHER_APP_ID
PUSHER_APP_KEY=YOUR_PUSHER_KEY
PUSHER_APP_SECRET=YOUR_PUSHER_SECRET
PUSHER_APP_CLUSTER=YOUR_CLUSTER

# === Business Model ===
BUSINESS_MODEL=multi

# === Stripe (PRODUCTION KEYS!) ===
STRIPE_KEY=pk_live_YOUR_PRODUCTION_KEY
STRIPE_SECRET=sk_live_YOUR_PRODUCTION_SECRET
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET

# === Firebase ===
FIREBASE_SERVER_KEY=YOUR_FIREBASE_SERVER_KEY

# === Tenancy ===
CENTRAL_DOMAINS=qutekart.com,www.qutekart.com
```

**Save:** Ctrl+X, Y, Enter

### Step 3: Update docker-compose.yml for Production

```bash
nano docker-compose.yml
```

**Update the database credentials section:**

```yaml
services:
  pgsql:
    image: postgres:16
    container_name: qutekart_postgres
    environment:
      POSTGRES_DB: qutekart_prod
      POSTGRES_USER: qutekart_user
      POSTGRES_PASSWORD: CHANGE_THIS_SECURE_PASSWORD_123!  # Same as in .env
    # ... rest stays the same
```

**Save:** Ctrl+X, Y, Enter

### Step 4: Start Docker Containers

```bash
# Start all services in background
docker-compose up -d

# This will start:
# - nginx (web server)
# - php (Laravel application)
# - pgsql (PostgreSQL database)
# - redis (cache & queue)
# - queue (background worker)
# - scheduler (cron jobs)
# - minio (temporary - we'll use Spaces in production)
# - mailpit (we'll skip this in production)

# Wait 1-2 minutes for services to start

# Check if all containers are running
docker-compose ps

# Should show all services as "Up"
```

### Step 5: Install Dependencies & Setup Database

```bash
# Install Composer dependencies
docker-compose exec php composer install --optimize-autoloader --no-dev

# Generate application key
docker-compose exec php php artisan key:generate

# Run migrations
docker-compose exec php php artisan migrate --force

# Seed database
docker-compose exec php php artisan db:seed --force

# Seed subscription plans
docker-compose exec php php artisan db:seed --class=PlansTableSeeder --force

# Apply ZARA branding
docker-compose exec php php artisan db:seed --class=ZaraThemeSeeder --force

# Create storage link
docker-compose exec php php artisan storage:link

# Cache configuration
docker-compose exec php php artisan config:cache
docker-compose exec php php artisan route:cache
docker-compose exec php php artisan view:cache

# Create installed flag
docker-compose exec php sh -c "echo 'Installed on \$(date)' > storage/installed"
```

### Step 6: Create Admin User

```bash
docker-compose exec php php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@qutekart.com',
    'phone' => '1234567890',
    'password' => bcrypt('YOUR_SECURE_PASSWORD'),
    'is_active' => true,
])->assignRole('root');

exit
```

---

## 🔒 Part 5: Setup SSL with Nginx Proxy (15 min)

We'll use **nginx-proxy** and **Let's Encrypt** companion for automatic SSL.

### Option A: Using nginx-proxy (Recommended - Automatic SSL)

**Create a new docker-compose file for proxy:**

```bash
cd /var/www/qutekart
nano docker-compose.proxy.yml
```

**Paste this:**

```yaml
version: '3.8'

services:
  nginx-proxy:
    image: nginxproxy/nginx-proxy
    container_name: nginx-proxy
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/tmp/docker.sock:ro
      - ./certs:/etc/nginx/certs
      - ./vhost:/etc/nginx/vhost.d
      - ./html:/usr/share/nginx/html
    networks:
      - qutekart
    restart: always

  letsencrypt:
    image: nginxproxy/acme-companion
    container_name: nginx-proxy-acme
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./certs:/etc/nginx/certs
      - ./vhost:/etc/nginx/vhost.d
      - ./html:/usr/share/nginx/html
      - ./acme:/etc/acme.sh
    environment:
      - DEFAULT_EMAIL=admin@qutekart.com
    depends_on:
      - nginx-proxy
    networks:
      - qutekart
    restart: always

networks:
  qutekart:
    external: true
```

**Start the proxy:**

```bash
docker-compose -f docker-compose.proxy.yml up -d
```

**Update your main docker-compose.yml nginx service:**

```bash
cd "Ready eCommerce-Admin with Customer Website/install"
nano docker-compose.yml
```

**Update the nginx service:**

```yaml
  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    container_name: qutekart_nginx
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php
    networks:
      - qutekart
    environment:
      - VIRTUAL_HOST=qutekart.com,www.qutekart.com,*.qutekart.com
      - LETSENCRYPT_HOST=qutekart.com,www.qutekart.com
      - LETSENCRYPT_EMAIL=admin@qutekart.com
    expose:
      - "80"
    # Remove the ports section - nginx-proxy will handle it
```

**Restart containers:**

```bash
docker-compose down
docker-compose up -d
```

**SSL certificates will be automatically generated!** ✅

---

### Option B: Manual SSL with Certbot (Simpler but less automated)

```bash
# Install Certbot
apt install -y certbot

# Stop nginx temporarily
docker-compose stop nginx

# Get SSL certificate
certbot certonly --standalone -d qutekart.com -d www.qutekart.com

# For wildcard (subdomains)
certbot certonly --manual --preferred-challenges dns -d "*.qutekart.com"
# Follow DNS TXT record instructions

# Certificates will be in: /etc/letsencrypt/live/qutekart.com/

# Update docker-compose.yml to mount certificates
nano docker-compose.yml
```

Add to nginx service:

```yaml
  nginx:
    # ... existing config
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - /etc/letsencrypt:/etc/letsencrypt:ro  # Add this line
    ports:
      - "443:443"  # Add HTTPS port
      - "80:80"
```

**Update nginx config for SSL:**

```bash
nano docker/nginx/default.conf
```

Add SSL server block (see full config in guide).

**Restart:**

```bash
docker-compose up -d
```

---

## 🎉 Part 6: Verify Deployment

### Test Your Site

```bash
# Check if all containers are running
docker-compose ps

# Should show all services as "Up"

# Check logs
docker-compose logs -f php
docker-compose logs -f nginx

# Test HTTP/HTTPS
curl -I https://qutekart.com

# Should return: HTTP/2 200
```

**Open in browser:**
- https://qutekart.com
- https://qutekart.com/admin

**Login with your admin credentials!**

---

## 📦 Part 7: Setup DigitalOcean Spaces (10 min)

Since we're using Docker, we can't rely on MinIO for production. Use Spaces instead.

### Create Spaces

1. **DigitalOcean → Spaces → Create**
   - Region: Same as droplet (e.g., NYC3)
   - Name: `qutekart-prod`
   - Enable CDN: Yes
   - File Listing: Public

2. **API → Spaces Keys → Generate**
   - Name: QuteKart Production
   - Copy Access Key & Secret

3. **Update .env:**

```bash
docker-compose exec php nano .env
```

Update:
```env
AWS_ACCESS_KEY_ID=your_spaces_key
AWS_SECRET_ACCESS_KEY=your_spaces_secret
AWS_URL=https://qutekart-prod.nyc3.cdn.digitaloceanspaces.com
```

4. **Reload config:**

```bash
docker-compose exec php php artisan config:cache
```

---

## 🔄 Part 8: Configure Auto-Updates (Optional)

### Watchtower - Auto-update Docker images

```bash
docker run -d \
  --name watchtower \
  -v /var/run/docker.sock:/var/run/docker.sock \
  containrrr/watchtower \
  --interval 86400
```

This will check for updated images daily and auto-update your containers.

---

## 🛠️ Daily Operations

### Deploying Updates

```bash
cd /var/www/qutekart/"Ready eCommerce-Admin with Customer Website/install"

# Pull latest code
git pull

# Update containers
docker-compose pull
docker-compose up -d --build

# Run migrations (if any)
docker-compose exec php php artisan migrate --force

# Clear cache
docker-compose exec php php artisan config:cache
docker-compose exec php php artisan route:cache
docker-compose exec php php artisan view:cache

# Restart queue workers
docker-compose restart queue
```

### View Logs

```bash
# All logs
docker-compose logs -f

# Specific service
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f pgsql
docker-compose logs -f queue

# Laravel logs
docker-compose exec php tail -f storage/logs/laravel.log
```

### Restart Services

```bash
# Restart all
docker-compose restart

# Restart specific service
docker-compose restart php
docker-compose restart nginx
docker-compose restart queue
```

### Access Database

```bash
# PostgreSQL shell
docker-compose exec pgsql psql -U qutekart_user -d qutekart_prod

# SQL commands
\dt              # List tables
\d shops         # Describe table
SELECT * FROM plans;
\q               # Quit
```

### Backup Database

```bash
# Create backup
docker-compose exec pgsql pg_dump -U qutekart_user qutekart_prod > backup-$(date +%Y%m%d).sql

# Restore backup
docker-compose exec -T pgsql psql -U qutekart_user -d qutekart_prod < backup-20251114.sql
```

---

## 🔍 Troubleshooting

### Containers won't start?

```bash
# Check what's wrong
docker-compose ps
docker-compose logs

# Rebuild from scratch
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Database connection failed?

```bash
# Check if PostgreSQL is running
docker-compose ps pgsql

# Check database logs
docker-compose logs pgsql

# Test connection
docker-compose exec pgsql psql -U qutekart_user -d qutekart_prod
```

### Permission errors?

```bash
# Fix permissions inside container
docker-compose exec php chown -R www-data:www-data storage bootstrap/cache
docker-compose exec php chmod -R 775 storage bootstrap/cache
```

### Queue not processing?

```bash
# Check queue worker
docker-compose logs queue

# Restart queue worker
docker-compose restart queue

# Check if jobs are in queue
docker-compose exec php php artisan queue:work --once
```

### SSL not working?

**If using nginx-proxy:**
```bash
# Check proxy logs
docker logs nginx-proxy
docker logs nginx-proxy-acme

# Restart proxy
docker-compose -f docker-compose.proxy.yml restart
```

**If using certbot:**
```bash
# Renew certificates
certbot renew

# Test renewal
certbot renew --dry-run
```

---

## 📊 Resource Monitoring

```bash
# Check Docker resource usage
docker stats

# Check disk space
df -h

# Check memory
free -h

# Clean up old images
docker system prune -a
```

---

## ✅ Deployment Checklist

- [ ] Droplet created (4GB RAM minimum)
- [ ] DNS configured (A records for @, www, *)
- [ ] Docker installed
- [ ] Docker Compose installed
- [ ] Repository cloned
- [ ] .env configured for production
- [ ] docker-compose.yml updated with secure passwords
- [ ] All containers running (`docker-compose ps`)
- [ ] Database migrated and seeded
- [ ] Admin user created
- [ ] SSL certificate configured
- [ ] HTTPS working
- [ ] DigitalOcean Spaces configured
- [ ] Stripe webhooks configured
- [ ] Can access https://qutekart.com
- [ ] Can login to admin panel
- [ ] Queue workers processing jobs
- [ ] Scheduler running (cron jobs)

---

## 💰 Monthly Cost

| Service | Cost |
|---------|------|
| Droplet (4GB) | $24/month |
| Spaces (250GB + CDN) | $5/month |
| Domain | ~$12/year |
| **TOTAL** | **~$30/month** |

Plus free tiers:
- Resend (3,000 emails/month)
- Pusher (100 connections)
- Firebase (10M notifications)

---

## 🎯 Advantages of Docker Deployment

✅ **Same as local** - identical environment
✅ **Easy updates** - `git pull && docker-compose up -d`
✅ **Isolated services** - each in own container
✅ **Easy rollback** - `docker-compose down && git checkout previous`
✅ **Portable** - move to any server easily
✅ **Resource efficient** - shared kernel
✅ **Quick disaster recovery** - rebuild in minutes

---

## 🚀 You're Live!

Your QuteKart SaaS platform is now running on DigitalOcean with Docker!

**Next Steps:**
1. Configure Stripe webhooks: https://dashboard.stripe.com/webhooks
2. Test subscription flow
3. Create test shop with subdomain
4. Configure Flutter mobile app
5. Test all features

**Support Commands:**
```bash
# View all services
docker-compose ps

# View logs
docker-compose logs -f

# Restart everything
docker-compose restart

# Update deployment
git pull && docker-compose up -d
```

---

**Deployed with:** Docker + Docker Compose
**Server:** DigitalOcean Droplet
**Domain:** qutekart.com
**Status:** 🚀 Live & Ready!
