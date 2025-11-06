# ⚠️ Firebase Configuration Required

## Current Status

The existing `google-services.json` file is configured for the **demo project** and needs to be replaced with your QuteCart production Firebase project.

**Current Configuration:**
- Project ID: `ready-ecommerce` (DEMO)
- Package Name: `com.readyecommerce.apps`
- Status: ❌ Needs replacement

## Required Actions

### 1. Create Firebase Project for QuteCart

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click **"Add project"**
3. Project name: **QuteCart** (or your preferred name)
4. Follow the setup wizard

### 2. Add Android App

1. In Firebase Console, click the Android icon to add an Android app
2. **Android package name:** `com.readyecommerce.apps`
   - ⚠️ IMPORTANT: This must match the package name in `android/app/build.gradle`
3. **App nickname (optional):** QuteCart Android
4. **Debug signing certificate SHA-1 (optional):** Leave empty for now
5. Click **"Register app"**

### 3. Download Configuration File

1. Click **"Download google-services.json"**
2. **Replace** the existing file at:
   ```
   android/app/google-services.json
   ```
3. Do NOT rename the file - it must be exactly `google-services.json`

### 4. Enable Cloud Messaging

1. In Firebase Console, go to **Project Settings** → **Cloud Messaging**
2. Go to the **"Cloud Messaging API (Legacy)"** tab
3. Copy the **"Server key"** (starts with `AAAA...`)
4. Add this to your Laravel production `.env` file:
   ```bash
   FIREBASE_SERVER_KEY=AAAA...your_server_key_here
   ```

### 5. Test Push Notifications

1. Build and run the app on a real device
2. In Firebase Console, go to **Cloud Messaging** → **"Send your first message"**
3. Fill in:
   - **Notification title:** Test
   - **Notification text:** Testing push notifications
   - **Target:** Select your app
4. Click **"Send test message"**
5. Verify notification appears on device

## Package Name Information

If you decide to change the package name from `com.readyecommerce.apps` to something like `com.qutecart.app`:

1. First change the package name using:
   ```bash
   cd FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode
   flutter pub run change_app_package_name:main com.qutecart.app
   ```

2. Then create a NEW Android app in Firebase with the NEW package name

3. Download the NEW `google-services.json` file

## iOS Configuration

If you're also building for iOS, you need to configure Firebase for iOS as well:

1. In Firebase Console, click the iOS icon to add an iOS app
2. **iOS bundle ID:** `com.readyecommerce.apps` (or your custom bundle ID)
3. Download `GoogleService-Info.plist`
4. Replace the file at:
   ```
   ios/Runner/GoogleService-Info.plist
   ```

## Verification

After replacing the configuration file:

1. Clean build:
   ```bash
   flutter clean
   flutter pub get
   ```

2. Build release APK:
   ```bash
   flutter build apk --release
   ```

3. Check for errors in the build output

4. Test on a real device (emulators may not receive push notifications reliably)

## Documentation

For detailed instructions, see:
- `docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md`
- `FlutterApp/CONFIGURATION_CHECKLIST.md`

---

**Last Updated:** 2025-11-06
**Status:** ⚠️ **ACTION REQUIRED** - Replace google-services.json with QuteCart production Firebase project
