# R2 API Token Setup Guide

## Your R2 Configuration (Ready to Use)

Based on your Wrangler access, here are your R2 configuration details:

### ✅ Bucket Configuration (Complete)
- **Account ID**: `d22237c467b01861fb0620336ff21f6e`
- **Production Bucket**: `qutecat-production` (enabled with public access)
- **Private Bucket**: `qutecat-private`
- **Public URL**: `https://pub-3d92172d800e48d4a3a7fa78cae3fb00.r2.dev`
- **R2 Endpoint**: `https://d22237c467b01861fb0620336ff21f6e.r2.cloudflarestorage.com`

### 🔐 Generate API Token (5 minutes)

1. **Go to Cloudflare Dashboard**: https://dash.cloudflare.com
2. **Navigate to R2** → Click **"Manage R2 API Tokens"**
3. **Create New Token**:
   - **Token Name**: `QuteCart Production`
   - **Permissions**: 
     - ✅ Object Read & Write
     - ✅ Account level permissions
   - **Account Resources**: 
     - ✅ Include `All accounts` or select your account
   - **Zone Resources**: Skip
   - **Bucket Permissions**:
     - ✅ Include specific buckets
     - Select: `qutecat-production`, `qutecat-private`
   - **TTL**: Never expire (recommended)

4. **Copy Your Credentials**:
   ```
   Access Key ID: (copy this)
   Secret Access Key: (copy this)
   ```

### 🔧 Update Railway Environment Variables

```bash
# Set R2 credentials
railway variables set R2_ACCESS_KEY_ID="your_access_key_from_above"
railway variables set R2_SECRET_ACCESS_KEY="your_secret_key_from_above"

# Already configured (but verify these are set):
railway variables set R2_BUCKET="qutecat-production"
railway variables set R2_PRIVATE_BUCKET="qutecat-private"
railway variables set R2_ENDPOINT="https://d22237c467b01861fb0620336ff21f6e.r2.cloudflarestorage.com"
railway variables set R2_PUBLIC_URL="https://pub-3d92172d800e48d4a3a7fa78cae3fb00.r2.dev"
railway variables set FILESYSTEM_DISK="r2"
```

### 🧪 Test R2 Integration

After setting up tokens:

1. **Deploy to Railway**: `git push origin master`
2. **Run Migration**: `railway run php artisan migrate --force`
3. **Test Upload**: 
   - Login to admin: https://qutecat.up.railway.app/admin
   - Create product with image
4. **Verify in R2**:
   ```bash
   wrangler r2 object list qutecat-production
   ```

### 📁 File Structure in R2

Expected structure:
```
qutecat-production/
├── products/
│   ├── shop-1/
│   │   ├── uuid123.jpg           (original)
│   │   ├── uuid123.webp          (optimized)
│   │   └── uuid123-thumbnail.webp
│   └── shop-2/
│       └── uuid456.jpg
├── logos/
│   └── shop-1/
└── documents/
    └── shop-1/
```

### 🚀 Next Steps

1. **Create API Token** (Step above)
2. **Update Railway Variables** (Command above)
3. **Deploy & Test** (Git push + upload test)
4. **Monitor Queue Workers** (Access: https://qutecat.up.railway.app/horizon)

### 💡 Cost Estimate

- **R2 Storage**: ~$0.015/GB/month
- **R2 Operations**: ~$4.50/M Class A, $0.36/M Class B
- **Monthly Total**: ~$5-15 for typical e-commerce usage

### ❓ Questions?

Check: `R2_INTEGRATION_GUIDE.md` for detailed implementation info

## Status
✅ Buckets created and configured
⏳ Waiting for API token creation
🔄 Ready for deployment once tokens are set
