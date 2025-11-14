# Deploy QuteKart to DigitalOcean - Step by Step

**Estimated Time:** 1-2 hours
**Cost:** ~$12-24/month (Droplet) + $5/month (Spaces for storage)
**Domain:** qutekart.com

---

## 📋 What You'll Need

Before starting, create accounts and get credentials:

### 1. DigitalOcean Account
- Sign up at: https://www.digitalocean.com/
- Add payment method
- Recommended: Use referral code for $200 credit (60 days)

### 2. Domain Setup
- You should already own **qutekart.com**
- You'll update DNS to point to DigitalOcean

### 3. Required Credentials (Get These Ready)

**Stripe** (for payments):
- Production API Keys: https://dashboard.stripe.com/apikeys
- Webhook Secret (we'll set up later)

**Pusher** (for real-time chat):
- Free account: https://pusher.com/
- Get App ID, Key, Secret, Cluster

**Firebase** (for push notifications):
- Create project: https://console.firebase.google.com/
- Get Server Key

**GitHub** (for code):
- Make sure your code is pushed to a Git repository

---

## 🚀 Part 1: Create DigitalOcean Droplet (5 minutes)

### Step 1: Create Droplet

1. **Login to DigitalOcean:** https://cloud.digitalocean.com/

2. **Click "Create" → "Droplets"**

3. **Choose Configuration:**

   **Region:** Choose closest to your users
   - New York (US)
   - San Francisco (US)
   - London (UK)
   - Singapore (Asia)
   - Frankfurt (Europe)

   **Image:** Ubuntu 22.04 LTS x64

   **Droplet Size:**
   - **For Testing:** Basic - $12/month (2GB RAM, 1 CPU, 50GB SSD)
   - **For Production:** Basic - $24/month (4GB RAM, 2 CPU, 80GB SSD) ✅ Recommended

   **Authentication:**
   - ✅ **SSH Key** (Recommended) - Add your SSH key
   - Or **Password** (simpler but less secure)

   **Hostname:** `qutekart-production`

4. **Click "Create Droplet"**

5. **Wait 1-2 minutes** for droplet to be created

6. **Copy the Droplet IP Address** (e.g., `164.92.123.45`)

---

## 🌐 Part 2: Configure DNS (10 minutes)

### Step 1: Add Domain to DigitalOcean

1. **In DigitalOcean dashboard:** Go to **Networking** → **Domains**

2. **Click "Add Domain"**
   - Enter: `qutekart.com`
   - Select your droplet: `qutekart-production`
   - Click "Add Domain"

### Step 2: Create DNS Records

**DigitalOcean will create some records automatically. Add these:**

| Type | Hostname | Value | TTL |
|------|----------|-------|-----|
| A | @ | [Your Droplet IP] | 3600 |
| A | www | [Your Droplet IP] | 3600 |
| A | * | [Your Droplet IP] | 3600 |
| CNAME | admin | @ | 3600 |

**The wildcard (*) A record is CRITICAL** for subdomain shops like `shop1.qutekart.com`

### Step 3: Update Your Domain Registrar

**If your domain is NOT at DigitalOcean:**

1. Go to your domain registrar (Namecheap, GoDaddy, etc.)
2. Find **DNS Settings** or **Nameservers**
3. Change nameservers to DigitalOcean:
   ```
   ns1.digitalocean.com
   ns2.digitalocean.com
   ns3.digitalocean.com
   ```
4. Save and wait 1-24 hours for propagation (usually 1-4 hours)

**Verify DNS:**
```bash
# Check if DNS is propagating
nslookup qutekart.com
# Should show your droplet IP
```

---

## 💻 Part 3: Connect to Server & Install Dependencies (20 minutes)

### Step 1: SSH to Your Droplet

```bash
# Replace with your droplet IP
ssh root@YOUR_DROPLET_IP

# If using SSH key, it should connect immediately
# If using password, enter the password emailed to you
```

### Step 2: Update System

```bash
# Update package list
apt update && apt upgrade -y

# Install basic tools
apt install -y curl wget git unzip software-properties-common
```

### Step 3: Install PHP 8.2 and Extensions

```bash
# Add PHP repository
add-apt-repository -y ppa:ondrej/php

# Update package list
apt update

# Install PHP 8.2 and required extensions
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-pgsql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath \
    php8.2-intl php8.2-redis php8.2-soap php8.2-gmp php8.2-imagick

# Verify PHP version
php -v
# Should show PHP 8.2.x
```

### Step 4: Install Composer

```bash
# Download Composer installer
curl -sS https://getcomposer.org/installer | php

# Move to global location
mv composer.phar /usr/local/bin/composer

# Verify
composer --version
```

### Step 5: Install PostgreSQL 16

```bash
# Add PostgreSQL repository
sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -

# Update and install
apt update
apt install -y postgresql-16 postgresql-contrib-16

# Start PostgreSQL
systemctl start postgresql
systemctl enable postgresql

# Verify
systemctl status postgresql
```

### Step 6: Install Redis

```bash
apt install -y redis-server

# Start Redis
systemctl start redis-server
systemctl enable redis-server

# Verify
redis-cli ping
# Should return: PONG
```

### Step 7: Install Nginx

```bash
apt install -y nginx

# Start Nginx
systemctl start nginx
systemctl enable nginx

# Verify
systemctl status nginx
```

### Step 8: Install Supervisor (for Queue Workers)

```bash
apt install -y supervisor

systemctl start supervisor
systemctl enable supervisor
```

### Step 9: Install Certbot (for SSL)

```bash
apt install -y certbot python3-certbot-nginx
```

**All dependencies installed! ✅**

---

## 🗄️ Part 4: Setup Database (5 minutes)

```bash
# Switch to postgres user
sudo -u postgres psql

# Run these SQL commands:
CREATE DATABASE qutekart_prod;
CREATE USER qutekart_user WITH ENCRYPTED PASSWORD 'YOUR_SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON DATABASE qutekart_prod TO qutekart_user;
ALTER DATABASE qutekart_prod OWNER TO qutekart_user;

# Grant schema permissions
\c qutekart_prod
GRANT ALL ON SCHEMA public TO qutekart_user;

# Exit
\q
```

**Test connection:**
```bash
psql -U qutekart_user -d qutekart_prod -h localhost -W
# Enter password when prompted
# Should connect successfully
# Type \q to exit
```

---

## 📦 Part 5: Deploy Laravel Application (15 minutes)

### Step 1: Create Web Directory

```bash
# Create directory
mkdir -p /var/www/qutekart
cd /var/www/qutekart

# Set ownership
chown -R www-data:www-data /var/www/qutekart
```

### Step 2: Clone Your Repository

**Option A: Using Git (Recommended)**

```bash
# If your repo is private, set up GitHub SSH key first
# Or use HTTPS with personal access token

git clone YOUR_REPOSITORY_URL .

# Navigate to Laravel directory
cd "Ready eCommerce-Admin with Customer Website/install"
```

**Option B: Upload via SFTP**

Use FileZilla or similar:
- Host: `sftp://YOUR_DROPLET_IP`
- Username: `root`
- Upload your entire project to `/var/www/qutekart/`

### Step 3: Install Dependencies

```bash
cd "/var/www/qutekart/Ready eCommerce-Admin with Customer Website/install"

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Note: --no-dev excludes development packages
```

### Step 4: Create Environment File

```bash
# Copy example env
cp .env.example .env

# Edit environment file
nano .env
```

**Update with these production values:**

```env
# === Application ===
APP_NAME="QuteKart"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://qutekart.com

# === Database ===
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=qutekart_prod
DB_USERNAME=qutekart_user
DB_PASSWORD=YOUR_SECURE_PASSWORD_HERE

# === Redis ===
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# === Mail (Using Resend - Sign up at resend.com) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=YOUR_RESEND_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@qutekart.com"
MAIL_FROM_NAME="QuteKart"

# === Storage (We'll use DigitalOcean Spaces - set up next) ===
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_SPACES_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SPACES_SECRET
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=qutekart-prod
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://qutekart-prod.nyc3.digitaloceanspaces.com

# === Queue ===
QUEUE_CONNECTION=redis

# === Broadcast ===
BROADCAST_DRIVER=pusher

# === Pusher ===
PUSHER_APP_ID=YOUR_PUSHER_APP_ID
PUSHER_APP_KEY=YOUR_PUSHER_KEY
PUSHER_APP_SECRET=YOUR_PUSHER_SECRET
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=YOUR_CLUSTER

# === Business Model ===
BUSINESS_MODEL=multi

# === Stripe (PRODUCTION KEYS!) ===
STRIPE_KEY=pk_live_YOUR_KEY
STRIPE_SECRET=sk_live_YOUR_SECRET
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET

# === Firebase ===
FIREBASE_SERVER_KEY=YOUR_FIREBASE_SERVER_KEY

# === Tenancy ===
CENTRAL_DOMAINS=qutekart.com,www.qutekart.com

# === Session ===
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

**Save file:** `Ctrl+X`, then `Y`, then `Enter`

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

### Step 6: Run Migrations

```bash
# Run migrations
php artisan migrate --force

# Seed essential data
php artisan db:seed --force

# Seed subscription plans
php artisan db:seed --class=PlansTableSeeder --force

# Apply ZARA branding
php artisan db:seed --class=ZaraThemeSeeder --force

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 7: Create Admin User

```bash
php artisan tinker
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

### Step 8: Set Permissions

```bash
cd /var/www/qutekart

# Set ownership
chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Storage and cache must be writable
chmod -R 775 "Ready eCommerce-Admin with Customer Website/install/storage"
chmod -R 775 "Ready eCommerce-Admin with Customer Website/install/bootstrap/cache"

# Create installed flag
echo "Installed on $(date)" > "Ready eCommerce-Admin with Customer Website/install/storage/installed"
chmod 664 "Ready eCommerce-Admin with Customer Website/install/storage/installed"
```

---

## 🌍 Part 6: Configure Nginx (10 minutes)

```bash
# Create Nginx configuration
nano /etc/nginx/sites-available/qutekart
```

**Paste this configuration:**

```nginx
# Main domain and admin
server {
    listen 80;
    server_name qutekart.com www.qutekart.com;

    root /var/www/qutekart/Ready eCommerce-Admin with Customer Website/install/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/qutekart-access.log;
    error_log /var/log/nginx/qutekart-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Max upload size
    client_max_body_size 100M;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\.env {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}

# Wildcard subdomain for tenant shops
server {
    listen 80;
    server_name *.qutekart.com;

    root /var/www/qutekart/Ready eCommerce-Admin with Customer Website/install/public;
    index index.php index.html;

    access_log /var/log/nginx/qutekart-subdomains-access.log;
    error_log /var/log/nginx/qutekart-subdomains-error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

**Save file:** `Ctrl+X`, `Y`, `Enter`

**Enable site:**

```bash
# Create symbolic link
ln -s /etc/nginx/sites-available/qutekart /etc/nginx/sites-enabled/

# Remove default site
rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t

# Should show: "syntax is okay" and "test is successful"

# Reload Nginx
systemctl reload nginx
```

---

## 🔒 Part 7: Setup SSL Certificate (5 minutes)

```bash
# Get SSL certificate for main domain
certbot --nginx -d qutekart.com -d www.qutekart.com

# Follow prompts:
# - Enter email address
# - Agree to terms (Y)
# - Share email with EFF (optional - N or Y)
# - Redirect HTTP to HTTPS? Choose: 2 (Redirect)

# For wildcard subdomain SSL (optional but recommended)
certbot certonly --manual --preferred-challenges dns -d "*.qutekart.com"

# This will ask you to add a DNS TXT record
# Follow instructions, add the TXT record in DigitalOcean DNS
# Wait 1-2 minutes, then press Enter
```

**Auto-renewal is configured automatically!**

**Verify SSL:**
```bash
# Test renewal
certbot renew --dry-run

# Should show: "Congratulations, all simulated renewals succeeded"
```

---

## ⚙️ Part 8: Configure Queue Workers (5 minutes)

```bash
# Create Supervisor configuration
nano /etc/supervisor/conf.d/qutekart-worker.conf
```

**Paste this:**

```ini
[program:qutekart-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
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

**Save and start workers:**

```bash
# Update Supervisor
supervisorctl reread
supervisorctl update

# Start workers
supervisorctl start qutekart-worker:*

# Check status
supervisorctl status
# Should show: qutekart-worker:qutekart-worker_00  RUNNING
#              qutekart-worker:qutekart-worker_01  RUNNING
```

---

## ⏰ Part 9: Configure Laravel Scheduler (2 minutes)

```bash
# Edit crontab for www-data user
crontab -e -u www-data

# If asked to choose editor, select nano (usually option 1)
```

**Add this line at the end:**

```
* * * * * cd /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install && php artisan schedule:run >> /dev/null 2>&1
```

**Save:** `Ctrl+X`, `Y`, `Enter`

---

## 🎉 Part 10: Test Your Deployment!

### Check if Site is Live

```bash
# Test HTTP response
curl -I https://qutekart.com

# Should return: HTTP/2 200
```

**Open in browser:**
- **Main site:** https://qutekart.com
- **Admin panel:** https://qutekart.com/admin

**Login with the admin account you created earlier!**

### Verify Services

```bash
# Check PHP-FPM
systemctl status php8.2-fpm

# Check Nginx
systemctl status nginx

# Check PostgreSQL
systemctl status postgresql

# Check Redis
redis-cli ping

# Check Queue Workers
supervisorctl status

# Check Laravel logs
tail -f /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/storage/logs/laravel.log

# Check Nginx logs
tail -f /var/log/nginx/qutekart-error.log
```

---

## 📦 Part 11: Setup DigitalOcean Spaces (Storage) (10 minutes)

### Create Spaces Bucket

1. **In DigitalOcean:** Go to **Spaces** → **Create Space**

2. **Configuration:**
   - Region: Same as your droplet (e.g., NYC3)
   - Name: `qutekart-prod`
   - File Listing: **Public** (for product images)
   - CDN: **Enable** (faster loading)

3. **Click "Create Space"**

### Get API Keys

1. Go to **API** → **Spaces Keys**
2. Click **"Generate New Key"**
3. Name: `QuteKart Production`
4. Copy **Access Key** and **Secret Key**

### Update Laravel .env

```bash
nano /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/.env
```

**Update these lines:**

```env
AWS_ACCESS_KEY_ID=YOUR_SPACES_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SPACES_SECRET_KEY
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=qutekart-prod
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_URL=https://qutekart-prod.nyc3.cdn.digitaloceanspaces.com
```

**Reload config:**

```bash
cd /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install
php artisan config:cache
```

**Test upload:**
```bash
php artisan tinker
```

```php
Storage::disk('s3')->put('test.txt', 'Hello from QuteKart!');
Storage::disk('s3')->exists('test.txt');
// Should return: true
exit
```

---

## 🎯 Part 12: Configure Stripe Webhooks (5 minutes)

1. **Go to:** https://dashboard.stripe.com/webhooks

2. **Click "Add endpoint"**

3. **Endpoint URL:** `https://qutekart.com/api/webhooks/stripe`

4. **Select events:**
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.trial_will_end`

5. **Click "Add endpoint"**

6. **Copy "Signing secret"** (starts with `whsec_`)

7. **Update .env:**
```bash
nano /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/.env
```

Add:
```env
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

8. **Reload config:**
```bash
php artisan config:cache
```

**Test webhook:**
- In Stripe dashboard, send test webhook
- Check Laravel logs: `tail -f storage/logs/laravel.log`

---

## ✅ Deployment Checklist

- [ ] Droplet created and running
- [ ] DNS configured (A records for @, www, *)
- [ ] SSH access working
- [ ] All dependencies installed (PHP, PostgreSQL, Redis, Nginx, Supervisor)
- [ ] Database created and configured
- [ ] Laravel code deployed
- [ ] Composer dependencies installed
- [ ] .env file configured with production values
- [ ] Database migrated and seeded
- [ ] Admin user created
- [ ] File permissions set correctly
- [ ] Nginx configured for main domain and subdomains
- [ ] SSL certificate installed
- [ ] HTTPS working (http redirects to https)
- [ ] Queue workers running
- [ ] Scheduler cron job configured
- [ ] DigitalOcean Spaces created and configured
- [ ] Stripe webhooks configured
- [ ] Can access https://qutekart.com
- [ ] Can login to admin panel
- [ ] Logs show no errors

---

## 🔍 Troubleshooting

### Site not loading?

```bash
# Check Nginx
nginx -t
systemctl restart nginx

# Check PHP-FPM
systemctl restart php8.2-fpm

# Check logs
tail -f /var/log/nginx/qutekart-error.log
tail -f /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/storage/logs/laravel.log
```

### Database connection failed?

```bash
# Test database connection
psql -U qutekart_user -d qutekart_prod -h localhost -W

# Check .env database credentials
cat /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/.env | grep DB_
```

### Queue not processing?

```bash
# Check supervisor
supervisorctl status

# Restart workers
supervisorctl restart qutekart-worker:*

# Check worker logs
tail -f /var/www/qutekart/worker.log
```

### 500 Error?

```bash
# Check Laravel logs
tail -50 /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## 📊 Monitor Your Application

```bash
# Check server resources
htop

# Check disk space
df -h

# Check memory
free -h

# Monitor logs in real-time
tail -f /var/www/qutekart/Ready\ eCommerce-Admin\ with\ Customer\ Website/install/storage/logs/laravel.log

# Check queue status
php artisan queue:work --once
```

---

## 💰 Monthly Costs Estimate

| Service | Cost |
|---------|------|
| DigitalOcean Droplet (4GB) | $24/month |
| DigitalOcean Spaces (250GB) | $5/month |
| Domain (yearly ÷ 12) | ~$1/month |
| Resend Email (free tier) | $0 |
| Pusher (free tier) | $0 |
| Firebase (free tier) | $0 |
| Stripe (pay per transaction) | 2.9% + 30¢ |
| **TOTAL** | **~$30/month** |

---

## 🎉 You're Live!

Your QuteKart SaaS platform is now deployed and running on DigitalOcean!

**Next Steps:**
1. Test all features (shop creation, subscriptions, products)
2. Create a test shop with subdomain
3. Test Stripe subscription flow
4. Configure Flutter mobile app to use production API
5. Build and test mobile app
6. Deploy to app stores

**Support:**
- DigitalOcean Docs: https://docs.digitalocean.com/
- Laravel Docs: https://laravel.com/docs
- Your logs: `storage/logs/laravel.log`

---

**Deployed on:** $(date)
**Server:** qutekart.com
**Status:** 🚀 Live
