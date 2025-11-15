# 🚀 R2 Integration - Quick Deployment Checklist

## ✅ FILES CREATED (ALL DONE)

- [x] `app/Services/Storage/PresignedUrlService.php`
- [x] `app/Jobs/ProcessImageOptimization.php`
- [x] `app/Jobs/ProcessVideoThumbnail.php`
- [x] `database/migrations/2025_11_15_232506_enhance_media_table_for_r2_storage.php`
- [x] `app/Models/Media.php` (updated)
- [x] `app/Repositories/MediaRepository.php` (updated)
- [x] `config/filesystems.php` (updated)
- [x] R2 Buckets: `qutecat-production`, `qutecat-private`

---

## 📋 DEPLOYMENT STEPS (DO THESE NOW)

### **1. Get Cloudflare R2 Credentials** (5 minutes)

```bash
# Go to: https://dash.cloudflare.com
# R2 → Manage R2 API Tokens → Create API Token
# Permissions: Object Read & Write
# Copy: Access Key ID, Secret Access Key, Account ID
```

### **2. Set Railway Environment Variables** (2 minutes)

```bash
# Replace with YOUR actual values:
railway variables set R2_ACCESS_KEY_ID="YOUR_ACCESS_KEY"
railway variables set R2_SECRET_ACCESS_KEY="YOUR_SECRET_KEY"
railway variables set R2_BUCKET="qutecat-production"
railway variables set R2_PRIVATE_BUCKET="qutecat-private"
railway variables set R2_ENDPOINT="https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com"
railway variables set R2_PUBLIC_URL="https://pub-xxxxx.r2.dev"
railway variables set FILESYSTEM_DISK="r2"
```

### **3. Install Laravel Horizon** (3 minutes)

```bash
cd backend/install
composer require laravel/horizon
php artisan horizon:install
php artisan horizon:publish
git add .
git commit -m "Add Laravel Horizon for queue management"
git push
```

### **4. Deploy to Railway** (2 minutes)

```bash
# Option A: Auto-deploy (if connected to GitHub)
git push origin master
# Railway will auto-deploy

# Option B: Manual deploy
railway up
```

### **5. Run Migration** (1 minute)

```bash
railway run php artisan migrate --force
```

### **6. Create Queue Worker Service** (5 minutes)

**Via Railway Dashboard:**
1. Go to https://railway.app/project/qutekart
2. Click "+ New Service"
3. Connect to same GitHub repo
4. Service name: `qutecat-queue-worker`
5. Root directory: `backend/install`
6. Start command: `php artisan horizon`
7. Deploy

### **7. Test Upload** (2 minutes)

1. Go to https://qutecat.up.railway.app/admin
2. Products → Create Product → Upload Image
3. Check Horizon: https://qutecat.up.railway.app/horizon
4. Verify job processing

### **8. Verify R2 Storage** (1 minute)

```bash
wrangler r2 object list --bucket qutecat-production
# Should show uploaded files
```

---

## ⏱️ **TOTAL TIME: ~20 MINUTES**

---

## 🎯 **EXPECTED RESULTS**

After deployment:
- ✅ Product images upload to R2
- ✅ Automatic WebP conversion
- ✅ 4 responsive sizes generated (thumbnail, small, medium, large)
- ✅ Global CDN delivery via Cloudflare
- ✅ Queue processing visible in Horizon
- ✅ Storage usage tracked per shop

---

## 🆘 **IF SOMETHING BREAKS**

```bash
# Check logs
railway logs --service=QUTECAT

# Check queue worker
railway logs --service=qutecat-queue-worker

# Check failed jobs
railway run php artisan queue:failed

# Restart services
railway restart
```

---

## 📞 **NEXT STEPS AFTER DEPLOYMENT**

1. Update Flutter app API endpoint (already noted)
2. Configure custom R2 domain (optional)
3. Set up automated backups
4. Monitor storage usage dashboard

---

**Ready to deploy?** Follow steps 1-8 above!

**Questions?** Check `R2_INTEGRATION_GUIDE.md` for detailed instructions.
