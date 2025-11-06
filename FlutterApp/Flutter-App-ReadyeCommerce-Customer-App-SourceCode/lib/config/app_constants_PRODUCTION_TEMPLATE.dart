/// Production Configuration Template
///
/// Instructions:
/// 1. Copy this file and rename to: app_constants.dart
/// 2. Replace all TODO values with your actual production credentials
/// 3. Test API connection before building release APK
///
/// ⚠️ NEVER commit this file with real credentials to public repositories!

class AppConstants {
// ============================================================================
// 🔧 REQUIRED: Backend API Configuration
// ============================================================================

  /// TODO: Replace with your production Laravel API URL
  ///
  /// Format: https://YOUR_DOMAIN.com/api
  ///
  /// Examples:
  ///   - https://qutecat.com/api
  ///   - https://api.qutecat.com/api
  ///   - https://yoursite.com/ecommerce/api
  ///
  /// Find your domain in Laravel .env file (APP_URL variable)
  static const String baseUrl = 'https://YOUR_DOMAIN_HERE.com/api';  // ⚠️ CHANGE THIS!

// ============================================================================
// API Endpoints (Auto-generated from baseUrl - DO NOT MODIFY)
// ============================================================================

  static const String settings = '$baseUrl/master';
  static const String loginUrl = '$baseUrl/login';
  static const String registrationUrl = '$baseUrl/registration';
  static const String sendOTP = '$baseUrl/send-otp';
  static const String verifyOtp = '$baseUrl/verify-otp';
  static const String resetPassword = '$baseUrl/reset-password';
  static const String changePassword = '$baseUrl/change-password';
  static const String updateProfile = '$baseUrl/update-profile';
  static const String getDashboardData = '$baseUrl/home';
  static const String getCategories = '$baseUrl/categories';
  static const String getSubCategories = '$baseUrl/sub-categories';
  static const String getShops = '$baseUrl/shops';
  static const String getShopDetails = '$baseUrl/shop';
  static const String getProducts = '$baseUrl/products';
  static const String getShopCategiries = '$baseUrl/shop-categories';
  static const String getReviews = '$baseUrl/reviews';
  static const String getCategoryWiseProducts = '$baseUrl/category-products';
  static const String getProductDetails = '$baseUrl/product-details';
  static const String productFavoriteAddRemoveUrl =
      '$baseUrl/favorite-add-or-remove';
  static const String getFavoriteProducts = '$baseUrl/favorite-products';
  static const String addAddess = '$baseUrl/address/store';
  static const String address = '$baseUrl/address';
  static const String getAddress = '$baseUrl/addresses';
  static const String addToCart = '$baseUrl/cart/store';
  static const String incrementQty = '$baseUrl/cart/increment';
  static const String decrementQty = '$baseUrl/cart/decrement';
  static const String getAllCarts = '$baseUrl/carts';
  static const String getAllGifts = '$baseUrl/gifts';
  static const String addGift = '$baseUrl/gift/store';
  static const String updateGift = '$baseUrl/gift/update';
  static const String removeGift = '$baseUrl/gift/delete';
  static const String buyNow = '$baseUrl/buy-now';
  static const String cartSummery = '$baseUrl/cart/checkout';
  static const String placeOrder = '$baseUrl/place-order';
  static const String placeOrderV1 = '$baseUrl/v1/place-order';
  static const String orderAgain = '$baseUrl/place-order/again';
  static const String buyNowOrderPlace = '$baseUrl/buy-now/place-order';
  static const String getOrders = '$baseUrl/orders';
  static const String getOrderDetails = '$baseUrl/order-details';
  static const String cancelOrder = '$baseUrl/orders/cancel';
  static const String addProductReview = '$baseUrl/product-review';
  static const String getVoucher = '$baseUrl/get-vouchers';
  static const String collectVoucher = '$baseUrl/vouchers-collect';
  static const String applyVoucher = '$baseUrl/apply-voucher';
  static const String ordePayment = '$baseUrl/order-payment';
  static const String blogs = '$baseUrl/blogs';
  static const String blogDetails = '$baseUrl/blog';

  static const String privacyPolicy = '$baseUrl/legal-pages/privacy-policy';
  static const String termsAndConditions =
      '$baseUrl/legal-pages/terms-and-conditions';
  static const String refundPolicy =
      '$baseUrl/legal-pages/return-and-refund-policy';
  static const String support = '$baseUrl/support';
  static const String contactUs = '$baseUrl/contact-us';
  static const String profileinfo = '$baseUrl/profile';

  static const String logout = '$baseUrl/logout';
  static const String flashSales = '$baseUrl/flash-sales';
  static const String flashSaleDetails = '$baseUrl/flash-sale';
  static const String allCountry = '$baseUrl/countries';
  static const String storeMessage = '$baseUrl/store-message';
  static const String getMessage = '$baseUrl/get-message';
  static const String sendMessage = '$baseUrl/send-message';
  static const String getShopsList = '$baseUrl/get-shops';
  static const String unreadMessage = '$baseUrl/unread-messages';
  static const String returnOrderSubmit = '$baseUrl/return-order';
  static const String returnHistory = '$baseUrl/return-history';
  static const String returnOrdersList = '$baseUrl/return-orders';
  static const String returnOrderDetails = '$baseUrl/return-order-details';

  // dynamic url based on the service name
  static String getDashboardInfoUrl(String serviceName) =>
      '$baseUrl/api/$serviceName/store/dashoard';

// ============================================================================
// 📦 Hive Local Storage Configuration (DO NOT MODIFY)
// ============================================================================

  // Box Names
  static const String appSettingsBox = 'appSettings';
  static const String authBox = 'laundrySeller_authBox';
  static const String userBox = 'laundrySeller_userBox';
  static const String cartModelBox = 'hive_cart_model_box';

  // Settings Variable Names
  static const String firstOpen = 'firstOpen';
  static const String appLocal = 'appLocal';
  static const String isDarkTheme = 'isDarkTheme';
  static const String primaryColor = 'primaryColor';
  static const String appLogo = 'appLogo';
  static const String appName = 'appName';
  static const String splashLogo = 'splashLogo';

  // Auth Variable Names
  static const String authToken = 'token';

  // User Variable Names
  static const String userData = 'userData';
  static const String storeData = 'storeData';
  static const String cartData = 'cartData';
  static const String defaultAddress = 'defaultAddress';

// ============================================================================
// 💰 App Currency (Auto-fetched from backend - can set default here)
// ============================================================================

  static String appCurrency = "\$";  // Default currency symbol

// ============================================================================
// 🏪 Service Type Configuration
// ============================================================================

  /// Service type determines UI theme and features
  ///
  /// Supported values:
  ///   - 'ecommerce' (default) - E-commerce marketplace
  ///   - 'food'      - Food delivery
  ///   - 'grocery'   - Grocery delivery
  ///   - 'pharmacy'  - Pharmacy/medicine delivery
  ///
  /// Each service has different color themes defined in lib/config/app_color.dart
  static String appServiceName = 'ecommerce';

// ============================================================================
// 🔧 REQUIRED: Pusher Configuration (Real-time Messaging)
// ============================================================================

  /// TODO: Replace with your Pusher credentials
  ///
  /// Find these values in:
  ///   1. Laravel .env file (PUSHER_APP_KEY and PUSHER_APP_CLUSTER)
  ///   2. Or Pusher Dashboard: https://dashboard.pusher.com
  ///
  /// Steps to get credentials:
  ///   1. SSH to your production server
  ///   2. cat /path/to/laravel/.env | grep PUSHER
  ///   3. Copy PUSHER_APP_KEY value here
  ///   4. Copy PUSHER_APP_CLUSTER value here
  ///
  /// Example values:
  ///   pusherApiKey = 'a3cbadc04a202a7746fc'
  ///   pusherCluster = 'mt1'  (or 'us2', 'eu', 'ap1', etc.)
  static String pusherApiKey = 'YOUR_PUSHER_KEY_HERE';      // ⚠️ CHANGE THIS!
  static String pusherCluster = 'mt1';                      // ⚠️ CHANGE THIS if different!

  /// 💡 Common Pusher clusters:
  ///   - 'mt1'  : US (Multi-tenant)
  ///   - 'us2'  : US (Dedicated)
  ///   - 'us3'  : US (Dedicated)
  ///   - 'eu'   : Europe
  ///   - 'ap1'  : Asia Pacific (Singapore)
  ///   - 'ap2'  : Asia Pacific (Mumbai)
  ///   - 'ap3'  : Asia Pacific (Tokyo)
  ///   - 'ap4'  : Asia Pacific (Sydney)
}

/// File system type enum
enum FileSystem {
  file,    // Uploaded file (video/image stored on server)
  image,   // Image type
}

// ============================================================================
// ✅ Configuration Validation
// ============================================================================

/// Call this method on app startup to validate configuration
void validateAppConfiguration() {
  // Check baseUrl
  if (AppConstants.baseUrl.contains('YOUR_DOMAIN_HERE')) {
    throw Exception(
      '❌ ERROR: baseUrl not configured!\n'
      'Please update lib/config/app_constants.dart\n'
      'Replace "YOUR_DOMAIN_HERE.com" with your actual domain.'
    );
  }

  // Check Pusher key
  if (AppConstants.pusherApiKey.contains('YOUR_PUSHER_KEY_HERE')) {
    throw Exception(
      '❌ ERROR: Pusher API Key not configured!\n'
      'Please update lib/config/app_constants.dart\n'
      'Get your Pusher key from Laravel .env or Pusher Dashboard.'
    );
  }

  // Validate URL format
  if (!AppConstants.baseUrl.startsWith('http://') &&
      !AppConstants.baseUrl.startsWith('https://')) {
    throw Exception(
      '❌ ERROR: Invalid baseUrl format!\n'
      'baseUrl must start with http:// or https://\n'
      'Current value: ${AppConstants.baseUrl}'
    );
  }

  // Warn if using HTTP in production
  if (AppConstants.baseUrl.startsWith('http://') &&
      !AppConstants.baseUrl.contains('localhost') &&
      !AppConstants.baseUrl.contains('127.0.0.1')) {
    print('⚠️ WARNING: Using HTTP instead of HTTPS!');
    print('   Production apps should use HTTPS for security.');
  }

  print('✅ App configuration validated successfully!');
  print('   API URL: ${AppConstants.baseUrl}');
  print('   Pusher Cluster: ${AppConstants.pusherCluster}');
  print('   Service: ${AppConstants.appServiceName}');
}
