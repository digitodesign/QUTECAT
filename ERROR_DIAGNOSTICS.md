# QUTECAT Error Diagnostics & Solutions

**Date:** November 18, 2025  
**Status:** Issues Identified & Partially Fixed

---

## 🔴 Critical Issues Found

### 1. **Missing API Routes - 404 Errors** ✅ FIXED

#### **Issue:** `/api/lang/en` returning 404
- **Root Cause:** No API route defined for language translations
- **Impact:** Frontend cannot load language files
- **Solution Applied:** Added route in `backend/install/routes/api.php` (lines 45-55)
```php
Route::get('/lang/{code}', function ($code) {
    $filePath = base_path("lang/{$code}.json");
    if (file_exists($filePath)) {
        $translations = json_decode(file_get_contents($filePath), true);
        return response()->json(['data' => $translations]);
    }
    return response()->json(['error' => 'Language file not found'], 404);
});
```

#### **Issue:** `/api/product-details` returning 404
- **Root Cause:** Frontend expects `/api/product-details?product_id=X` but only `/api/products/{id}` exists
- **Impact:** Product detail pages fail to load
- **Solution Applied:** Added alias route in `backend/install/routes/api.php` (line 50)
```php
Route::get('/product-details', [App\Http\Controllers\API\ProductController::class, 'show']);
```

---

### 2. **Pusher WebSocket Connection Failures** ✅ CONFIGURED (Investigating Connection Issues)

#### **Errors:**
```
WebSocket connection to 'wss://ws-*****.pusher.com/app/*****' failed
ERR_NAME_NOT_RESOLVED for sockjs-*****.pusher.com
```

#### **UPDATED STATUS:**
Pusher credentials **ARE CONFIGURED** in Railway (confirmed):
```env
PUSHER_APP_KEY=*****         # ✅ SET (redacted in API)
PUSHER_APP_SECRET=*****      # ✅ SET
PUSHER_APP_CLUSTER=*****     # ✅ SET (redacted in API)
```

#### **Root Cause:**
Connection issue is **NOT** due to missing credentials. Possible causes:
1. **Client-side network issue** - Firewall/proxy blocking Pusher domains
2. **Browser restrictions** - Extensions or security settings
3. **ISP blocking** - Some ISPs block WebSocket connections
4. **Pusher app configuration** - App might be paused or limited

#### **Impact:**
- Real-time chat notifications won't work
- Support ticket live updates won't work  
- Order status updates won't be real-time

#### **Solutions:**

**Option A: Configure Pusher (Recommended for Production)**
1. Go to https://dashboard.pusher.com
2. Create a new app or get credentials from existing app
3. Update `.env` file (in `backend/install/`) with:
```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1  # or your cluster (e.g., us2, eu, ap1)
```
4. Update in admin panel: `/admin/pusher` route
5. Clear config cache: `php artisan config:clear`

**Option B: Disable Pusher (For Development)**
If you don't need real-time features during development:
1. Edit `backend/install/resources/js/App.vue` line 73:
```javascript
if (!masterStore.pusher_app_key) {
    console.warn('Pusher not configured - real-time features disabled');
    return; // Exit early
}
```

**Option C: Use Laravel Echo with Broadcasting**
Switch to Laravel's built-in broadcasting with Redis:
```env
BROADCAST_DRIVER=redis
```

---

### 3. **API 500 Error on `/api/products`** ⚠️ NEEDS INVESTIGATION

#### **Error:**
```
api/products?page=1&per_page=12&sort_type=default&brand_id=&category_id=13
Status: 500 Internal Server Error
```

#### **Likely Causes:**
1. **Database connection issue** - Check if PostgreSQL is running
2. **Missing database tables** - Run migrations
3. **Query error** - Complex SQL with flash sales (lines 121-136 in ProductController)
4. **Missing category ID 13** - Category doesn't exist in database

#### **Debugging Steps:**

1. **Check database connection:**
```bash
cd c:\Users\WEBDRIPTECH\Desktop\QUTECAT\backend\install
php artisan tinker
>>> DB::connection()->getPdo();
```

2. **Check if migrations are run:**
```bash
php artisan migrate:status
```

3. **View Laravel logs:**
```bash
# Check the error in logs
type c:\Users\WEBDRIPTECH\Desktop\QUTECAT\backend\install\storage\logs\laravel.log
```

4. **Test the query directly:**
```bash
php artisan tinker
>>> \App\Models\Product::where('id', 1)->first();
>>> \App\Models\Category::find(13);
```

---

### 4. **Facebook SDK Timeout** ℹ️ LOW PRIORITY

#### **Error:**
```
connect.facebook.net/en_US/sdk.js - ERR_CONNECTION_TIMED_OUT
```

#### **Root Cause:**
Facebook SDK is being loaded but:
- No internet connection OR
- Facebook is blocked OR
- Facebook SDK not needed but still loading

#### **Solution:**
If Facebook login is not needed, remove from layout:
- Check `backend/install/resources/views/layouts/app.blade.php` for Facebook SDK references
- Or configure Facebook App credentials in admin panel

---

## ✅ Fixes Applied

| Issue | Status | File Modified |
|-------|--------|---------------|
| Missing `/api/lang/{code}` route | ✅ Fixed | `backend/install/routes/api.php` |
| Missing `/api/product-details` route | ✅ Fixed | `backend/install/routes/api.php` |

---

## 🔧 Next Steps

### Immediate Actions Required:

1. **Configure Pusher** (if you have credentials):
   ```bash
   cd c:\Users\WEBDRIPTECH\Desktop\QUTECAT\backend\install
   # Edit .env file and add Pusher credentials
   php artisan config:clear
   ```

2. **Fix 500 Error** - Check Laravel logs:
   ```bash
   cd c:\Users\WEBDRIPTECH\Desktop\QUTECAT\backend\install
   tail -f storage\logs\laravel.log
   # Then refresh the page to see the actual error
   ```

3. **Verify Database Connection**:
   ```bash
   php artisan migrate:status
   ```

4. **Test the fixes**:
   - Visit: `http://your-domain/api/lang/en` (should return JSON)
   - Visit: `http://your-domain/api/product-details?product_id=1`

---

## 📊 Application Architecture

**Stack:**
- **Backend:** Laravel 10.x (PHP)
- **Frontend:** Vue.js 3 + Inertia.js
- **Database:** PostgreSQL
- **Storage:** Cloudflare R2
- **Real-time:** Pusher (WebSockets)
- **Deployment:** Railway.app
- **Worker:** Cloudflare Workers

**Deployment Structure:**
- Main Laravel app deployed on Railway
- Static assets possibly on Cloudflare Workers
- File storage on Cloudflare R2

---

## 🔍 Codebase Insights

### Key Files Modified:
1. `backend/install/routes/api.php` - API route definitions
2. `backend/install/app/Http/Controllers/API/ProductController.php` - Product logic
3. `backend/install/app/Http/Controllers/API/MasterController.php` - Master data (includes Pusher config)

### Controllers Found:
- ProductController (index, show methods)
- MasterController (returns app config including Pusher credentials)
- LanguageController (admin-only, manages translations)

### API Routes Pattern:
- Public routes (no auth): `/api/home`, `/api/products`, `/api/categories`
- Auth routes: `/api/cart`, `/api/orders`, `/api/user`
- Seller routes: `/api/seller/*`
- Rider routes: `/api/rider/*`

---

## 📝 Environment Setup Checklist

- [ ] PostgreSQL database running
- [ ] Database migrations executed
- [ ] Pusher configured (or disabled)
- [ ] Cloudflare R2 configured  
- [ ] `.env` file properly set up
- [ ] File permissions correct (storage/, bootstrap/cache/)
- [ ] Laravel caches cleared

---

## 🆘 Support Commands

```bash
# Navigate to Laravel directory
cd c:\Users\WEBDRIPTECH\Desktop\QUTECAT\backend\install

# Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check environment
php artisan about

# Run migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed

# Check routes
php artisan route:list --path=api

# Check logs
tail -f storage\logs\laravel.log
```

---

**Created by:** Amp AI Assistant  
**Thread:** https://ampcode.com/threads/T-f4a99671-ae4d-40d1-b028-d314f2e7bb60
