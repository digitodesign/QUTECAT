# Flutter Mobile App - Complete Setup Guide

**App Name:** Ready eCommerce Customer App

**Version:** 1.0.5+7

**Platform:** Flutter (Cross-platform: Android & iOS)

**Last Updated:** 2025-11-06

---

## Executive Summary

The Ready eCommerce Flutter app is a **multi-service customer-facing mobile application** that supports:
- **E-commerce** (primary service)
- **Food delivery**
- **Grocery delivery**
- **Pharmacy delivery**

The app is fully integrated with the Laravel backend and provides a complete shopping experience with real-time messaging, push notifications, video support, and multiple payment gateways.

**Key Features:**
- Multi-vendor marketplace support
- Product browsing with video support
- Real-time chat with shops (Pusher)
- Push notifications (Firebase Cloud Messaging)
- Multiple payment methods
- Order tracking
- Return/refund management
- Multi-language support
- Dark mode support
- Offline cart management (Hive local database)

---

## Project Structure

```
FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/
├── android/                    # Android native configuration
│   ├── app/
│   │   ├── build.gradle       # Android app config
│   │   ├── google-services.json  # Firebase config
│   │   ├── keystore.jks       # Release signing key
│   │   └── src/main/AndroidManifest.xml
│   └── build.gradle           # Android project config
├── ios/                       # iOS native configuration
│   ├── Runner/
│   │   ├── Info.plist        # iOS app config
│   │   └── Assets.xcassets/  # iOS assets
│   └── Podfile               # iOS dependencies
├── lib/                       # Main Flutter code
│   ├── config/               # App configuration
│   │   ├── app_constants.dart   # API endpoints & constants
│   │   ├── app_color.dart       # Color themes
│   │   ├── theme.dart           # App theme
│   │   └── app_text_style.dart  # Typography
│   ├── models/               # Data models
│   │   ├── eCommerce/       # E-commerce models
│   │   ├── food/            # Food delivery models
│   │   ├── grocery/         # Grocery models
│   │   └── pharmacy/        # Pharmacy models
│   ├── services/            # API services
│   │   ├── base/            # Base HTTP client
│   │   ├── common/          # Shared services
│   │   ├── eCommerce/       # E-commerce API services
│   │   ├── food/            # Food API services
│   │   ├── grocery/         # Grocery API services
│   │   └── pharmacy/        # Pharmacy API services
│   ├── controllers/         # Riverpod state management
│   ├── views/               # UI screens
│   │   ├── common/          # Shared screens (auth, splash, etc.)
│   │   ├── eCommerce/       # E-commerce screens
│   │   ├── food/            # Food delivery screens
│   │   ├── grocery/         # Grocery screens
│   │   └── pharmacy/        # Pharmacy screens
│   ├── components/          # Reusable widgets
│   ├── utils/               # Utility functions
│   ├── l10n/                # Localization files
│   ├── routes.dart          # Navigation routing
│   ├── main.dart            # App entry point
│   └── firebase_options.dart  # Firebase config
├── assets/                   # Static assets
│   ├── svg/                 # SVG icons
│   ├── png/                 # PNG images
│   ├── json/                # JSON data
│   └── font/                # Custom fonts (Mulish)
├── pubspec.yaml             # Dependencies & metadata
└── README.md                # Documentation

```

---

## Technology Stack

### Core Framework
- **Flutter SDK:** >=3.5.0 <4.0.0
- **Dart:** Latest stable
- **Architecture:** Clean Architecture with Riverpod

### State Management
- **flutter_riverpod** (2.4.9) - Modern, reactive state management

### UI/UX
- **flutter_screenutil** (5.9.0) - Responsive screen adaptation
- **page_transition** (2.1.0) - Smooth page animations
- **flutter_svg** (2.0.9) - SVG support
- **cached_network_image** (3.3.0) - Image caching
- **shimmer** (3.0.0) - Loading skeletons
- **flutter_animate** (4.5.0) - Declarative animations
- **gap** (3.0.1) - Spacing widgets
- **auto_size_text** (3.0.0) - Responsive text

### Backend Integration
- **dio** (5.4.0) - HTTP client
- **pretty_dio_logger** (1.3.1) - API logging

### Local Storage
- **hive** (2.2.3) - Local NoSQL database
- **hive_flutter** (1.1.0) - Hive Flutter integration
- **path_provider** (2.1.2) - File system paths

### Firebase Services
- **firebase_core** (3.0.0) - Firebase SDK
- **firebase_messaging** (15.0.0) - Push notifications
- **flutter_local_notifications** (17.1.2) - Local notifications

### Real-time Communication
- **pusher_channels_flutter** (2.5.0) - Real-time messaging

### Media Handling
- **image_picker** (1.0.7) - Image/video picker
- **chewie** (1.9.2) - Video player
- **flutter_downloader** (1.11.8) - File downloads
- **open_file** (3.5.10) - File opening

### Payment & Location
- **geolocator** (13.0.1) - GPS location
- **url_launcher** (6.2.5) - External URLs
- **flutter_inappwebview** (6.0.0) - In-app browser for payments
- **webview_flutter** (4.10.0) - WebView widget

### Utilities
- **intl** (0.20.0) - Internationalization
- **flutter_localizations** - Multi-language support
- **country_code_picker** (3.1.0) - Country codes
- **permission_handler** (11.3.0) - Runtime permissions
- **connectivity_wrapper** (1.1.4) - Network connectivity
- **share_plus** (10.1.4) - Content sharing
- **fluttertoast** (8.2.12) - Toast messages

### UI Components
- **flutter_form_builder** (10.2.0) - Form management
- **pinput** (5.0.0) - OTP input
- **flutter_rating_bar** (4.0.1) - Star ratings
- **percent_indicator** (4.2.3) - Progress indicators
- **carousel_slider** (5.0.0) - Image carousels
- **dotted_border** (2.1.0) - Dotted borders
- **flutter_timer_countdown** (1.0.7) - Countdown timers

---

## Configuration Files

### 1. app_constants.dart

**Location:** `lib/config/app_constants.dart`

**Current Configuration:**

```dart
// API Base URL
static const String baseUrl = 'https://demo.readyecommerce.app/api';

// Service Name (changes app behavior)
static String appServiceName = 'ecommerce';  // or 'food', 'grocery', 'pharmacy'

// Pusher Configuration (Real-time messaging)
static String pusherApiKey = 'a3cbadc04a202a7746fc';
static String pusherCluster = 'mt1';

// Hive Box Names (Local storage)
static const String appSettingsBox = 'appSettings';
static const String userBox = 'laundrySeller_userBox';
static const String cartModelBox = 'hive_cart_model_box';
```

**Key API Endpoints:**

| Feature | Endpoint |
|---------|----------|
| Login | `/api/login` |
| Registration | `/api/registration` |
| Home/Dashboard | `/api/home` |
| Products | `/api/products` |
| Product Details | `/api/product-details` |
| Categories | `/api/categories` |
| Shops | `/api/shops` |
| Cart | `/api/carts` |
| Checkout | `/api/cart/checkout` |
| Place Order | `/api/place-order` |
| Orders | `/api/orders` |
| Messages | `/api/get-message` |

### 2. Android Configuration

**File:** `android/app/build.gradle`

```gradle
android {
    namespace "com.readyecommerce.apps"
    compileSdkVersion = 36

    defaultConfig {
        applicationId "com.readyecommerce.apps"
        multiDexEnabled = true
        minSdkVersion flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile keystoreProperties['storeFile']
            storePassword keystoreProperties['storePassword']
        }
    }
}
```

**Package Name:** `com.readyecommerce.apps`

**App Name:** "Ready eCommerce"

### 3. Firebase Configuration

**Android:** `android/app/google-services.json`
**iOS:** `ios/Runner/GoogleService-Info.plist`

**Services Used:**
- Firebase Cloud Messaging (Push Notifications)
- Firebase Analytics (Optional)

---

## Setup Instructions

### Prerequisites

1. **Flutter SDK** installed (version >=3.5.0)
2. **Android Studio** (for Android development)
3. **Xcode** (for iOS development - macOS only)
4. **Git** installed
5. **Active internet connection**

### Step 1: Clone Repository

```bash
cd /home/user/QUTECAT/FlutterApp
# Already cloned at: Flutter-App-ReadyeCommerce-Customer-App-SourceCode
cd Flutter-App-ReadyeCommerce-Customer-App-SourceCode
```

### Step 2: Install Dependencies

```bash
# Get Flutter packages
flutter pub get

# Check Flutter doctor
flutter doctor -v

# Expected output: ✓ Flutter, ✓ Android toolchain, ✓ Xcode (macOS)
```

### Step 3: Configure Backend Connection

**Edit:** `lib/config/app_constants.dart`

```dart
// Change from demo server to your production server
static const String baseUrl = 'https://yoursite.com/api';

// Update Pusher credentials (from backend .env)
static String pusherApiKey = 'YOUR_PUSHER_KEY';
static String pusherCluster = 'YOUR_PUSHER_CLUSTER';
```

### Step 4: Configure Firebase

**For Android:**

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create new project or use existing
3. Add Android app with package name: `com.readyecommerce.apps`
4. Download `google-services.json`
5. Place in: `android/app/google-services.json`

**For iOS:**

1. Add iOS app in Firebase Console
2. Download `GoogleService-Info.plist`
3. Place in: `ios/Runner/GoogleService-Info.plist`

### Step 5: Update App Name & Package

**Change Package Name (Optional):**

```bash
# Install package renaming tool
flutter pub add change_app_package_name

# Run package rename (example)
flutter pub run change_app_package_name:main com.yourcompany.qutecat
```

**Change App Name:**

**Android:** Edit `android/app/src/main/AndroidManifest.xml`
```xml
<application
    android:label="YourApp Name"
    ...>
```

**iOS:** Edit `ios/Runner/Info.plist`
```xml
<key>CFBundleName</key>
<string>YourApp Name</string>
```

### Step 6: Configure App Signing (Android)

**Create keystore (if not exists):**

```bash
keytool -genkey -v -keystore keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

**Create:** `android/key.properties`

```properties
storePassword=YOUR_KEYSTORE_PASSWORD
keyPassword=YOUR_KEY_PASSWORD
keyAlias=upload
storeFile=../app/keystore.jks
```

**Move keystore:**

```bash
mv keystore.jks android/app/keystore.jks
```

### Step 7: Update App Icons

**Generate app icons:**

1. Create 1024x1024 PNG icon
2. Use online tool: [https://appicon.co](https://appicon.co)
3. Replace icons in:
   - `android/app/src/main/res/mipmap-*/ic_launcher.png`
   - `ios/Runner/Assets.xcassets/AppIcon.appiconset/`

**Or use flutter_launcher_icons:**

```yaml
# Add to pubspec.yaml
dev_dependencies:
  flutter_launcher_icons: ^0.13.1

flutter_launcher_icons:
  android: true
  ios: true
  image_path: "assets/icon/app_icon.png"
```

```bash
flutter pub get
flutter pub run flutter_launcher_icons
```

---

## Building the App

### Android Debug Build

```bash
# Connect Android device or start emulator
flutter devices

# Run on connected device
flutter run

# Build APK
flutter build apk --release

# Build App Bundle (for Play Store)
flutter build appbundle --release

# Output location:
# build/app/outputs/flutter-apk/app-release.apk
# build/app/outputs/bundle/release/app-release.aab
```

### iOS Build (macOS only)

```bash
# Install CocoaPods dependencies
cd ios
pod install
cd ..

# Run on simulator
open -a Simulator
flutter run

# Build IPA (requires Apple Developer Account)
flutter build ios --release

# Open in Xcode
open ios/Runner.xcworkspace
```

---

## Key Features Implementation

### 1. Multi-Service Architecture

The app supports 4 different services with different UI/themes:

**Service Selection:**

```dart
// In app_constants.dart
static String appServiceName = 'ecommerce';  // Change to 'food', 'grocery', or 'pharmacy'
```

**Theme Changes:**

Each service has its own color scheme defined in `lib/config/app_color.dart`:

```dart
class AppColorManager {
  static AppColor getColorClass({required String serviceName}) {
    switch (serviceName.toLowerCase()) {
      case 'ecommerce':
        return AppColor(primaryColor: Color(0xFFEE456B), ...);
      case 'food':
        return AppColor(primaryColor: Color(0xFF8322FF), ...);
      case 'grocery':
        return AppColor(primaryColor: Color(0xFF8322FF), ...);
      case 'pharmacy':
        return AppColor(primaryColor: Color(0xFF8322FF), ...);
    }
  }
}
```

### 2. Dynamic Primary Color

The app can fetch and apply primary color from backend:

**Backend API Response** (`/api/master`):

```json
{
  "data": {
    "primary_color": "#000000"
  }
}
```

**App automatically applies color:**

```dart
// In main.dart
final primaryColor = box.get(AppConstants.primaryColor);
if (primaryColor != null) {
  EcommerceAppColor.primary = hexToColor(primaryColor);
}
```

### 3. Offline Cart Management

**Technology:** Hive (local NoSQL database)

**Implementation:**

```dart
// Cart model with Hive annotations
@HiveType(typeId: 0)
class HiveCartModel extends HiveObject {
  @HiveField(0)
  final int productId;

  @HiveField(1)
  final int quantity;

  @HiveField(2)
  final double price;

  // ... more fields
}
```

**Benefits:**
- Cart persists even if app is closed
- Works offline
- Syncs with backend on checkout

### 4. Real-time Messaging

**Technology:** Pusher Channels

**Implementation:**

```dart
// In message service
final pusher = PusherChannelsFlutter();
await pusher.init(
  apiKey: AppConstants.pusherApiKey,
  cluster: AppConstants.pusherCluster,
);

// Subscribe to shop channel
final channel = await pusher.subscribe(channelName: 'shop.$shopId');

// Listen for new messages
channel.bind(
  eventName: 'message.sent',
  onEvent: (event) {
    // Handle new message
  },
);
```

### 5. Push Notifications

**Technology:** Firebase Cloud Messaging (FCM)

**Implementation:**

```dart
// Initialize FCM
await Firebase.initializeApp();
await setupFlutterNotifications();

// Get FCM token
String? fcmToken = await FirebaseMessaging.instance.getToken();

// Send token to backend for registration
```

**Notification Types:**
- Order status updates
- New messages from shops
- Flash sale alerts
- Marketing notifications

### 6. Product Video Support

**Already Implemented!** ✅

**Video Display:**

```dart
// In product_image_page_view.dart
if (fileSystem == FileSystem.file.name) {
  // Uploaded video file
  return VideoPlayer(
    videoUrl: thumbnail.url ?? '',
  );
} else if (fileSystem != FileSystem.image.name) {
  // External embed (YouTube, Vimeo, Dailymotion)
  return IframeCard(
    iframeUrl: thumbnail.url ?? '',
  );
}
```

**Supported Video Types:**
- MP4, AVI, MOV, WMV (uploaded files)
- YouTube embeds
- Vimeo embeds
- Dailymotion embeds

### 7. Multi-language Support

**Technology:** flutter_localizations + intl

**Supported Languages:**

Check `lib/l10n/` directory for available translations.

**Change Language:**

```dart
// Save selected language
box.put(AppConstants.appLocal, 'en');  // or 'ar', 'es', etc.
```

**Add New Language:**

1. Create `lib/l10n/intl_XX.arb` (XX = language code)
2. Run: `flutter pub run intl_utils:generate`
3. Restart app

### 8. Dark Mode

**Toggle Dark Mode:**

```dart
// Save preference
box.put(AppConstants.isDarkTheme, true);

// App automatically rebuilds with dark theme
```

**Dark Theme Colors:**

Defined in `lib/config/theme.dart`:

```dart
ThemeData getAppTheme({required bool isDarkTheme}) {
  return ThemeData(
    scaffoldBackgroundColor: isDarkTheme ? appColor.dark : appColor.light,
    // ... other theme properties
  );
}
```

---

## API Integration Details

### Authentication Flow

**1. Registration:**

```
POST /api/registration
Body: {
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "password": "password123"
}
```

**2. OTP Verification:**

```
POST /api/verify-otp
Body: {
  "phone": "+1234567890",
  "otp": "123456"
}
```

**3. Login:**

```
POST /api/login
Body: {
  "email": "john@example.com",
  "password": "password123"
}

Response: {
  "data": {
    "token": "Bearer eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": { ... }
  }
}
```

**4. Token Storage:**

```dart
// Save token to Hive
final authBox = Hive.box(AppConstants.authBox);
authBox.put(AppConstants.authToken, token);

// Add to all API requests
headers: {
  'Authorization': 'Bearer $token',
}
```

### Product Browsing

**Get Products:**

```
GET /api/products?shop_id=123&category_id=5&page=1

Response: {
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "price": "99.99",
      "thumbnails": [
        {
          "id": 1,
          "url": "https://storage.../video.mp4",
          "type": "file"
        },
        {
          "id": 2,
          "thumbnail": "https://storage.../image.jpg",
          "type": "image"
        }
      ]
    }
  ]
}
```

**Get Product Details:**

```
GET /api/product-details/{id}

Response: {
  "data": {
    "product": { ... },
    "related_products": [ ... ],
    "reviews": [ ... ]
  }
}
```

### Cart & Checkout

**Add to Cart:**

```
POST /api/cart/store
Body: {
  "product_id": 1,
  "quantity": 2,
  "variation_id": 5  // Optional
}
```

**Get Cart Summary:**

```
GET /api/cart/checkout

Response: {
  "data": {
    "sub_total": "199.98",
    "delivery_charge": "5.00",
    "discount": "10.00",
    "total": "194.98",
    "items": [ ... ]
  }
}
```

**Place Order:**

```
POST /api/place-order
Body: {
  "address_id": 1,
  "payment_method": "stripe",  // or 'cod', 'paypal', etc.
  "delivery_type": "home",     // or 'pickup'
}

Response: {
  "data": {
    "order_id": 12345,
    "payment_url": "https://stripe.com/..."  // If online payment
  }
}
```

---

## Customization for Your Brand

### 1. Update Colors (ZARA Style)

**File:** `lib/config/app_color.dart`

**Line 89:**

```dart
// Before (Pink)
static Color primary = const Color(0xFFEE456B);

// After (ZARA Black)
static Color primary = const Color(0xFF000000);
```

**Full ZARA Color Update:**

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

### 2. Update App Name

**File:** `lib/config/app_constants.dart`

```dart
// Add custom app name
static String customAppName = 'QuteCart';
```

**Update in manifests (see Step 5 above)**

### 3. Update Logo

**Replace splash screen logo:**
- `assets/png/logo.png` (splash screen)
- `assets/svg/logo.svg` (in-app logo)

**Update app icon** (see Step 7 above)

### 4. Update API Base URL

**File:** `lib/config/app_constants.dart`

**Line 3:**

```dart
// Before
static const String baseUrl = 'https://demo.readyecommerce.app/api';

// After
static const String baseUrl = 'https://qutecat.com/api';
```

### 5. Custom Fonts (Optional)

**Current:** Mulish font

**To change:**

1. Add font files to `assets/font/`
2. Update `pubspec.yaml`:

```yaml
fonts:
  - family: Inter
    fonts:
      - asset: assets/font/Inter-Regular.ttf
      - asset: assets/font/Inter-Bold.ttf
        weight: 700
```

3. Update `lib/config/theme.dart`:

```dart
fontFamily: 'Inter',  // Change from 'Mulish'
```

---

## Testing

### Unit Testing

```bash
# Run all tests
flutter test

# Run specific test
flutter test test/models/product_test.dart

# With coverage
flutter test --coverage
```

### Integration Testing

```bash
# Run integration tests
flutter test integration_test/
```

### Manual Testing Checklist

**Authentication:**
- [ ] Registration with phone/email
- [ ] OTP verification
- [ ] Login
- [ ] Password reset
- [ ] Logout

**Product Browsing:**
- [ ] View home page
- [ ] Browse categories
- [ ] Filter products
- [ ] Search products
- [ ] View product details
- [ ] Play product video
- [ ] Add to favorites

**Cart & Checkout:**
- [ ] Add products to cart
- [ ] Update quantities
- [ ] Remove from cart
- [ ] Apply voucher/coupon
- [ ] Select delivery address
- [ ] Choose payment method
- [ ] Place order

**Orders:**
- [ ] View order history
- [ ] View order details
- [ ] Track order status
- [ ] Cancel order
- [ ] Initiate return
- [ ] Add product review

**Messaging:**
- [ ] Send message to shop
- [ ] Receive real-time messages
- [ ] View message history

**Profile:**
- [ ] View profile
- [ ] Edit profile
- [ ] Change password
- [ ] Manage addresses
- [ ] Change language
- [ ] Toggle dark mode

**Push Notifications:**
- [ ] Receive order status notification
- [ ] Receive new message notification
- [ ] Notification tap navigation

---

## Deployment

### Android (Google Play Store)

**1. Prepare Release:**

```bash
# Build App Bundle
flutter build appbundle --release

# Output: build/app/outputs/bundle/release/app-release.aab
```

**2. Google Play Console:**

1. Create app in [Google Play Console](https://play.google.com/console)
2. Fill app details (name, description, screenshots)
3. Upload AAB file
4. Fill content rating questionnaire
5. Set pricing (free/paid)
6. Submit for review

**Required Assets:**
- App icon (512x512 PNG)
- Feature graphic (1024x500 PNG)
- Screenshots (at least 2, phone + tablet)
- Privacy policy URL
- Short description (80 chars)
- Full description (4000 chars)

### iOS (App Store)

**1. Prepare Release:**

```bash
# Build iOS app
flutter build ios --release

# Open in Xcode
open ios/Runner.xcworkspace

# Archive in Xcode:
# Product → Archive → Distribute App → App Store Connect
```

**2. App Store Connect:**

1. Create app in [App Store Connect](https://appstoreconnect.apple.com)
2. Fill app metadata
3. Upload screenshots (iPhone + iPad)
4. Submit for review

**Requirements:**
- Apple Developer Account ($99/year)
- App Store screenshots (6.5", 5.5" iPhones)
- App Store icon (1024x1024 PNG)
- Privacy policy URL
- Support URL

---

## Troubleshooting

### Common Issues

**1. Firebase Configuration Error**

```
Error: google-services.json not found
```

**Solution:** Ensure `google-services.json` is in `android/app/` directory.

**2. Build Fails on Android**

```
Error: Execution failed for task ':app:processReleaseResources'
```

**Solution:**
```bash
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get
flutter build apk
```

**3. iOS Pod Install Fails**

```
Error: CocoaPods not installed
```

**Solution:**
```bash
sudo gem install cocoapods
cd ios
pod install --repo-update
cd ..
```

**4. Video Not Playing**

**Check:**
- Video URL is accessible
- Video format is supported (MP4 recommended)
- Internet permission is granted

**5. Push Notifications Not Working**

**Check:**
- Firebase configuration is correct
- FCM token is being sent to backend
- Backend is sending correct notification format
- App has notification permission

**6. Real-time Messages Not Receiving**

**Check:**
- Pusher credentials are correct
- Pusher channel name matches backend
- Internet connection is stable
- App is in foreground

---

## Performance Optimization

### 1. Image Optimization

**Use cached_network_image:**

```dart
CachedNetworkImage(
  imageUrl: product.thumbnail,
  placeholder: (context, url) => Shimmer.fromColors(
    child: Container(color: Colors.grey),
  ),
  errorWidget: (context, url, error) => Icon(Icons.error),
  memCacheWidth: 300,  // Resize in memory
)
```

### 2. List Performance

**Use ListView.builder for large lists:**

```dart
ListView.builder(
  itemCount: products.length,
  itemBuilder: (context, index) {
    return ProductCard(product: products[index]);
  },
)
```

### 3. API Caching

**Hive for offline caching:**

```dart
// Cache products list
await box.put('products', jsonEncode(products));

// Retrieve from cache when offline
final cachedProducts = box.get('products');
```

### 4. Bundle Size Reduction

**Remove unused assets:**

```bash
# Analyze bundle size
flutter build apk --analyze-size

# Remove unused files from assets/
```

**Enable tree shaking:**

```yaml
# In android/app/build.gradle
buildTypes {
    release {
        shrinkResources true
        minifyEnabled true
    }
}
```

---

## Security Best Practices

### 1. API Token Security

**Never hardcode tokens:**

```dart
// ❌ Bad
static const String apiKey = 'sk_live_abc123';

// ✅ Good - Fetch from backend after login
final token = await box.get(AppConstants.authToken);
```

### 2. Keystore Security

**Keep keystore safe:**
- Never commit `keystore.jks` to Git
- Store securely in password manager
- Use different keystores for debug/release

### 3. SSL Pinning (Optional)

**For high-security apps:**

```dart
import 'package:dio/dio.dart';

final dio = Dio();
dio.interceptors.add(
  CertificatePinningInterceptor(
    allowedSHAFingerprints: [
      'sha256/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    ],
  ),
);
```

### 4. Obfuscation

**Enable code obfuscation:**

```bash
flutter build apk --obfuscate --split-debug-info=./debug-info
```

---

## Monitoring & Analytics

### Firebase Analytics (Optional)

**Track events:**

```dart
import 'package:firebase_analytics/firebase_analytics.dart';

final analytics = FirebaseAnalytics.instance;

// Track screen view
await analytics.logScreenView(
  screenName: 'ProductDetails',
);

// Track custom event
await analytics.logEvent(
  name: 'add_to_cart',
  parameters: {
    'product_id': product.id,
    'price': product.price,
  },
);
```

### Crashlytics (Optional)

**Track crashes:**

```dart
import 'package:firebase_crashlytics/firebase_crashlytics.dart';

// Initialize
FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterError;

// Log custom error
FirebaseCrashlytics.instance.recordError(error, stackTrace);
```

---

## Conclusion

The Ready eCommerce Flutter app is a **production-ready, feature-rich mobile application** that provides:

✅ **Multi-service architecture** (E-commerce, Food, Grocery, Pharmacy)
✅ **Complete shopping experience** (Browse, Cart, Checkout, Orders)
✅ **Real-time features** (Pusher messaging, FCM notifications)
✅ **Media support** (Images, videos, downloads)
✅ **Offline capabilities** (Local cart, caching)
✅ **Modern UI/UX** (Animations, dark mode, responsive)
✅ **Multi-language support**
✅ **Payment gateway integration**
✅ **Video product support** (Already implemented!)

**Next Steps:**
1. Update API base URL to your backend
2. Configure Firebase
3. Customize colors & branding (ZARA style guide available)
4. Test thoroughly
5. Build release APK/IPA
6. Deploy to app stores

---

**Document Version:** 1.0
**Last Updated:** 2025-11-06
**Maintained By:** QuteCart Development Team
