# Queue Worker Deployment Fix

## Issue
Worker service (QUTECAT-WORKER) has no deployments because GitHub repository is not connected.

## Quick Fix (2 minutes)

### Option 1: Connect via Railway Dashboard (Recommended)

1. **Open Railway Dashboard**:
   ```
   https://railway.app/project/db8119e9-b2c6-425b-84b7-ec8a10064700
   ```

2. **Click on QUTECAT-WORKER service**

3. **Connect GitHub Repository**:
   - Click **Settings** tab
   - Under **Source** section, click **Connect Repo**
   - Select your QuteCart repository
   - Branch: `master`
   - Root Directory: Leave blank (will use root)

4. **Verify Start Command** (should auto-detect from nixpacks.toml):
   - The service will automatically use the conditional start command
   - PROCESS_TYPE=worker is already set in environment variables

5. **Deploy**:
   - Click **Deploy** or it will auto-deploy after connecting repo
   - Wait 2-3 minutes for build to complete

### Option 2: Deploy via Railway CLI

Since connecting repo via CLI is limited, use the dashboard method above.

## Verification

After deployment:

```bash
# Switch to worker service
railway service QUTECAT-WORKER

# Check deployment status
railway deployment list

# View logs
railway logs -n 50
```

**Expected logs**:
```
[2025-11-16] INFO: Processing: App\Jobs\ProcessImageOptimization
[2025-11-16] INFO: Processed: App\Jobs\ProcessImageOptimization
```

## If Deployment Still Crashes

### Check 1: Environment Variables
```bash
railway service QUTECAT-WORKER
railway variables
```

Ensure these are set:
- ✅ DB_HOST=postgres.railway.internal
- ✅ DB_PORT=5432
- ✅ REDIS_HOST=redis.railway.internal
- ✅ REDIS_PORT=6379
- ✅ PROCESS_TYPE=worker

### Check 2: Database Connection Test
```bash
railway run php artisan tinker
>>> DB::connection()->getPdo(); // Should return PDO object, not error
>>> Redis::connection()->ping(); // Should return "PONG"
```

### Check 3: Remove Migration from Worker Startup

The worker doesn't need to run migrations. If it's still crashing, we can simplify the start command:

**In Railway Dashboard → QUTECAT-WORKER → Settings → Start Command:**
```bash
cd backend/install && php artisan queue:work redis --sleep=3 --tries=3 --timeout=60 --memory=512 --verbose
```

This skips composer install (already done in build phase) and migrations (not needed for worker).

## Alternative: Simplified Worker Configuration

If the conditional nixpacks.toml is causing issues, create a separate configuration:

1. **In Railway Dashboard → QUTECAT-WORKER → Settings**
2. **Custom Start Command**:
   ```bash
   cd backend/install && composer install --no-dev --optimize-autoloader && php artisan config:clear && php artisan queue:work redis --sleep=3 --tries=3 --timeout=60
   ```

3. **Skip Build Command**: Leave blank or use:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

## Current Status

✅ **QUTECAT (Web)**: Running successfully  
✅ **Environment Variables**: All set for QUTECAT-WORKER  
✅ **Redis & PostgreSQL**: Connected and accessible  
✅ **R2 Credentials**: Configured  
⏳ **QUTECAT-WORKER**: Needs GitHub repo connection  

## Next Step

**Connect GitHub repo to QUTECAT-WORKER in Railway Dashboard** - this is the only missing piece!

URL: https://railway.app/project/db8119e9-b2c6-425b-84b7-ec8a10064700

Once connected, the worker will:
1. Auto-build from your master branch
2. Detect PROCESS_TYPE=worker environment variable
3. Run the queue worker command from nixpacks.toml
4. Start processing image optimization jobs

---

**Timeline**: 2 minutes to connect + 3 minutes to build = **5 minutes total**
