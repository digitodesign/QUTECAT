# Flutter App - Production Configuration Checklist

**Quick Reference:** What needs to be configured before building production APK

**Date:** 2025-11-06

---

## ✅ Configuration Checklist

### 1. Backend API URL

**File:** `lib/config/app_constants.dart`

**Line 3:**

```dart
static const String baseUrl = 'https://YOUR_DOMAIN.com/api';
```

**How to find your domain:**
```bash
# SSH to production server
ssh user@yourserver.com

# Check Laravel .env
cat /path/to/laravel/.env | grep APP_URL
# Output: APP_URL=https://qutecat.com

# Your API URL = APP_URL + /api
# Example: https://qutecat.com/api
```

**Test it works:**
```bash
curl https://YOUR_DOMAIN.com/api/master
# Should return JSON with app settings
```

- [ ] `baseUrl` updated in `app_constants.dart`
- [ ] API URL tested and accessible
- [ ] Using HTTPS (not HTTP)

---

### 2. Firebase (Push Notifications)

**Android File:** `android/app/google-services.json`
**iOS File:** `ios/Runner/GoogleService-Info.plist`

**Steps:**

1. **Create Firebase Project:**
   - Go to: https://console.firebase.google.com/
   - Click "Add project"
   - Name: `QuteCart` (or your brand)

2. **Add Android App:**
   - Click Android icon
   - Package: `com.readyecommerce.apps` (or your custom package)
   - Download `google-services.json`
   - Place in: `android/app/google-services.json`

3. **Add iOS App (optional):**
   - Click iOS icon
   - Bundle ID: `com.readyecommerce.apps`
   - Download `GoogleService-Info.plist`
   - Place in: `ios/Runner/GoogleService-Info.plist`

4. **Get Server Key:**
   - Firebase Console → Settings → Cloud Messaging
   - Copy "Server key"
   - Add to Laravel `.env`:
     ```bash
     FIREBASE_SERVER_KEY=YOUR_SERVER_KEY
     ```

**Checklist:**
- [ ] Firebase project created
- [ ] `google-services.json` downloaded
- [ ] `google-services.json` placed in `android/app/` directory
- [ ] `GoogleService-Info.plist` downloaded (if iOS)
- [ ] `GoogleService-Info.plist` placed in `ios/Runner/` directory
- [ ] Server key added to Laravel `.env`
- [ ] Test notification sent from Firebase Console

**Test:**
```bash
# Send test notification from Firebase Console
# Navigate to: Cloud Messaging → Send your first message
# Should receive notification on device within 30 seconds
```

---

### 3. Pusher (Real-time Messaging)

**File:** `lib/config/app_constants.dart`

**Lines 114-115:**

```dart
static String pusherApiKey = 'YOUR_PUSHER_KEY';
static String pusherCluster = 'mt1';
```

**How to get Pusher credentials:**

**Option A: From Laravel .env**
```bash
# SSH to production server
cat /path/to/laravel/.env | grep PUSHER

# Output example:
# PUSHER_APP_KEY=a3cbadc04a202a7746fc
# PUSHER_APP_CLUSTER=mt1
```

**Option B: Create Pusher Account**
1. Go to: https://dashboard.pusher.com/
2. Create app
3. Copy credentials from "App Keys" tab

**Checklist:**
- [ ] Pusher credentials obtained
- [ ] `pusherApiKey` updated in `app_constants.dart`
- [ ] `pusherCluster` updated in `app_constants.dart`
- [ ] Credentials match Laravel `.env` exactly
- [ ] Laravel `BROADCAST_DRIVER=pusher` in `.env`
- [ ] Laravel queue worker running: `php artisan queue:work`
- [ ] Test message sent and received instantly

**Test:**
```bash
# In app: Send message to shop
# In Laravel admin: Reply
# Message should appear in app within 1-2 seconds
```

---

### 4. App Branding

#### App Name

**Android:** `android/app/src/main/AndroidManifest.xml`
```xml
<application android:label="QuteCart" ...>
```

**iOS:** `ios/Runner/Info.plist`
```xml
<key>CFBundleName</key>
<string>QuteCart</string>
```

- [ ] App name changed in `AndroidManifest.xml`
- [ ] App name changed in `Info.plist` (if iOS)

#### App Colors (ZARA Style)

**File:** `lib/config/app_color.dart`

**Line 89:**
```dart
static Color primary = const Color(0xFF000000);  // ZARA Black
```

- [ ] Primary color updated to `#000000` (black)
- [ ] Other colors updated (see ZARA guide)

#### App Icon

**Replace icons in:**
- `android/app/src/main/res/mipmap-*/ic_launcher.png`
- `ios/Runner/Assets.xcassets/AppIcon.appiconset/`

**Or use automated tool:**
```bash
flutter pub add flutter_launcher_icons
# Add config to pubspec.yaml
flutter pub run flutter_launcher_icons
```

- [ ] App icon created (1024x1024 PNG)
- [ ] Icons generated and replaced
- [ ] Icons tested on device

#### Splash Screen Logo

**Files:**
- `assets/png/logo.png`
- `assets/svg/logo.svg`

- [ ] Logo replaced with brand logo
- [ ] Splash screen tested

---

### 5. Package Name (Optional but Recommended)

**Current:** `com.readyecommerce.apps`

**Change to your brand:**
```bash
flutter pub run change_app_package_name:main com.qutecat.app
```

**⚠️ If you change package name:**
- [ ] Update Firebase with new package
- [ ] Re-download `google-services.json`
- [ ] Re-download `GoogleService-Info.plist`
- [ ] Update package in all manifests

---

## 🧪 Testing Before Release

### Build Test

```bash
cd FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode

# Clean build
flutter clean
flutter pub get

# Test debug build
flutter run

# Build release APK
flutter build apk --release

# Check APK size (should be 20-40 MB)
ls -lh build/app/outputs/flutter-apk/app-release.apk
```

- [ ] Debug build successful
- [ ] Release build successful
- [ ] APK size reasonable (< 50 MB)

### Functional Test

**Authentication:**
- [ ] Register new account
- [ ] Receive OTP
- [ ] Login successful
- [ ] Logout works

**Product Browsing:**
- [ ] Home page loads
- [ ] Products display
- [ ] Product images load
- [ ] Product videos play
- [ ] Categories work
- [ ] Search works

**Shopping:**
- [ ] Add to cart
- [ ] Checkout loads
- [ ] Address selection
- [ ] Payment methods appear
- [ ] Place order works

**Real-time:**
- [ ] Send message to shop
- [ ] Receive reply instantly (< 2 seconds)
- [ ] Push notification arrives
- [ ] Notification opens app correctly

**Offline:**
- [ ] Cart persists after app close
- [ ] Cached images display

---

## 📋 Quick Reference

### Configuration Files Summary

| What | File | What to Change |
|------|------|----------------|
| API URL | `lib/config/app_constants.dart` | Line 3: `baseUrl` |
| Pusher | `lib/config/app_constants.dart` | Lines 114-115: `pusherApiKey`, `pusherCluster` |
| Firebase Android | `android/app/google-services.json` | Download from Firebase Console |
| Firebase iOS | `ios/Runner/GoogleService-Info.plist` | Download from Firebase Console |
| App Name Android | `android/app/src/main/AndroidManifest.xml` | `android:label` |
| App Name iOS | `ios/Runner/Info.plist` | `CFBundleName` |
| Colors | `lib/config/app_color.dart` | Line 89: `primary` color |
| App Icon | Various icon files | Use flutter_launcher_icons |

### Commands Summary

```bash
# Get dependencies
flutter pub get

# Test build
flutter run

# Build release APK
flutter build apk --release

# Build App Bundle (for Play Store)
flutter build appbundle --release

# Change package name
flutter pub run change_app_package_name:main com.YOUR.PACKAGE

# Generate app icons
flutter pub run flutter_launcher_icons

# Clean build
flutter clean

# Check for issues
flutter analyze
```

### URLs to Visit

| Service | URL | Purpose |
|---------|-----|---------|
| Firebase Console | https://console.firebase.google.com/ | Push notifications setup |
| Pusher Dashboard | https://dashboard.pusher.com/ | Real-time messaging credentials |
| App Icon Generator | https://appicon.co/ | Generate app icons |
| Play Store Console | https://play.google.com/console | Android app deployment |
| App Store Connect | https://appstoreconnect.apple.com | iOS app deployment |

---

## 🚨 Common Mistakes to Avoid

### ❌ Wrong API URL Format

```dart
// ❌ Wrong - missing /api
static const String baseUrl = 'https://qutecat.com';

// ✅ Correct
static const String baseUrl = 'https://qutecat.com/api';
```

### ❌ Firebase File in Wrong Location

```bash
# ❌ Wrong
android/google-services.json

# ✅ Correct
android/app/google-services.json
```

### ❌ Pusher Credentials Don't Match

```bash
# Laravel .env
PUSHER_APP_KEY=abc123

# Flutter app_constants.dart
pusherApiKey = 'xyz789'  # ❌ Wrong - must match!
```

### ❌ Using HTTP Instead of HTTPS

```dart
// ❌ Wrong - not secure
static const String baseUrl = 'http://qutecat.com/api';

// ✅ Correct - secure
static const String baseUrl = 'https://qutecat.com/api';
```

### ❌ Forgetting to Update Firebase After Package Change

If you change package name from `com.readyecommerce.apps` to `com.qutecat.app`:
1. Update Firebase Console with new package
2. Re-download `google-services.json`
3. Replace old file

---

## 📞 Getting Help

### Where to Check Logs

**Flutter App Logs:**
```bash
# Android
adb logcat | grep Flutter

# iOS (in Xcode)
# Window → Devices and Simulators → View Device Logs
```

**Laravel API Logs:**
```bash
# On production server
tail -f /path/to/laravel/storage/logs/laravel.log
```

**Pusher Debug Console:**
- Go to: https://dashboard.pusher.com/
- Select your app
- Click "Debug Console"
- Watch for events in real-time

### Test Endpoints Manually

```bash
# Test API connection
curl https://YOUR_DOMAIN.com/api/master

# Test login
curl -X POST https://YOUR_DOMAIN.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Test authenticated endpoint
curl https://YOUR_DOMAIN.com/api/profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## ✅ Final Checklist Before Deployment

- [ ] All configuration values updated (no TODOs remaining)
- [ ] API connection tested and working
- [ ] Firebase push notifications tested
- [ ] Pusher real-time messaging tested
- [ ] App name and branding updated
- [ ] App icon replaced
- [ ] Colors updated (ZARA style or custom)
- [ ] Debug build successful
- [ ] Release build successful
- [ ] Tested on real device (not just emulator)
- [ ] All critical features work (auth, shopping, messaging)
- [ ] SSL certificate valid on production server
- [ ] Laravel queue worker running on server
- [ ] No console errors or warnings
- [ ] APK size reasonable (< 50 MB)

**When all checked ✅, you're ready to deploy!** 🚀

---

**Document Version:** 1.0
**Last Updated:** 2025-11-06
