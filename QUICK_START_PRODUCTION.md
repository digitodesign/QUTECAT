# 🚀 Quick Start - Production Deployment Guide

**Goal:** Get QUTECAT live in production in **1 business day**  
**Total Time:** 4-6 hours  
**Difficulty:** Medium  

---

## ✅ Pre-Flight Check

Before starting, ensure you have:
- [ ] Credit card for third-party services (most have free tiers)
- [ ] Domain name purchased (or use Railway subdomain temporarily)
- [ ] Railway account created
- [ ] 4-6 hours of uninterrupted time

---

## 📋 Step-by-Step Deployment

### **Phase 1: Third-Party Service Setup** (2 hours)

#### 1.1 Stripe Setup (30 min)
```bash
🌐 Go to: https://dashboard.stripe.com/register

Steps:
1. Create account → Verify email
2. Switch to "Live mode" (toggle in top right)
3. Go to "Developers" → "API keys"
4. Copy:
   STRIPE_KEY=pk_live_***
   STRIPE_SECRET=sk_live_***

5. Create Products:
   Products → "+ Create product"
   
   Product 1: Starter Plan
   - Price: $29/month (recurring monthly)
   - Copy Price ID: price_***starter***
   
   Product 2: Growth Plan  
   - Price: $99/month (recurring monthly)
   - Copy Price ID: price_***growth***
   
   Product 3: Enterprise Plan
   - Price: $299/month (recurring monthly)
   - Copy Price ID: price_***enterprise***

6. Create Webhook:
   Developers → Webhooks → "+ Add endpoint"
   - Endpoint URL: https://qutecat.up.railway.app/api/webhooks/stripe
   - Select events:
     ☑ customer.subscription.created
     ☑ customer.subscription.updated
     ☑ customer.subscription.deleted
     ☑ invoice.payment_succeeded
     ☑ invoice.payment_failed
     ☑ customer.subscription.trial_will_end
   - Copy: STRIPE_WEBHOOK_SECRET=whsec_***

📝 Save all credentials in a secure note!
```

#### 1.2 Pusher Setup (15 min)
```bash
🌐 Go to: https://pusher.com/signup

Steps:
1. Create free account (100 connections free)
2. Create Channel → Name: "QuteCart Production"
3. Select cluster: "us-east-1" (or closest to your users)
4. Copy:
   PUSHER_APP_ID=***
   PUSHER_APP_KEY=***
   PUSHER_APP_SECRET=***
   PUSHER_APP_CLUSTER=us-east-1
```

#### 1.3 Firebase Setup (20 min)
```bash
🌐 Go to: https://console.firebase.google.com/

Steps:
1. "+ Add project" → Name: QuteCart
2. Disable Google Analytics (optional)

For Android:
3. Click Android icon → Add Android app
   - Package: com.readyecommerce.apps
   - Download google-services.json
   - Save to: FlutterApp/android/app/google-services.json

For iOS (optional):
4. Click iOS icon → Add iOS app
   - Bundle ID: com.readyecommerce.apps
   - Download GoogleService-Info.plist
   - Save to: FlutterApp/ios/Runner/GoogleService-Info.plist

5. Enable Cloud Messaging:
   - Go to: Project Settings → Cloud Messaging
   - Copy Server Key: FIREBASE_SERVER_KEY=AAAA***
```

#### 1.4 Email Service (Resend) (20 min)
```bash
🌐 Go to: https://resend.com/signup

Steps:
1. Create free account (3,000 emails/month free)
2. Verify email
3. Domains → "+ Add Domain"
   - Enter your domain (e.g., qutekart.com)
   - Add DNS records to your domain registrar:
     TXT: resend._domainkey
     MX: feedback-smtp.resend.com
   - Wait for verification (5-10 min)

4. API Keys → "+ Create API Key"
   - Name: QuteCart Production
   - Copy: MAIL_PASSWORD=re_***

5. Set:
   MAIL_HOST=smtp.resend.com
   MAIL_PORT=587
   MAIL_USERNAME=resend
   MAIL_FROM_ADDRESS=noreply@qutekart.com
```

---

### **Phase 2: Deploy to Railway** (1.5 hours)

#### 2.1 Setup Railway Project (15 min)
```bash
# Install Railway CLI (if not installed)
npm install -g @railway/cli

# Or use web interface: https://railway.app/new

# Login
railway login

# From project root
cd c:\Users\WEBDRIPTECH\Desktop\QUTECAT
railway init

# Select: "Create new project"
# Project name: QUTECAT Production
```

#### 2.2 Add Database Services (10 min)
```bash
# In Railway Dashboard:
1. Click "+ New" → "Database" → "PostgreSQL"
   - Name: qutecat-db
   - Railway will auto-set DATABASE_URL

2. Click "+ New" → "Database" → "Redis"
   - Name: qutecat-redis
   - Railway will auto-set REDIS_URL

✅ Databases are now provisioned!
```

#### 2.3 Configure Environment Variables (30 min)
```bash
# In Railway Dashboard → QUTECAT project → Variables:

# App Settings
APP_NAME=QuteCart
APP_ENV=production
APP_DEBUG=false
APP_URL=https://qutecat.up.railway.app (use Railway URL initially)
APP_KEY=base64:*** (generate: php artisan key:generate --show)

# Database (auto-set by Railway, verify):
DATABASE_URL=${{PostgreSQL.DATABASE_URL}}
REDIS_URL=${{Redis.REDIS_URL}}

# Stripe (from Phase 1.1)
STRIPE_KEY=pk_live_***
STRIPE_SECRET=sk_live_***
STRIPE_WEBHOOK_SECRET=whsec_***

# Pusher (from Phase 1.2)
PUSHER_APP_ID=***
PUSHER_APP_KEY=***
PUSHER_APP_SECRET=***
PUSHER_APP_CLUSTER=us-east-1

# Firebase (from Phase 1.3)
FIREBASE_SERVER_KEY=AAAA***

# Email (from Phase 1.4)
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_***
MAIL_FROM_ADDRESS=noreply@qutekart.com
MAIL_FROM_NAME=QuteCart

# Cloudflare R2 (already configured)
R2_ACCESS_KEY_ID=yef899f6197af9dc3b7bd7a8fb2ea128f
R2_SECRET_ACCESS_KEY=8f1bd5905c57d989942dc13c671278857f4fe95b5f818f811beb775e5f7807f7
R2_BUCKET=qutecat-production
R2_PRIVATE_BUCKET=qutecat-private
R2_ENDPOINT=https://d22237c467b01861fb0620336ff21f6e.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://pub-3d92172d800e48d4a3a7fa78cae3fb00.r2.dev

# Laravel Settings
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
FILESYSTEM_DISK=r2
```

#### 2.4 Deploy Main Web Service (15 min)
```bash
# Railway will auto-deploy from nixpacks.toml

# If manual deployment needed:
railway up

# Monitor deployment:
railway logs

# Check status:
railway status

# Should see:
✅ Build successful
✅ Deployment live
✅ URL: https://qutecat.up.railway.app
```

#### 2.5 Create Queue Worker Service (15 min)
```bash
# In Railway Dashboard:
1. Click "+ New" → "Empty Service"
2. Name: qutecat-worker
3. Settings → Root Directory: (leave blank)
4. Settings → Start Command:
   bash worker-start.sh
5. Variables → Copy all env vars from main service
6. Add: PROCESS_TYPE=worker
7. Deploy

# Verify worker is running:
railway logs --service=qutecat-worker

# Should see: "Processing jobs from queue..."
```

#### 2.6 Seed Production Database (15 min)
```bash
# SSH into Railway container:
railway run bash

# Run seeding script:
bash deploy-production-data.sh

# Expected output:
✅ Running migrations...
✅ Seeding essential system data...
✅ Seeding demo content...
✅ Applying ZARA theme...
✅ Deployment Complete!

Login Credentials:
  Root Admin:
    Email: root@qutekart.com
    Password: secret

# Exit SSH:
exit
```

---

### **Phase 3: Update Stripe with Price IDs** (15 min)

```bash
# SSH into Railway:
railway run bash
cd backend/install

# Enter Laravel Tinker:
php artisan tinker

# Update Starter Plan:
>>> $starter = App\Models\Plan::where('slug', 'starter')->first();
>>> $starter->stripe_price_id = 'price_***starter***';  # From Phase 1.1
>>> $starter->save();

# Update Growth Plan:
>>> $growth = App\Models\Plan::where('slug', 'growth')->first();
>>> $growth->stripe_price_id = 'price_***growth***';
>>> $growth->save();

# Update Enterprise Plan:
>>> $enterprise = App\Models\Plan::where('slug', 'enterprise')->first();
>>> $enterprise->stripe_price_id = 'price_***enterprise***';
>>> $enterprise->save();

# Verify:
>>> App\Models\Plan::all(['name', 'stripe_price_id']);

# Exit:
>>> exit
exit
```

---

### **Phase 4: Update Flutter App** (30 min)

#### 4.1 Update Configuration
```bash
cd FlutterApp

# 1. Replace Firebase config files (from Phase 1.3)
# Android:
copy google-services.json android/app/google-services.json

# iOS (if applicable):
copy GoogleService-Info.plist ios/Runner/GoogleService-Info.plist

# 2. Update API & Pusher config:
# Edit: lib/config/app_constants.dart
```

```dart
// lib/config/app_constants.dart
class AppConstants {
  // API Configuration
  static String baseUrl = 'https://qutecat.up.railway.app/api';  // Railway URL
  
  // Pusher Configuration (from Phase 1.2)
  static String pusherApiKey = 'YOUR_PUSHER_KEY';
  static String pusherCluster = 'us-east-1';
  
  // App Information
  static String appName = 'QuteCart';
  static String appVersion = '1.0.0';
}
```

#### 4.2 Build APK
```bash
# Clean project:
flutter clean
flutter pub get

# Build release APK:
flutter build apk --release

# APK location:
# build/app/outputs/flutter-apk/app-release.apk

# Test on device:
flutter install --release
```

---

### **Phase 5: Testing & Verification** (1 hour)

#### 5.1 Backend API Tests (20 min)
```bash
# Test homepage:
curl https://qutecat.up.railway.app/api/home

# Test master data:
curl https://qutecat.up.railway.app/api/master

# Test products:
curl https://qutecat.up.railway.app/api/products

# Test language:
curl https://qutecat.up.railway.app/api/lang/en

# Test subscription plans:
curl https://qutecat.up.railway.app/api/subscription/plans

✅ All should return JSON data
```

#### 5.2 Admin Panel Test (15 min)
```bash
# Visit:
https://qutecat.up.railway.app/admin

# Login:
Email: root@qutekart.com
Password: secret

# Verify:
✅ Dashboard loads
✅ Products page loads
✅ Orders page loads
✅ Shops page loads
✅ Settings page loads
```

#### 5.3 Stripe Integration Test (15 min)
```bash
# 1. Register new shop via mobile app or website
# 2. Go to subscription page
# 3. Select "Growth Plan" ($99/month)
# 4. Enter test card: 4242 4242 4242 4242
#    Exp: 12/34, CVC: 123, ZIP: 12345
# 5. Complete subscription

# 6. Verify in Stripe Dashboard:
🌐 https://dashboard.stripe.com/subscriptions
✅ New subscription created
✅ Status: Active

# 7. Trigger webhook manually (test):
Stripe Dashboard → Webhooks → Test webhook
✅ Webhook received successfully
```

#### 5.4 Mobile App Test (10 min)
```bash
# Install app on Android device:
adb install build/app/outputs/flutter-apk/app-release.apk

# Test flow:
1. Open app
2. Browse products ✅
3. Register account ✅
4. Add to cart ✅
5. Checkout ✅
6. View order ✅
7. Send message to shop (test Pusher) ✅
```

---

## 🎉 You're Live!

### Production URLs:

```
🌐 Web API: https://qutecat.up.railway.app/api
🔒 Admin Panel: https://qutecat.up.railway.app/admin
📱 Mobile App: Connected to production API
```

### Default Credentials:

```
👤 Root Admin
   Email: root@qutekart.com
   Password: secret

👤 Demo Shop
   Email: shop@qutekart.com
   Password: secret
```

---

## 📊 Post-Launch Monitoring

### Day 1 Checklist:
- [ ] Monitor Railway logs for errors
- [ ] Check Stripe webhook deliveries
- [ ] Verify email sending (Resend dashboard)
- [ ] Test Pusher connections (Pusher dashboard)
- [ ] Check R2 storage usage (Cloudflare dashboard)
- [ ] Monitor database performance (Railway)
- [ ] Test subscription flows
- [ ] Verify queue worker processing jobs

### Week 1 Checklist:
- [ ] Set up custom domain (optional)
- [ ] Configure SSL certificate
- [ ] Set up monitoring (Railway metrics)
- [ ] Configure backup strategy
- [ ] Test disaster recovery
- [ ] Review error logs
- [ ] Optimize database queries
- [ ] Performance testing

---

## 🆘 Troubleshooting

### App won't deploy:
```bash
# Check logs:
railway logs

# Common issues:
- Missing env vars → Check all variables set
- Build failure → Verify nixpacks.toml syntax
- Migration failure → Check database connection
```

### Stripe not working:
```bash
# Verify webhook:
- Stripe Dashboard → Webhooks → Check delivery attempts
- Ensure URL is correct: /api/webhooks/stripe
- Check signing secret matches env var
```

### Pusher not connecting:
```bash
# Verify credentials:
- Check PUSHER_APP_KEY matches Flutter app
- Check PUSHER_APP_CLUSTER matches
- Test in Pusher Debug Console
```

### Emails not sending:
```bash
# Check Resend:
- Resend Dashboard → Logs
- Verify domain verified
- Check MAIL_FROM_ADDRESS is verified email
```

---

## 📝 Next Steps

### Optional Enhancements:
1. **Custom Domain** (1 hour)
   - Point domain to Railway
   - Configure SSL

2. **Google Play Store** (2-3 hours)
   - Create developer account ($25 one-time)
   - Generate signing key
   - Upload AAB bundle

3. **Apple App Store** (3-4 hours)
   - Enroll in Apple Developer Program ($99/year)
   - Build iOS app
   - Submit for review

4. **Performance Optimization** (2-3 hours)
   - Enable Redis caching
   - Configure CDN
   - Optimize images

---

**🎯 Total Deployment Time: 4-6 hours**  
**💰 Monthly Cost Estimate:** ~$50-75
- Railway: $5-10 (database + Redis)
- Cloudflare R2: $0-5 (storage)
- Resend: $0 (free tier)
- Pusher: $0 (free tier)
- Stripe: $0 (pay-as-you-go)
- Firebase: $0 (free tier)

**Congratulations! Your SaaS platform is now live! 🚀**
