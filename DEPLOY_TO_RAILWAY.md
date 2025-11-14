# Deploy QuteKart to Railway - Quick Deploy Guide

**Estimated Time:** 30-45 minutes
**Cost:** ~$10-30/month (usage-based)
**Pros:** Super fast deployment, automatic SSL, easy scaling
**Cons:** More expensive than DigitalOcean, less control

---

## ⚠️ Important Note

Railway is great for **quick testing** but has limitations for a full SaaS platform:
- Queue workers need separate service (extra cost)
- Scheduler requires workarounds
- Storage should use external S3 (no local files persist)
- Can get expensive with traffic

**Recommended for:** Testing, MVP, small traffic
**For production:** Consider DigitalOcean (see DEPLOY_TO_DIGITALOCEAN.md)

---

## 🚀 Quick Deploy Steps

### Step 1: Prepare Your Code (5 min)

**Add these files to your repo:**

**1. `Procfile` (create in project root)**

```
web: cd "Ready eCommerce-Admin with Customer Website/install" && php artisan serve --host=0.0.0.0 --port=$PORT
worker: cd "Ready eCommerce-Admin with Customer Website/install" && php artisan queue:work redis --tries=3
```

**2. `nixpacks.toml` (create in project root)**

```toml
[phases.setup]
nixPkgs = ["php82", "php82Packages.composer", "postgresql"]

[phases.install]
cmds = [
    "cd 'Ready eCommerce-Admin with Customer Website/install'",
    "composer install --optimize-autoloader --no-dev"
]

[phases.build]
cmds = [
    "cd 'Ready eCommerce-Admin with Customer Website/install'",
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
]

[start]
cmd = "cd 'Ready eCommerce-Admin with Customer Website/install' && php artisan serve --host=0.0.0.0 --port=$PORT"
```

**3. Commit and push:**

```bash
git add Procfile nixpacks.toml
git commit -m "Add Railway deployment configuration"
git push
```

---

### Step 2: Create Railway Account (2 min)

1. Go to https://railway.app/
2. Click **"Start a New Project"**
3. Sign up with GitHub
4. Authorize Railway

---

### Step 3: Deploy from GitHub (10 min)

1. **Click "New Project"**

2. **Select "Deploy from GitHub repo"**

3. **Choose your QuteKart repository**

4. **Railway will detect it's a PHP project and start deploying**

5. **Wait 2-5 minutes** for initial deployment

---

### Step 4: Add PostgreSQL Database (2 min)

1. **In your project:** Click **"+ New"**

2. **Select "Database" → "PostgreSQL"**

3. Railway will create a PostgreSQL instance

4. **Copy connection details** (available in Variables tab)

---

### Step 5: Add Redis (2 min)

1. **Click "+ New"** again

2. **Select "Database" → "Redis"**

3. Railway creates Redis instance

4. **Copy connection URL**

---

### Step 6: Configure Environment Variables (10 min)

1. **Go to your main service** (the PHP app)

2. **Click "Variables" tab**

3. **Add these variables:**

```env
APP_NAME=QuteKart
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_RAILWAY_DOMAIN.up.railway.app

# Database (Railway auto-provides these as DATABASE_URL, split it)
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

# Redis (from Redis service)
REDIS_HOST=${{Redis.REDIS_HOST}}
REDIS_PORT=${{Redis.REDIS_PORT}}
REDIS_PASSWORD=${{Redis.REDIS_PASSWORD}}

# Storage (use AWS S3 or DigitalOcean Spaces)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_s3_key
AWS_SECRET_ACCESS_KEY=your_s3_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=qutekart-prod
AWS_ENDPOINT=https://s3.amazonaws.com
AWS_URL=https://qutekart-prod.s3.amazonaws.com

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=your_resend_api_key
MAIL_FROM_ADDRESS=noreply@qutekart.com

# Pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1

# Stripe
STRIPE_KEY=pk_live_your_key
STRIPE_SECRET=sk_live_your_secret

# Business Model
BUSINESS_MODEL=multi

# Tenancy
CENTRAL_DOMAINS=qutekart.com,www.qutekart.com
```

4. **Click "Save"** (app will redeploy automatically)

---

### Step 7: Run Migrations (5 min)

**Railway doesn't have a direct shell access, so we need to run migrations on deploy:**

**Option A: Add to `nixpacks.toml`**

Update the build phase:

```toml
[phases.build]
cmds = [
    "cd 'Ready eCommerce-Admin with Customer Website/install'",
    "php artisan migrate --force",
    "php artisan db:seed --force",
    "php artisan db:seed --class=PlansTableSeeder --force",
    "php artisan db:seed --class=ZaraThemeSeeder --force",
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
]
```

**Option B: Use Railway CLI**

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link to project
railway link

# Run migrations
railway run php artisan migrate --force
railway run php artisan db:seed --force
```

---

### Step 8: Add Custom Domain (5 min)

1. **In Railway project:** Click "Settings"

2. **Scroll to "Domains"**

3. **Click "Generate Domain"** (gets you a *.up.railway.app domain)

4. **Or add custom domain:**
   - Click "Custom Domain"
   - Enter: `qutekart.com`
   - Railway provides DNS records
   - Add to your domain registrar:
     ```
     CNAME record: @ → your-project.up.railway.app
     CNAME record: www → your-project.up.railway.app
     ```

5. **Railway auto-configures SSL!** ✅

---

### Step 9: Deploy Queue Worker (Optional, +$5/month)

**Queue workers need a separate service in Railway:**

1. **Click "+ New"** in project

2. **Select "Empty Service"**

3. **Connect to same GitHub repo**

4. **In Settings:**
   - Start Command: `cd 'Ready eCommerce-Admin with Customer Website/install' && php artisan queue:work redis`
   - Share same environment variables

5. **Deploy**

---

### Step 10: Test Your Site

1. **Visit your Railway URL** or custom domain

2. **Create admin user** (using Railway CLI):
   ```bash
   railway run php artisan tinker
   ```

   Then:
   ```php
   App\Models\User::create([
       'name' => 'Admin',
       'email' => 'admin@qutekart.com',
       'phone' => '1234567890',
       'password' => bcrypt('your-password'),
       'is_active' => true,
   ])->assignRole('root');
   exit
   ```

3. **Login** at https://your-domain.com/admin

---

## ⚠️ Railway Limitations for This Project

### Issues You'll Face:

1. **No persistent file storage**
   - Uploaded images will disappear on redeploy
   - **Solution:** MUST use S3/Spaces for all uploads

2. **Queue worker costs extra**
   - Each worker = separate service = ~$5-10/month more

3. **No cron jobs**
   - Laravel scheduler won't work without workarounds
   - **Solution:** Use external cron service (cron-job.org)

4. **Subdomain tenants tricky**
   - Wildcard domains need Pro plan ($20/month)

5. **Can get expensive**
   - Usage-based pricing
   - High traffic = high costs

---

## 💰 Railway Cost Estimate

| Component | Monthly Cost |
|-----------|-------------|
| Web service (1GB RAM) | ~$5-10 |
| PostgreSQL | ~$5 |
| Redis | ~$5 |
| Queue worker (optional) | ~$5-10 |
| Pro plan (for wildcards) | ~$20 |
| **TOTAL** | **$25-50/month** |

**Plus:**
- S3/Spaces: $5/month
- More with higher traffic

---

## ✅ Railway Deployment Checklist

- [ ] Code pushed to GitHub
- [ ] `Procfile` added
- [ ] `nixpacks.toml` added
- [ ] Railway project created
- [ ] PostgreSQL added
- [ ] Redis added
- [ ] Environment variables configured
- [ ] Migrations run
- [ ] Admin user created
- [ ] Custom domain added (optional)
- [ ] SSL working
- [ ] Queue worker deployed (optional)
- [ ] S3/Spaces configured for file storage

---

## 🔍 Troubleshooting

**Build fails?**
- Check `nixpacks.toml` syntax
- View build logs in Railway dashboard

**Database connection failed?**
- Verify Postgres variables: `${{Postgres.PGHOST}}`
- Check if DATABASE_URL is set

**Queue not processing?**
- Deploy separate queue worker service
- Check Redis connection

**Files not uploading?**
- Railway doesn't persist files
- Must configure S3/Spaces in FILESYSTEM_DISK

---

## 📊 Railway vs DigitalOcean Comparison

| Feature | Railway | DigitalOcean |
|---------|---------|--------------|
| Setup Time | 30 min | 1-2 hours |
| Complexity | Easy | Moderate |
| Cost | $25-50/mo | $20-30/mo |
| Control | Limited | Full |
| Scaling | Automatic | Manual |
| File Storage | Must use S3 | Can use local or S3 |
| Queue Workers | Extra cost | Included |
| Scheduler | Need workaround | Native support |
| SSH Access | Via CLI | Direct |
| **Best For** | Testing, MVP | Production, SaaS |

---

## 🎯 Recommendation

**For quick testing:** ✅ Use Railway
**For production SaaS:** ✅ Use DigitalOcean

Railway is great to see if your app works, but for a multi-tenant SaaS with subscriptions, queue workers, and file uploads, **DigitalOcean is more suitable and cost-effective**.

---

**Deployment Guide:** Railway Quick Deploy
**Status:** Alternative option for testing
**Recommended:** See `DEPLOY_TO_DIGITALOCEAN.md` for production
