# Flutter App - Production Configuration Guide

**Status:** Step-by-Step Production Setup

**Date:** 2025-11-06

**Purpose:** Configure Flutter mobile app for production deployment with your Laravel backend

---

## Overview

This guide walks you through configuring the Flutter app to connect to your production Laravel backend server instead of the demo server.

**What we'll configure:**
1. ✅ Backend API URL (Laravel server)
2. ✅ Firebase Cloud Messaging (Push notifications)
3. ✅ Pusher Channels (Real-time chat)
4. ✅ App branding (Name, colors, icons)

**Time Required:** ~30 minutes

---

## Prerequisites

Before starting, ensure you have:

- [ ] Laravel backend deployed and accessible via HTTPS (e.g., `https://qutecat.com`)
- [ ] Access to Laravel `.env` file on production server
- [ ] Google account for Firebase Console
- [ ] Pusher account (or credentials from backend `.env`)
- [ ] Flutter SDK installed and working

---

## Part 1: Backend API Configuration

### Step 1: Identify Your Production API URL

Your Laravel backend API URL should be your domain + `/api`:

**Examples:**
- If your site is `https://qutecat.com` → API URL: `https://qutecat.com/api`
- If your site is `https://api.qutecat.com` → API URL: `https://api.qutecat.com/api`
- If subfolder: `https://yoursite.com/ecommerce` → API URL: `https://yoursite.com/ecommerce/api`

**Find it in your Laravel backend:**

```bash
# SSH into your production server
ssh user@yourserver.com

# Check Laravel .env
cd /path/to/laravel
cat .env | grep APP_URL

# Output example:
# APP_URL=https://qutecat.com
```

Your API base URL = `APP_URL + /api`

### Step 2: Update Flutter App Constants

**File to edit:** `FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/lib/config/app_constants.dart`

**Line 3:**

```dart
// ❌ BEFORE (Demo server)
static const String baseUrl = 'https://demo.readyecommerce.app/api';

// ✅ AFTER (Your production server)
static const String baseUrl = 'https://qutecat.com/api';
//                              👆 Replace with YOUR domain
```

**Full change:**

```dart
class AppConstants {
  // 🔧 CHANGE THIS - Your production API URL
  static const String baseUrl = 'https://YOUR_DOMAIN.com/api';

  // All other endpoints remain the same (they use $baseUrl)
  static const String settings = '$baseUrl/master';
  static const String loginUrl = '$baseUrl/login';
  static const String registrationUrl = '$baseUrl/registration';
  // ... etc
}
```

### Step 3: Test Backend Connection

**Create a test file to verify API is accessible:**

```dart
// Test API connection
import 'package:dio/dio.dart';

Future<void> testApiConnection() async {
  final dio = Dio();

  try {
    final response = await dio.get('https://YOUR_DOMAIN.com/api/master');
    print('✅ API Connected: ${response.statusCode}');
    print('Data: ${response.data}');
  } catch (e) {
    print('❌ API Connection Failed: $e');
  }
}
```

**Run test:**

```bash
# In Flutter project root
flutter run
# Then call testApiConnection() from your code
```

**Expected response:** JSON with app settings (logo, colors, etc.)

---

## Part 2: Firebase Configuration

### Why Firebase?

Firebase Cloud Messaging (FCM) provides:
- Push notifications when order status changes
- New message alerts from shops
- Flash sale notifications
- Marketing campaigns

### Step 1: Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project" or select existing project
3. Enter project name: `QuteCart` (or your brand name)
4. Enable Google Analytics: **Yes** (recommended)
5. Select or create Analytics account
6. Click "Create project"
7. Wait for project setup (30-60 seconds)

### Step 2: Add Android App

**In Firebase Console:**

1. Click Android icon (robot)
2. Fill in app details:

```
Android package name: com.readyecommerce.apps
                      👆 (or your custom package if changed)

App nickname (optional): QuteCart Android

Debug signing certificate SHA-1 (optional): Leave blank for now
```

3. Click "Register app"

4. **Download config file:**
   - Click "Download google-services.json"
   - Save file to your computer

5. **Place config file in Flutter project:**

```bash
# Copy google-services.json to Android app directory
cp ~/Downloads/google-services.json \
   FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/android/app/google-services.json
```

6. Verify file location:

```bash
# Should exist at this exact path:
FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/
  └── android/
      └── app/
          └── google-services.json  ✅
```

7. Click "Next" → "Next" → "Continue to console"

### Step 3: Add iOS App (Optional - if supporting iOS)

**In Firebase Console:**

1. Click iOS icon (apple)
2. Fill in app details:

```
iOS bundle ID: com.readyecommerce.apps
               👆 (must match package name)

App nickname (optional): QuteCart iOS

App Store ID (optional): Leave blank until published
```

3. Click "Register app"

4. **Download config file:**
   - Click "Download GoogleService-Info.plist"
   - Save file to your computer

5. **Place config file in Flutter project:**

```bash
# Copy GoogleService-Info.plist to iOS runner directory
cp ~/Downloads/GoogleService-Info.plist \
   FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/ios/Runner/GoogleService-Info.plist
```

6. Verify file location:

```bash
# Should exist at this exact path:
FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/
  └── ios/
      └── Runner/
          └── GoogleService-Info.plist  ✅
```

7. Click "Next" → "Next" → "Continue to console"

### Step 4: Enable Cloud Messaging

**In Firebase Console:**

1. Click ⚙️ (Settings gear) → "Project settings"
2. Click "Cloud Messaging" tab
3. Scroll to "Cloud Messaging API (Legacy)"
4. If disabled, click "Enable"
5. Copy "Server key" - you'll need this for Laravel backend

**Example Server Key:**
```
AAAA1234567890:APA91bF...very_long_string...xyz
```

### Step 5: Update Laravel Backend with Firebase Credentials

**SSH to your production server:**

```bash
ssh user@yourserver.com
cd /path/to/laravel
nano .env
```

**Add Firebase credentials:**

```bash
# Firebase Configuration (for push notifications)
FIREBASE_SERVER_KEY=YOUR_SERVER_KEY_FROM_FIREBASE_CONSOLE
FIREBASE_CREDENTIALS=/path/to/firebase-credentials.json
```

**Save and restart Laravel:**

```bash
# Restart queue workers to pick up new config
php artisan queue:restart

# Restart web server
sudo systemctl restart nginx  # or apache2
```

### Step 6: Test Push Notifications

**Send test notification from Firebase Console:**

1. Go to Firebase Console → "Cloud Messaging"
2. Click "Send your first message"
3. Enter notification:
   - Title: "Test Notification"
   - Text: "Push notifications are working!"
4. Click "Next"
5. Target: Select "User segment" → "All users"
6. Click "Next" → "Review" → "Publish"
7. Install and open app on test device
8. You should receive notification within 30 seconds

---

## Part 3: Pusher Configuration

### Why Pusher?

Pusher Channels provides:
- Real-time chat between customers and shops
- Instant message delivery
- Typing indicators (if implemented)
- Online status (if implemented)

### Step 1: Get Pusher Credentials from Laravel Backend

**Option A: From Laravel .env file**

```bash
# SSH to production server
ssh user@yourserver.com
cd /path/to/laravel

# View Pusher credentials
cat .env | grep PUSHER

# Output example:
# PUSHER_APP_ID=1234567
# PUSHER_APP_KEY=a3cbadc04a202a7746fc
# PUSHER_APP_SECRET=abc123def456
# PUSHER_APP_CLUSTER=mt1
```

**Option B: Create new Pusher account (if not set up)**

1. Go to [Pusher Dashboard](https://dashboard.pusher.com/)
2. Sign up or log in
3. Click "Create app"
4. Fill in details:
   - Name: `QuteCart`
   - Cluster: `us-east-1` (or closest to your users)
   - Frontend: `Flutter`
   - Backend: `Laravel`
5. Click "Create app"
6. Copy credentials from "App Keys" tab:
   - app_id
   - key
   - secret
   - cluster

### Step 2: Update Laravel Backend

**Edit Laravel .env:**

```bash
# Pusher Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=1234567
PUSHER_APP_KEY=a3cbadc04a202a7746fc
PUSHER_APP_SECRET=abc123def456
PUSHER_APP_CLUSTER=mt1
```

**Restart Laravel:**

```bash
php artisan config:clear
php artisan queue:restart
```

### Step 3: Update Flutter App

**File to edit:** `lib/config/app_constants.dart`

**Lines 114-115:**

```dart
// ❌ BEFORE (Demo credentials)
static String pusherApiKey = 'a3cbadc04a202a7746fc';
static String pusherCluster = 'mt1';

// ✅ AFTER (Your production credentials)
static String pusherApiKey = 'YOUR_PUSHER_KEY';  // From Laravel .env
static String pusherCluster = 'YOUR_CLUSTER';    // From Laravel .env (e.g., 'us2', 'eu', 'ap1')
```

**Full configuration:**

```dart
class AppConstants {
  // ... other constants

  // 🔧 CHANGE THIS - Your Pusher credentials (from Laravel .env)
  static String pusherApiKey = 'a3cbadc04a202a7746fc';  // PUSHER_APP_KEY
  static String pusherCluster = 'mt1';                   // PUSHER_APP_CLUSTER
}
```

### Step 4: Test Real-time Messaging

**Test chat functionality:**

1. Install app on test device
2. Login as customer
3. Go to a shop's page
4. Click "Message" or "Chat"
5. Send a message
6. Check if message appears in Laravel admin panel
7. Reply from admin panel
8. Message should appear in app instantly (within 1-2 seconds)

**Troubleshooting:**

If messages don't appear instantly:
- Check Pusher credentials match in Laravel .env and Flutter app
- Verify BROADCAST_DRIVER=pusher in Laravel .env
- Check Laravel queue worker is running: `ps aux | grep queue:work`
- Check Pusher dashboard for event activity

---

## Part 4: App Branding Configuration

### Step 1: Update App Name

**Android:**

**File:** `android/app/src/main/AndroidManifest.xml`

```xml
<!-- Line 6 -->
<application
    android:label="QuteCart"
    👆 Change this to your brand name
```

**iOS:**

**File:** `ios/Runner/Info.plist`

```xml
<key>CFBundleName</key>
<string>QuteCart</string>
         👆 Change this to your brand name

<key>CFBundleDisplayName</key>
<string>QuteCart</string>
         👆 Change this too
```

### Step 2: Update Package Name (Optional but Recommended)

**Why?** Your package name should reflect your brand, not "readyecommerce.apps"

**Change package name:**

```bash
cd FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode

# Install package renaming tool (if not already installed)
flutter pub add change_app_package_name

# Change package name (example)
flutter pub run change_app_package_name:main com.qutecat.app
#                                               👆 Your package name
```

**Package naming convention:**
- Format: `com.companyname.appname`
- All lowercase
- No spaces or special characters
- Examples:
  - `com.qutecat.app`
  - `com.yourcompany.ecommerce`
  - `com.brandname.shop`

**⚠️ IMPORTANT:** After changing package name:
- Update Firebase Console with new package name
- Re-download google-services.json
- Re-download GoogleService-Info.plist

### Step 3: Update Colors (ZARA Style)

**File:** `lib/config/app_color.dart`

**Line 89:**

```dart
// ❌ BEFORE (Pink)
static Color primary = const Color(0xFFEE456B);

// ✅ AFTER (ZARA Black)
static Color primary = const Color(0xFF000000);
```

**Full ZARA color update:**

```dart
class EcommerceAppColor {
  static const Color white = Color(0xFFFFFFFF);
  static const Color offWhite = Color(0xFFFAFAFA);
  static const Color black = Color(0xFF000000);
  static const Color gray = Color(0xFF666666);
  static const Color lightGray = Color(0xFFD4D4D4);
  static Color primary = const Color(0xFF000000);  // BLACK
  static const Color carrotOrange = Color(0xFF000000);
  static const Color blueChalk = Color(0xFFF5F5F5);
  static const Color red = Color(0xFFDC2626);
  static const Color green = Color(0xFF16A34A);
}
```

### Step 4: Update App Icon

**Generate custom icon:**

1. Create 1024x1024 PNG icon (your logo)
2. Go to [appicon.co](https://appicon.co/)
3. Upload your icon
4. Download generated icons
5. Replace icons in:
   - `android/app/src/main/res/mipmap-hdpi/ic_launcher.png`
   - `android/app/src/main/res/mipmap-mdpi/ic_launcher.png`
   - `android/app/src/main/res/mipmap-xhdpi/ic_launcher.png`
   - `android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png`
   - `android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png`
   - `ios/Runner/Assets.xcassets/AppIcon.appiconset/`

**Or use flutter_launcher_icons (automated):**

1. Add to `pubspec.yaml`:

```yaml
dev_dependencies:
  flutter_launcher_icons: ^0.13.1

flutter_launcher_icons:
  android: true
  ios: true
  image_path: "assets/icon/app_icon.png"  # Your 1024x1024 icon
  adaptive_icon_background: "#000000"     # Black background for Android
  adaptive_icon_foreground: "assets/icon/app_icon_foreground.png"
```

2. Place your icon in `assets/icon/app_icon.png`

3. Generate icons:

```bash
flutter pub get
flutter pub run flutter_launcher_icons
```

### Step 5: Update Splash Screen Logo

**File locations:**
- `assets/png/logo.png` (displayed on splash screen)
- `assets/svg/logo.svg` (used in app header)

**Replace with your logo:**

```bash
# Backup original
cp assets/png/logo.png assets/png/logo_original_backup.png

# Replace with your logo
cp ~/your-logo.png assets/png/logo.png
```

**Logo requirements:**
- PNG format with transparent background
- Recommended size: 512x512 pixels
- Your brand colors (or white for black splash screen)

---

## Part 5: Verification & Testing

### Configuration Verification Checklist

**Backend Connection:**
- [ ] `baseUrl` updated in `app_constants.dart`
- [ ] API responds to `/api/master` endpoint
- [ ] SSL certificate valid (HTTPS)
- [ ] CORS enabled on Laravel backend

**Firebase:**
- [ ] `google-services.json` in `android/app/` directory
- [ ] `GoogleService-Info.plist` in `ios/Runner/` directory (if iOS)
- [ ] Server key added to Laravel .env
- [ ] Test notification received on device

**Pusher:**
- [ ] `pusherApiKey` updated in `app_constants.dart`
- [ ] `pusherCluster` updated in `app_constants.dart`
- [ ] Pusher credentials match Laravel .env
- [ ] Real-time messages working
- [ ] Queue worker running on Laravel

**Branding:**
- [ ] App name changed in AndroidManifest.xml
- [ ] App name changed in Info.plist (iOS)
- [ ] Package name changed (optional)
- [ ] Primary color updated
- [ ] App icon replaced
- [ ] Splash logo replaced

### Build Test

**Test debug build:**

```bash
cd FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode

# Clean build
flutter clean
flutter pub get

# Test run
flutter run

# Check for errors in console
```

**Expected output:**

```
✓ Built build/app/outputs/flutter-apk/app-debug.apk
Launching lib/main.dart on Android SDK built for x86 in debug mode...
Running Gradle task 'assembleDebug'...
✓ Built build/app/outputs/flutter-apk/app-debug.apk. (12.3s)
Installing build/app/outputs/flutter-apk/app-debug.apk...
```

**Test release build:**

```bash
# Build release APK
flutter build apk --release

# Check output
ls -lh build/app/outputs/flutter-apk/app-release.apk

# Should be 20-40 MB
```

### Functional Testing

**Test all critical features:**

1. **Authentication:**
   - [ ] Registration works
   - [ ] OTP code arrives
   - [ ] Login successful
   - [ ] Token saved
   - [ ] Logout works

2. **Product Browsing:**
   - [ ] Home page loads
   - [ ] Products display
   - [ ] Product images load
   - [ ] Product videos play
   - [ ] Categories work
   - [ ] Search works

3. **Shopping:**
   - [ ] Add to cart
   - [ ] Cart updates
   - [ ] Checkout loads
   - [ ] Address selection
   - [ ] Payment methods appear
   - [ ] Order placement succeeds

4. **Real-time Features:**
   - [ ] Send message to shop
   - [ ] Receive reply instantly
   - [ ] Push notification arrives
   - [ ] Notification opens app correctly

5. **Offline Features:**
   - [ ] Cart persists after app restart
   - [ ] Cached images display offline

---

## Part 6: Environment-Specific Configuration (Advanced)

### Multiple Environments (Dev, Staging, Production)

**Create environment-specific configs:**

**File:** `lib/config/environment.dart` (Create new file)

```dart
enum Environment {
  development,
  staging,
  production,
}

class EnvironmentConfig {
  static const Environment currentEnvironment = Environment.production;

  static String get baseUrl {
    switch (currentEnvironment) {
      case Environment.development:
        return 'http://localhost:8000/api';  // Local Laravel
      case Environment.staging:
        return 'https://staging.qutecat.com/api';
      case Environment.production:
        return 'https://qutecat.com/api';
    }
  }

  static String get pusherKey {
    switch (currentEnvironment) {
      case Environment.development:
        return 'dev_pusher_key';
      case Environment.staging:
        return 'staging_pusher_key';
      case Environment.production:
        return 'prod_pusher_key';
    }
  }

  static String get pusherCluster {
    return 'mt1';  // Same for all environments
  }

  static bool get isProduction => currentEnvironment == Environment.production;
  static bool get isDevelopment => currentEnvironment == Environment.development;
}
```

**Update app_constants.dart:**

```dart
import 'environment.dart';

class AppConstants {
  // Use environment-specific URL
  static String baseUrl = EnvironmentConfig.baseUrl;

  // Use environment-specific Pusher key
  static String pusherApiKey = EnvironmentConfig.pusherKey;
  static String pusherCluster = EnvironmentConfig.pusherCluster;

  // ... rest of code
}
```

**Switch environments:**

Just change one line in `environment.dart`:

```dart
// Development
static const Environment currentEnvironment = Environment.development;

// Staging
static const Environment currentEnvironment = Environment.staging;

// Production
static const Environment currentEnvironment = Environment.production;
```

---

## Part 7: Common Issues & Solutions

### Issue 1: "Unable to connect to API"

**Symptoms:**
- App shows network error
- Products don't load
- Login fails

**Solutions:**

1. **Check API URL:**
```dart
// Make sure baseUrl is correct and accessible
static const String baseUrl = 'https://YOUR_DOMAIN.com/api';
```

2. **Test API directly:**
```bash
curl https://YOUR_DOMAIN.com/api/master
# Should return JSON response
```

3. **Check CORS headers on Laravel:**
```php
// In Laravel: config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['*'],  // Or specific origins
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

4. **Check SSL certificate:**
```bash
curl -I https://YOUR_DOMAIN.com
# Should return 200 OK, not SSL error
```

### Issue 2: "Push notifications not working"

**Solutions:**

1. **Verify google-services.json location:**
```bash
# Must be exactly here:
android/app/google-services.json
```

2. **Check Firebase project settings:**
- Package name matches Flutter app
- Cloud Messaging API enabled
- Server key added to Laravel backend

3. **Check app permissions:**
```xml
<!-- In AndroidManifest.xml -->
<uses-permission android:name="android.permission.INTERNET"/>
```

4. **Test with Firebase Console:**
- Send test message from Firebase Console → Cloud Messaging
- Should receive within 30 seconds

### Issue 3: "Real-time messages delayed or not arriving"

**Solutions:**

1. **Verify Pusher credentials match:**
```bash
# Laravel .env
PUSHER_APP_KEY=abc123

# Flutter app_constants.dart
pusherApiKey = 'abc123'  # Must match exactly
```

2. **Check Laravel queue worker:**
```bash
# On server
ps aux | grep queue:work

# Should show running process
# If not, start it:
php artisan queue:work --daemon
```

3. **Check Pusher dashboard:**
- Go to Pusher dashboard
- Check "Debug Console"
- Should see events when messages sent

4. **Verify broadcast driver:**
```bash
# In Laravel .env
BROADCAST_DRIVER=pusher  # Not 'log' or 'null'
```

### Issue 4: "Build fails after configuration"

**Solutions:**

1. **Clean build:**
```bash
flutter clean
flutter pub get
flutter build apk
```

2. **Check google-services.json syntax:**
```bash
# Validate JSON
cat android/app/google-services.json | python -m json.tool
# Should print formatted JSON without errors
```

3. **Rebuild dependencies:**
```bash
# Android
cd android
./gradlew clean
cd ..

# iOS
cd ios
pod install --repo-update
cd ..
```

### Issue 5: "App crashes on startup"

**Solutions:**

1. **Check for syntax errors in changed files:**
```bash
flutter analyze
```

2. **View crash logs:**
```bash
# Android
adb logcat | grep Flutter

# iOS
# View logs in Xcode
```

3. **Test with original config first:**
- Revert to demo server URL
- If works, issue is with your config
- Check each change one by one

---

## Part 8: Quick Configuration Script

**Create a configuration script to automate setup:**

**File:** `configure_production.sh` (Create in project root)

```bash
#!/bin/bash

# Flutter App Production Configuration Script
# Usage: ./configure_production.sh

echo "🚀 QuteCart Production Configuration"
echo "======================================"
echo ""

# Get configuration values
read -p "Enter your production API URL (e.g., https://qutecat.com/api): " API_URL
read -p "Enter your Pusher App Key: " PUSHER_KEY
read -p "Enter your Pusher Cluster (e.g., mt1): " PUSHER_CLUSTER
read -p "Enter your app name (e.g., QuteCart): " APP_NAME

echo ""
echo "Configuration Summary:"
echo "- API URL: $API_URL"
echo "- Pusher Key: $PUSHER_KEY"
echo "- Pusher Cluster: $PUSHER_CLUSTER"
echo "- App Name: $APP_NAME"
echo ""

read -p "Proceed with configuration? (y/n): " CONFIRM

if [ "$CONFIRM" != "y" ]; then
    echo "Configuration cancelled."
    exit 0
fi

echo ""
echo "📝 Updating configuration files..."

# Update app_constants.dart
sed -i "s|static const String baseUrl = '.*';|static const String baseUrl = '$API_URL';|" \
    lib/config/app_constants.dart

sed -i "s|static String pusherApiKey = '.*';|static String pusherApiKey = '$PUSHER_KEY';|" \
    lib/config/app_constants.dart

sed -i "s|static String pusherCluster = '.*';|static String pusherCluster = '$PUSHER_CLUSTER';|" \
    lib/config/app_constants.dart

# Update Android app name
sed -i "s|android:label=\".*\"|android:label=\"$APP_NAME\"|" \
    android/app/src/main/AndroidManifest.xml

# Update iOS app name
sed -i '' "s|<string>Ready eCommerce</string>|<string>$APP_NAME</string>|" \
    ios/Runner/Info.plist 2>/dev/null || true

echo "✅ Configuration files updated!"
echo ""
echo "⚠️  Don't forget to:"
echo "   1. Add google-services.json to android/app/"
echo "   2. Add GoogleService-Info.plist to ios/Runner/ (if iOS)"
echo "   3. Update app icon"
echo "   4. Test with: flutter run"
echo ""
echo "🎉 Configuration complete!"
```

**Make script executable:**

```bash
chmod +x configure_production.sh
```

**Run script:**

```bash
./configure_production.sh
```

---

## Summary

### What We Configured:

1. ✅ **Backend API URL** - Points to your production Laravel server
2. ✅ **Firebase** - Push notifications configured
3. ✅ **Pusher** - Real-time messaging configured
4. ✅ **Branding** - App name, colors, icons updated

### Files Modified:

| File | Changes |
|------|---------|
| `lib/config/app_constants.dart` | API URL, Pusher credentials |
| `android/app/google-services.json` | Firebase config (Android) |
| `ios/Runner/GoogleService-Info.plist` | Firebase config (iOS) |
| `android/app/src/main/AndroidManifest.xml` | App name |
| `ios/Runner/Info.plist` | App name (iOS) |
| `lib/config/app_color.dart` | Brand colors (optional) |

### Next Steps:

1. **Test thoroughly** - Use verification checklist above
2. **Build release APK** - `flutter build apk --release`
3. **Test on real devices** - Not just emulator
4. **Deploy to app stores** - See Flutter App Setup Guide

---

**Document Version:** 1.0
**Last Updated:** 2025-11-06
**Next Review:** After first production deployment
