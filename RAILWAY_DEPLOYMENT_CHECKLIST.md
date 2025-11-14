# Railway Deployment Checklist for QuteKart

**Ready to deploy!** Follow this checklist to ensure smooth deployment.

---

## ✅ Pre-Deployment Checklist

### Files Created (Already Done ✅)

- [x] `Procfile` - Defines web and worker processes
- [x] `nixpacks.toml` - Build configuration
- [x] `.railwayignore` - Excludes unnecessary files
- [x] All code pushed to GitHub

### Required External Services

Before deploying, sign up for these services (all have free tiers):

#### 1. DigitalOcean Spaces (File Storage) - Required
- [ ] Create account: https://www.digitalocean.com/
- [ ] Create Spaces bucket: `qutekart-prod`
- [ ] Get Access Key and Secret Key
- [ ] Note: **Railway has no persistent file storage** - Spaces is REQUIRED

#### 2. Resend (Email) - Required
- [ ] Create account: https://resend.com/
- [ ] Add domain: qutekart.com
- [ ] Verify domain (add DNS records)
- [ ] Get API key (starts with `re_`)

#### 3. Pusher (Real-time Chat) - Required
- [ ] Create account: https://pusher.com/
- [ ] Create app: QuteKart
- [ ] Get App ID, Key, Secret, Cluster

#### 4. Firebase (Push Notifications) - Required
- [ ] Create project: https://console.firebase.google.com/
- [ ] Get Server Key from Cloud Messaging settings

#### 5. Stripe (Payments) - Required
- [ ] Create account: https://stripe.com/
- [ ] Switch to Live mode
- [ ] Get production API keys (pk_live_ and sk_live_)
- [ ] Create products (Free, Starter, Growth, Enterprise)
- [ ] Get Price IDs for each plan

---

## 🚀 Railway Deployment Steps

### Step 1: Create Railway Account (2 min)

1. Go to https://railway.app/
2. Click "Start a New Project"
3. Sign up with GitHub
4. Authorize Railway

### Step 2: Add PostgreSQL Database (2 min)

1. Click "New Project"
2. Select "Provision PostgreSQL"
3. Wait for database to be created
4. Note: Database URL will be auto-provided as variables

### Step 3: Add Redis (2 min)

1. In same project, click "+ New"
2. Select "Database" → "Redis"
3. Wait for Redis to be created

### Step 4: Deploy from GitHub (5 min)

1. In same project, click "+ New"
2. Select "GitHub Repo"
3. Authorize Railway to access your repos
4. Select your QuteKart repository
5. Railway will detect PHP and start building
6. **Wait 5-10 minutes** for first build

### Step 5: Configure Environment Variables (15 min)

Click on your web service → "Variables" tab → "Raw Editor"

**Paste these variables (replace YOUR_* values):**

```env
# === Application ===
APP_NAME=QuteKart
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY_WILL_BE_GENERATED
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

# === Database (Auto-provided by Railway PostgreSQL) ===
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

# === Redis (Auto-provided by Railway Redis) ===
REDIS_HOST=${{Redis.REDIS_HOST}}
REDIS_PORT=${{Redis.REDIS_PORT}}
REDIS_PASSWORD=${{Redis.REDIS_PASSWORD}}

# === Storage (DigitalOcean Spaces - REQUIRED!) ===
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

# === Mail (Resend) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=YOUR_RESEND_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@qutekart.com
MAIL_FROM_NAME=QuteKart

# === Pusher (Real-time) ===
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
CENTRAL_DOMAINS=qutekart.com,www.qutekart.com,${{RAILWAY_PUBLIC_DOMAIN}}

# === Session ===
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

**Click "Save"** - Railway will redeploy with new environment variables.

### Step 6: Run Migrations (5 min)

**Option A: Using Railway CLI (Recommended)**

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link to your project
railway link

# Navigate to Laravel directory
cd "backend/install"

# Generate app key
railway run php artisan key:generate

# Run migrations
railway run php artisan migrate --force

# Seed database
railway run php artisan db:seed --force
railway run php artisan db:seed --class=PlansTableSeeder --force
railway run php artisan db:seed --class=ZaraThemeSeeder --force

# Create admin user
railway run php artisan tinker
```

Then in tinker:
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

**Option B: Update nixpacks.toml (Automated)**

This will run migrations on every deploy:

Edit `nixpacks.toml`:
```toml
[phases.build]
cmds = [
    "cd 'backend/install'",
    "php artisan migrate --force",
    "php artisan db:seed --force --class=PlansTableSeeder",
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
]
```

**Push changes** and Railway will redeploy with migrations.

### Step 7: Configure Custom Domain (5 min)

#### In Railway:

1. Go to your web service → "Settings"
2. Scroll to "Networking" → "Public Networking"
3. Click "Generate Domain" (gets you *.up.railway.app)
4. Or add custom domain:
   - Click "Custom Domain"
   - Enter: `qutekart.com`
   - Railway provides CNAME value

#### In Your Domain DNS:

Add these records:
```
CNAME   @     your-project.up.railway.app.
CNAME   www   your-project.up.railway.app.
```

**SSL is automatic!** ✅

### Step 8: Deploy Queue Worker (Optional - $5-10/mo)

Queue workers need a separate service in Railway:

1. Click "+ New" in project
2. Select "GitHub Repo"
3. Choose same QuteKart repo
4. In "Settings" → "Deploy":
   - **Start Command:** `cd 'backend/install' && php artisan queue:work redis --sleep=3 --tries=3`
5. In "Variables":
   - Share same variables as web service
   - Or use "Shared Variables" feature
6. Deploy

### Step 9: Update Stripe Price IDs (5 min)

```bash
# Using Railway CLI
railway run php artisan tinker
```

```php
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

### Step 10: Configure Stripe Webhooks (5 min)

1. Go to: https://dashboard.stripe.com/webhooks
2. Click "Add endpoint"
3. **Endpoint URL:** `https://your-domain.up.railway.app/api/webhooks/stripe`
4. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.trial_will_end`
5. Click "Add endpoint"
6. Copy "Signing secret" (starts with `whsec_`)
7. Add to Railway Variables:
   ```
   STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
   ```
8. Save (Railway will redeploy)

---

## ✅ Verification Checklist

After deployment, verify these:

### Basic Checks
- [ ] Site loads: https://your-domain.up.railway.app
- [ ] Admin panel accessible: /admin
- [ ] Can login with admin credentials
- [ ] No errors in Railway logs

### Database Checks
- [ ] Subscription plans exist (check /admin)
- [ ] Can create a new shop
- [ ] ZARA branding applied (black theme)

### Service Checks
- [ ] File uploads work (images save to Spaces)
- [ ] Emails send (check Resend dashboard)
- [ ] Real-time chat connects (Pusher debug console)
- [ ] Queue processes jobs (check Railway worker logs)

### Subscription Checks
- [ ] Can view subscription plans
- [ ] Stripe checkout works (test mode first!)
- [ ] Webhooks receive from Stripe
- [ ] Usage limits enforced

---

## ⚠️ Railway Limitations to Know

### 1. No Persistent File Storage
- **Issue:** Uploaded files deleted on redeploy
- **Solution:** MUST use DigitalOcean Spaces (configured above)
- **Verification:** Upload product image, redeploy, check if image still loads

### 2. Wildcard Subdomains (*.qutekart.com)
- **Issue:** Requires Railway Pro plan ($20/month)
- **Solution:** Either:
  - Upgrade to Pro for wildcard support
  - Or manually add each subdomain as custom domain
  - Or use shop IDs instead of subdomains initially

### 3. Queue Worker Costs Extra
- **Issue:** Separate service = extra $5-10/month
- **Solution:** Deploy worker service (optional for testing)

### 4. Scheduler (Cron Jobs)
- **Issue:** No native cron support
- **Solution:** Use external cron service:
  - https://cron-job.org/
  - Set to hit: `https://your-domain/schedule-run` every minute
  - Create route in Laravel to run `php artisan schedule:run`

### 5. Can Get Expensive
- **Issue:** Usage-based pricing
- **Solution:** Monitor usage in Railway dashboard
- **Estimated:** $10-30/month for testing, more with traffic

---

## 🔍 Troubleshooting

### Build Fails

**Check Railway build logs:**
1. Click on your service
2. Go to "Deployments" tab
3. Click on failed deployment
4. View logs

**Common issues:**
- Composer install failed → Check `composer.json`
- Path issues → Check `nixpacks.toml` paths
- PHP version → Nixpacks uses PHP 8.2

**Solution:** Fix issue, push to GitHub, Railway auto-redeploys

### Database Connection Failed

**Check:**
1. PostgreSQL service is running
2. Environment variables correct:
   ```bash
   railway variables
   # Should show Postgres.* variables
   ```
3. Database credentials in Variables tab

**Fix:**
```bash
# Test connection
railway run php artisan migrate:status
```

### Queue Not Processing

**Check:**
1. Queue worker service deployed?
2. Redis variables shared?
3. Worker logs show errors?

**View logs:**
```bash
# In Railway dashboard
# Click worker service → Deployments → View logs
```

### Files Not Uploading

**Check:**
1. DigitalOcean Spaces configured?
2. AWS_* variables set correctly?
3. Bucket public?

**Test:**
```bash
railway run php artisan tinker

Storage::disk('s3')->put('test.txt', 'Hello World');
Storage::disk('s3')->exists('test.txt');
# Should return true
exit
```

### Subdomain Not Working

**Remember:** Wildcard requires Railway Pro ($20/mo)

**Workaround:**
1. Manually add each subdomain as custom domain
2. Or use shop IDs in URL: `qutekart.com/shop/123`

---

## 💰 Cost Estimate

| Component | Cost |
|-----------|------|
| Web service (hobby tier) | ~$5-10/mo |
| PostgreSQL | ~$5/mo |
| Redis | ~$5/mo |
| Queue worker (optional) | ~$5-10/mo |
| Pro plan (for wildcards) | ~$20/mo |
| **Subtotal** | **$20-50/mo** |
| DigitalOcean Spaces | $5/mo |
| **TOTAL** | **$25-55/mo** |

Plus free tiers:
- Resend (3,000 emails/month)
- Pusher (100 connections)
- Firebase (10M notifications)

**Can increase with traffic** (usage-based)

---

## 📊 Post-Deployment

### Monitor Your App

**Railway Dashboard:**
- View metrics (CPU, RAM, requests)
- View logs in real-time
- Check deployments

**Commands:**
```bash
# View logs
railway logs

# Run artisan commands
railway run php artisan <command>

# Access database
railway connect Postgres
```

### Update Your App

```bash
# Make changes locally
git add .
git commit -m "Your changes"
git push

# Railway auto-deploys on push! ✅
```

---

## ✅ Final Checklist

- [ ] Railway account created
- [ ] PostgreSQL added
- [ ] Redis added
- [ ] Web service deployed from GitHub
- [ ] Environment variables configured
- [ ] Migrations run
- [ ] Admin user created
- [ ] Subscription plans seeded
- [ ] ZARA branding applied
- [ ] DigitalOcean Spaces configured
- [ ] Custom domain added (optional)
- [ ] SSL working (automatic)
- [ ] Queue worker deployed (optional)
- [ ] Stripe Price IDs updated
- [ ] Stripe webhooks configured
- [ ] Test subscription flow
- [ ] All features tested

---

## 🎉 You're Live on Railway!

**Your QuteKart SaaS platform is now running!**

**Next Steps:**
1. Test all features thoroughly
2. Create test shop with subscription
3. Test Stripe payment flow (test mode)
4. Configure Flutter mobile app
5. Test mobile app with Railway backend
6. Switch to production Stripe keys when ready
7. Launch! 🚀

**Support:**
- Railway Docs: https://docs.railway.app/
- Railway Discord: https://discord.gg/railway
- QuteKart Docs: See all *.md files in repo

---

**Deployed on:** Railway
**Status:** Ready to deploy! 🚀
**Estimated Setup Time:** 45-60 minutes
