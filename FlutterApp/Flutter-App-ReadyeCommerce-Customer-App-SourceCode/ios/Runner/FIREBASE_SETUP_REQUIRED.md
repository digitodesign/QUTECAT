# ⚠️ Firebase iOS Configuration Required

## Current Status

The existing `GoogleService-Info.plist` file is configured for the **demo project** and needs to be replaced with your QuteCart production Firebase project.

**Current Configuration:**
- Project ID: `ready-ecommerce` (DEMO)
- Bundle ID: `com.readyecommerce` or `com.readyecommerce.apps`
- Status: ❌ Needs replacement

## Required Actions

### 1. Create/Use Firebase Project

If you haven't already created a Firebase project for QuteCart:

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your QuteCart project (or create one)

### 2. Add iOS App

1. In Firebase Console, click the iOS icon to add an iOS app
2. **iOS bundle ID:** Check your current bundle ID in `ios/Runner/Info.plist`
   - Look for `<key>CFBundleIdentifier</key>`
   - Likely: `com.readyecommerce.apps` or `com.readyecommerce`
3. **App nickname (optional):** QuteCart iOS
4. **App Store ID (optional):** Leave empty for now
5. Click **"Register app"**

### 3. Download Configuration File

1. Click **"Download GoogleService-Info.plist"**
2. **Replace** the existing file at:
   ```
   ios/Runner/GoogleService-Info.plist
   ```
3. Do NOT rename the file - it must be exactly `GoogleService-Info.plist`

### 4. Enable Push Notifications in Xcode

1. Open the iOS project in Xcode:
   ```bash
   open ios/Runner.xcworkspace
   ```

2. Select the **Runner** target

3. Go to **"Signing & Capabilities"** tab

4. Click **"+ Capability"**

5. Add **"Push Notifications"**

6. Add **"Background Modes"** and enable:
   - Remote notifications
   - Background fetch

### 5. Configure APNs (Apple Push Notification Service)

#### Option A: Using APNs Authentication Key (Recommended)

1. Go to [Apple Developer Portal](https://developer.apple.com/account/)
2. Navigate to **Certificates, Identifiers & Profiles** → **Keys**
3. Create a new key with **Apple Push Notifications service (APNs)** enabled
4. Download the `.p8` key file
5. In Firebase Console:
   - Go to **Project Settings** → **Cloud Messaging** → **iOS app configuration**
   - Upload the `.p8` key
   - Enter Key ID and Team ID

#### Option B: Using APNs Certificates

1. Generate a certificate signing request in Keychain Access
2. Create APNs certificate in Apple Developer Portal
3. Download and install the certificate
4. Export the certificate as `.p12` file
5. Upload to Firebase Console

### 6. Test Push Notifications

1. Build and run the app on a real iOS device (simulator won't work for push notifications)
   ```bash
   flutter run --release
   ```

2. In Firebase Console, go to **Cloud Messaging** → **"Send your first message"**

3. Fill in:
   - **Notification title:** Test
   - **Notification text:** Testing iOS push notifications
   - **Target:** Select your iOS app

4. Click **"Send test message"**

5. Verify notification appears on device

## Bundle ID Information

To check your current bundle ID:

```bash
cat ios/Runner/Info.plist | grep -A 1 CFBundleIdentifier
```

If you need to change the bundle ID:

1. Use the package name change tool:
   ```bash
   flutter pub run change_app_package_name:main com.qutekart.app
   ```

2. Then update Firebase with the NEW bundle ID

3. Download a NEW `GoogleService-Info.plist` file

## Verification

After replacing the configuration file:

1. Clean build:
   ```bash
   flutter clean
   flutter pub get
   ```

2. Build iOS app:
   ```bash
   flutter build ios --release
   ```

3. Test on a real iOS device

## Common Issues

### Issue: "No push notification permission"

**Solution:** The app requests push notification permission on first launch. If denied, user must enable it manually in iOS Settings → QuteCart → Notifications

### Issue: "APNs device token not set"

**Solution:**
- Ensure you're testing on a real device, not simulator
- Check that Push Notifications capability is enabled in Xcode
- Verify APNs certificate/key is correctly configured in Firebase

### Issue: "Invalid provisioning profile"

**Solution:** You need an Apple Developer account to test push notifications on iOS. Simulators do not support push notifications.

## Documentation

For detailed instructions, see:
- `docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md`
- `FlutterApp/CONFIGURATION_CHECKLIST.md`
- [Firebase iOS Setup Guide](https://firebase.google.com/docs/ios/setup)
- [Flutter Firebase Messaging](https://firebase.flutter.dev/docs/messaging/overview/)

---

**Last Updated:** 2025-11-06
**Status:** ⚠️ **ACTION REQUIRED** - Replace GoogleService-Info.plist with QuteCart production Firebase project
