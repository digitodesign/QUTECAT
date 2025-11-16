# Queue Worker Service Configuration

## Service Created ✅
Service Name: **QUTECAT-WORKER**
Status: Created but needs configuration

## Required Configuration Steps

### 1. Link GitHub Repository to Worker Service

**Via Railway Dashboard:**
1. Go to: https://railway.app/project/db8119e9-b2c6-425b-84b7-ec8a10064700
2. Click on **QUTECAT-WORKER** service
3. Click **Settings** tab
4. Under **Source**, click **Connect Repo**
5. Select your GitHub repository (same as QUTECAT service)
6. Set **Root Directory**: `backend/install`
7. Set **Branch**: `master`

### 2. Configure Start Command

**In Service Settings:**
- **Start Command**: `php artisan queue:work redis --sleep=3 --tries=3 --timeout=60 --memory=512`

This will:
- Process jobs from Redis queue
- Sleep 3 seconds between job checks
- Retry failed jobs 3 times
- Timeout long-running jobs after 60 seconds
- Restart if memory exceeds 512MB

### 3. Share Environment Variables

**Copy ALL variables from QUTECAT service:**

The worker needs access to the same environment variables:
- Database credentials (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
- Redis credentials (REDIS_HOST, REDIS_PORT, REDIS_PASSWORD)
- R2 storage credentials (R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET, R2_ENDPOINT, R2_PUBLIC_URL)
- App configuration (APP_KEY, APP_ENV, APP_URL)

**Quick Method:**
1. In Railway Dashboard, go to QUTECAT service
2. Click **Variables** tab
3. Copy all variables
4. Go to QUTECAT-WORKER service
5. Click **Variables** tab
6. Add variables in batch (or use Reference variables from QUTECAT)

**Better Method (Shared Variables):**
Use Railway's shared variables feature:
1. Go to Project Settings → Shared Variables
2. Add all shared variables there
3. Both services will automatically use them

### 4. Configure Build Settings

**In Service Settings → Build:**
- **Builder**: Nixpacks (default)
- **Watch Paths**: Leave empty or set to `backend/**`

### 5. Resource Allocation

**Recommended for Queue Worker:**
- **Memory**: 512 MB - 1 GB
- **CPU**: 0.5 - 1 vCPU
- **Instances**: 1 (scale up if needed)

### 6. Health Checks (Optional but Recommended)

Add a health check command:
```bash
php artisan queue:work redis --once
```

This ensures the worker can connect to Redis before starting.

## Alternative: CLI Configuration (if available)

If you have Railway CLI with full access, you can set variables:

```bash
# Link to worker service
railway service QUTECAT-WORKER

# Set variables (reference from main service)
railway variables --copy-from QUTECAT

# Deploy
railway up
```

## Verification Steps

After configuration:

### 1. Check Worker Logs
```bash
railway service QUTECAT-WORKER
railway logs -n 50
```

You should see:
```
Processing: App\Jobs\ProcessImageOptimization
Processed:  App\Jobs\ProcessImageOptimization
```

### 2. Test Image Upload
1. Upload an image through admin panel
2. Check worker logs for processing
3. Verify WebP conversion in R2 bucket:
```bash
wrangler r2 object list qutecat-production --limit 10
```

### 3. Monitor Queue Status
```bash
railway run php artisan queue:work --once
```

This runs a single job to test connectivity.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                     Railway Project                      │
│                                                          │
│  ┌──────────────┐        ┌──────────────────────┐      │
│  │   QUTECAT    │        │  QUTECAT-WORKER      │      │
│  │   (Web)      │        │  (Queue Worker)      │      │
│  │              │        │                      │      │
│  │ php -S ...   │        │ php artisan          │      │
│  │ server.php   │        │ queue:work redis     │      │
│  └──────┬───────┘        └──────┬───────────────┘      │
│         │                       │                       │
│         │                       │                       │
│         ├───────────┬───────────┤                       │
│         │           │           │                       │
│    ┌────▼───┐  ┌───▼────┐ ┌───▼────┐                  │
│    │ Redis  │  │ Postgres│ │   R2   │                  │
│    │ Queue  │  │   DB    │ │ Storage│                  │
│    └────────┘  └─────────┘ └────────┘                  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## What Jobs Will Be Processed?

### 1. ProcessImageOptimization
**Triggered**: When images are uploaded (JPG, JPEG, PNG)
**Actions**:
- Converts to WebP format
- Generates responsive sizes (thumbnail, small, medium, large)
- Uploads optimized versions to R2
- Updates media table with optimized URLs

**File**: `app/Jobs/ProcessImageOptimization.php`

### 2. ProcessVideoThumbnail (if implemented)
**Triggered**: When videos are uploaded
**Actions**:
- Extracts video thumbnail
- Uploads thumbnail to R2

## Troubleshooting

### Worker Not Processing Jobs

1. **Check Redis Connection**:
```bash
railway run php artisan tinker
>>> Redis::connection()->ping();
```

2. **Check Queue Configuration**:
```bash
railway run php artisan config:show queue
```

3. **Manually Process One Job**:
```bash
railway run php artisan queue:work redis --once
```

### Jobs Failing

1. **Check Failed Jobs Table**:
```bash
railway run php artisan queue:failed
```

2. **View Failed Job Details**:
```bash
railway run php artisan queue:failed --json
```

3. **Retry Failed Jobs**:
```bash
railway run php artisan queue:retry all
```

### High Memory Usage

Adjust worker configuration:
```bash
php artisan queue:work redis --memory=256 --timeout=30
```

## Production Best Practices

1. **Use Horizon** (recommended for better monitoring):
   - Install: Already included in project
   - Start command: `php artisan horizon`
   - Dashboard: `https://qutecat.up.railway.app/horizon`

2. **Set up Monitoring**:
   - Configure Laravel Horizon for queue monitoring
   - Set up alerts for failed jobs
   - Monitor worker memory usage

3. **Scale Workers**:
   - Start with 1 worker
   - Scale up based on queue size
   - Railway allows horizontal scaling

4. **Job Priorities**:
   - Use different queues for different priorities
   - Example: `ProcessImageOptimization::dispatch($media)->onQueue('images')`

## Cost Estimate

**QUTECAT-WORKER Service:**
- Memory: 512 MB
- CPU: 0.5 vCPU
- Estimated: ~$3-5/month (Railway Hobby plan)

**Note**: The worker only consumes resources when active, so cost depends on upload volume.

## Next Steps

1. ✅ Service created: QUTECAT-WORKER
2. ⏳ Configure GitHub repository connection
3. ⏳ Set start command
4. ⏳ Copy/share environment variables
5. ⏳ Deploy and verify logs
6. ⏳ Test image upload and optimization

---

**Project ID**: db8119e9-b2c6-425b-ec8a10064700  
**Environment**: production  
**Services**: QUTECAT (web), QUTECAT-WORKER (queue)
