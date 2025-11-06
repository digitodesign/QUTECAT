# ZARA-Style Brand Customization Guide

**Status:** Complete Implementation Guide

**Target:** Transform QuteCart into a minimalist, sophisticated ZARA-style ecommerce platform

**Date:** 2025-11-06

**Aesthetic Goal:** Clean, minimal, black-and-white focused design with subtle accents

---

## Executive Summary

This guide provides step-by-step instructions to rebrand your QuteCart platform with ZARA's signature minimalist aesthetic. The system has a built-in theme color management system that makes customization straightforward.

**ZARA Color Palette:**
- **Primary (Black):** #000000
- **Secondary (Light Gray):** #F5F5F5
- **Accent (Off-White):** #FAFAFA
- **Subtle Gray:** #E5E5E5
- **Text:** #1A1A1A (soft black for better readability)

**Design Philosophy:**
- Minimalist and sophisticated
- High contrast (black & white)
- Clean typography
- Plenty of whitespace
- Subtle hover effects
- No vibrant colors (except for alerts/errors)

---

## Current System Architecture

### How Theme Colors Work

**1. Admin Panel (Laravel):**

The system stores theme colors in:
- **Database:** `theme_colors` table
- **CSS Files:**
  - `public/assets/css/style.css` (CSS variables)
  - `public/assets/css/login.css`
- **Dynamic Injection:** `layouts/app.blade.php` (JavaScript sets CSS variables on page load)

**2. Mobile App (Flutter):**

Colors are defined in:
- **File:** `lib/config/app_color.dart`
- **Line 89:** `static Color primary = const Color(0xFFEE456B);` (current pink)

**3. Customer Website (Vue.js):**

Website inherits colors from backend theme settings via API.

---

## Implementation Steps

### Step 1: Update Admin Panel Colors (Easiest Method)

**Option A: Use Admin Dashboard (Recommended)**

1. Log into admin panel at `/admin/login`
2. Navigate to: **Business Settings** → **Theme Colors**
3. Click "Change Color Palette" button
4. Enter ZARA black color: `#000000`
5. Click "Generate Variants" - System auto-generates 11 shades (50-950)
6. Click "Save changes"
7. The system will:
   - Update database (`theme_colors` table)
   - Update `public/assets/css/style.css`
   - Update `public/assets/css/login.css`
   - Set `--theme-color` CSS variable to `#000000`

**Option B: Database Seed (For Fresh Install)**

Create a seeder file:

**File:** `database/seeders/ZaraThemeSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\ThemeColor;
use Illuminate\Database\Seeder;

class ZaraThemeSeeder extends Seeder
{
    public function run(): void
    {
        // Delete existing theme colors
        ThemeColor::query()->delete();

        // Create ZARA-style black theme with subtle gray variants
        ThemeColor::create([
            'primary' => '#000000',           // Pure black
            'secondary' => '#F5F5F5',         // Light gray
            'variant_50' => '#FAFAFA',        // Almost white
            'variant_100' => '#F5F5F5',       // Light gray
            'variant_200' => '#E5E5E5',       // Subtle gray
            'variant_300' => '#D4D4D4',       // Medium-light gray
            'variant_400' => '#A3A3A3',       // Medium gray
            'variant_500' => '#000000',       // Base black (primary)
            'variant_600' => '#1A1A1A',       // Soft black
            'variant_700' => '#333333',       // Dark gray
            'variant_800' => '#4D4D4D',       // Medium-dark gray
            'variant_900' => '#666666',       // Gray
            'variant_950' => '#808080',       // Light gray
            'is_default' => true,
        ]);

        $this->command->info('ZARA-style theme colors seeded successfully!');
    }
}
```

**Run seeder:**

```bash
php artisan db:seed --class=ZaraThemeSeeder
```

**Then update CSS files:**

```bash
# Update style.css
php artisan tinker

# In tinker shell:
$file = public_path('assets/css/style.css');
$str = file_get_contents($file);
$str = preg_replace('/\s*--theme-color:\s*(#[a-zA-Z0-9]{6});/', '  --theme-color: #000000;', $str);
$str = preg_replace('/\s*--theme-hover-bg:\s*(#[a-zA-Z0-9]{6});/', '  --theme-hover-bg: #F5F5F5;', $str);
file_put_contents($file, $str);

# Update login.css
$file = public_path('assets/css/login.css');
$str = file_get_contents($file);
$str = preg_replace('/\s*--theme_color:\s*(#[a-zA-Z0-9]{6});/', '  --theme_color: #000000;', $str);
file_put_contents($file, $str);

exit;
```

---

### Step 2: Update Mobile App Colors

**File:** `FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode/lib/config/app_color.dart`

**Line 89 (Change from pink to black):**

```dart
// BEFORE:
static Color primary = const Color(0xFFEE456B); // Pink

// AFTER:
static Color primary = const Color(0xFF000000); // Black (ZARA style)
```

**Full ZARA-Style Color Update:**

```dart
class EcommerceAppColor {
  static const Color white = Color(0xFFFFFFFF);
  static const Color offWhite = Color(0xFFFAFAFA);        // Changed: More subtle
  static const Color black = Color(0xFF000000);            // Pure black
  static const Color gray = Color(0xFF666666);             // Darker gray
  static const Color lightGray = Color(0xFFD4D4D4);        // Lighter gray
  static Color primary = const Color(0xFF000000);          // BLACK (was pink)
  static const Color carrotOrange = Color(0xFF000000);     // Changed to black
  static const Color blueChalk = Color(0xFFF5F5F5);        // Changed to light gray
  static const Color red = Color(0xFFDC2626);              // Keep red for errors
  static const Color green = Color(0xFF16A34A);            // Keep green for success
  static const Color blue = Color(0xFF2563EB);             // Keep blue for info
  static const Color yellow = Color(0xFFF59E0B);           // Keep yellow for warnings
  static const Color orange = Color(0xFFEA580C);           // Keep orange for highlights
}
```

**Rebuild Flutter app:**

```bash
cd FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode
flutter pub get
flutter build apk  # Android
flutter build ios  # iOS (on macOS)
```

---

### Step 3: Verify CSS Variables

**File:** `public/assets/css/style.css` (Lines 1-8)

Ensure these CSS variables are set:

```css
:root {
    --body-color: #FAFAFA;              /* Off-white background */
    --theme-color: #000000;              /* Black primary */
    --theme-hover-bg: #F5F5F5;           /* Light gray hover */
    --dark-theme-color: #FFFFFF;         /* White for dark mode */
    --dark-theme-body-color: #1A1A1A;    /* Soft black for dark mode */
    --dark-border-color: #333333;        /* Dark gray borders */
}
```

---

### Step 4: Additional Styling Enhancements (Optional)

For a truly ZARA-like aesthetic, consider these additional CSS customizations:

**File:** `public/assets/css/custom-zara.css` (Create new file)

```css
/* ZARA-Style Custom Overrides */

/* Typography - Clean and minimal */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    letter-spacing: 0.02em;
    color: #1A1A1A;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 400 !important;  /* Lighter weight for elegance */
    letter-spacing: 0.03em;
    color: #000000;
}

/* Buttons - Minimal style */
.btn-primary {
    background: #000000;
    border: 1px solid #000000;
    border-radius: 0;              /* Sharp corners */
    padding: 12px 32px;
    font-weight: 400;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-size: 12px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #1A1A1A;
    border-color: #1A1A1A;
    box-shadow: none;               /* No shadow */
    transform: translateY(-1px);    /* Subtle lift */
}

.btn-outline-primary {
    color: #000000;
    border: 1px solid #000000;
    background: transparent;
    border-radius: 0;
}

.btn-outline-primary:hover {
    color: #FFFFFF;
    background: #000000;
    border-color: #000000;
}

/* Cards - Minimal borders */
.card {
    border: 1px solid #E5E5E5;
    border-radius: 0;
    box-shadow: none;
}

.card-header {
    background: #FAFAFA;
    border-bottom: 1px solid #E5E5E5;
    font-weight: 400;
    letter-spacing: 0.05em;
}

/* Forms - Clean inputs */
.form-control {
    border: 1px solid #D4D4D4;
    border-radius: 0;
    padding: 14px 16px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #000000;
    box-shadow: none;
    outline: none;
}

/* Tables - Minimal lines */
.table {
    border: none;
}

.table th {
    background: #FAFAFA;
    color: #000000;
    font-weight: 400;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    font-size: 11px;
    border: none;
    border-bottom: 1px solid #E5E5E5;
}

.table td {
    border-bottom: 1px solid #F5F5F5;
    color: #1A1A1A;
    font-size: 13px;
}

/* Sidebar - Clean navigation */
.app-sidebar {
    background: #FFFFFF;
    border-right: 1px solid #E5E5E5;
}

.menu {
    color: #666666;
    font-weight: 400;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 11px;
    transition: color 0.3s ease;
}

.menu.active {
    color: #000000;
    background: #FAFAFA;
    border-left: 2px solid #000000;
}

.menu:hover {
    color: #000000;
    background: #F5F5F5;
}

/* Product Cards - Image-first design */
.product-card {
    border: none;
    border-radius: 0;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.product-card:hover {
    transform: translateY(-4px);
}

.product-card img {
    width: 100%;
    height: auto;
    display: block;
}

.product-card .product-title {
    font-size: 14px;
    font-weight: 400;
    color: #000000;
    letter-spacing: 0.02em;
    margin-top: 12px;
}

.product-card .product-price {
    font-size: 13px;
    font-weight: 400;
    color: #666666;
    margin-top: 4px;
}

/* Dashboard Stats - Minimal cards */
.dashboard-box {
    background: #FFFFFF;
    border: 1px solid #E5E5E5;
    border-radius: 0;
    padding: 24px;
    transition: border-color 0.3s ease;
}

.dashboard-box:hover {
    border-color: #000000;
}

.dashboard-box .count {
    font-size: 32px;
    font-weight: 300;
    color: #000000;
    letter-spacing: -0.02em;
}

.dashboard-box .title {
    font-size: 12px;
    font-weight: 400;
    color: #666666;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 8px;
}

/* Remove all box shadows */
* {
    box-shadow: none !important;
}

/* Pagination - Minimal style */
.pagination .page-link {
    color: #000000;
    border: 1px solid #E5E5E5;
    border-radius: 0;
    background: #FFFFFF;
}

.pagination .page-link:hover {
    background: #000000;
    color: #FFFFFF;
    border-color: #000000;
}

.pagination .page-item.active .page-link {
    background: #000000;
    border-color: #000000;
    color: #FFFFFF;
}

/* Badges - Minimal style */
.badge {
    border-radius: 0;
    font-weight: 400;
    letter-spacing: 0.05em;
    font-size: 10px;
    text-transform: uppercase;
    padding: 4px 8px;
}

.badge-success {
    background: #FFFFFF;
    color: #16A34A;
    border: 1px solid #16A34A;
}

.badge-danger {
    background: #FFFFFF;
    color: #DC2626;
    border: 1px solid #DC2626;
}

.badge-info {
    background: #FFFFFF;
    color: #000000;
    border: 1px solid #000000;
}
```

**Include this file in `layouts/app.blade.php`:**

```blade
<!-- After existing CSS links -->
<link rel="stylesheet" href="{{ asset('assets/css/custom-zara.css') }}">
```

---

### Step 5: Update Logo and Branding Assets

**1. Create/Update Logo**

**Recommended:** Black text on transparent background, or white text for dark backgrounds

**Files to update:**
- `public/assets/images/logo.png` (Main logo)
- `public/assets/images/logo-white.png` (For dark backgrounds)
- `public/assets/images/favicon.ico` (Browser icon)
- `public/assets/images/logo-sm.png` (Small logo for mobile)

**Logo Style Guidelines:**
- Simple, clean typography
- No elaborate graphics
- Black or white only
- Plenty of negative space

**2. Update Application Name**

**File:** `.env`

```env
APP_NAME="QuteCart"  # Or your brand name
```

**File:** `config/app.php`

```php
'name' => env('APP_NAME', 'QuteCart'),
```

---

### Step 6: Typography Enhancement

**Install Better Fonts (Optional but Recommended)**

ZARA uses clean, modern sans-serif fonts. Consider:

**Option A: Update to Helvetica Neue or Inter (Already Using Inter)**

Already using Inter font (excellent choice for minimal design)

**Option B: Add Custom Font**

**File:** `resources/views/layouts/app.blade.php` (Update font import)

```html
<!-- Replace Google Fonts import with this: -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

**Update CSS:**

```css
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-weight: 400;
}

h1, h2, h3 {
    font-weight: 300;  /* Thin weight for headings */
}

h4, h5, h6 {
    font-weight: 400;  /* Regular weight */
}
```

---

### Step 7: Mobile App UI Polish (Flutter)

**Update Theme in Flutter:**

**File:** `lib/config/theme.dart`

```dart
ThemeData getAppTheme({required BuildContext context, required bool isDarkTheme}) {
  AppColor appColor = AppColorManager.getColorClass(serviceName: 'ecommerce');

  return ThemeData(
    useMaterial3: true,
    fontFamily: 'Inter',  // or 'Roboto' for cleaner look

    // Primary color scheme
    primaryColor: const Color(0xFF000000),  // Black

    // Card theme - minimal
    cardTheme: CardTheme(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.zero,  // Sharp corners
        side: BorderSide(color: Color(0xFFE5E5E5)),
      ),
    ),

    // App bar - clean
    appBarTheme: AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: Colors.black,
      elevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(
        color: Colors.black,
        fontSize: 16.sp,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.5,
      ),
    ),

    // Button theme - minimal
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.zero,  // Sharp corners
        ),
        padding: EdgeInsets.symmetric(horizontal: 32, vertical: 16),
        textStyle: TextStyle(
          fontSize: 12.sp,
          fontWeight: FontWeight.w400,
          letterSpacing: 1.5,
        ),
      ),
    ),

    // ... rest of theme
  );
}
```

---

### Step 8: Testing Checklist

After implementing changes, verify:

**Admin Panel:**
- [ ] Dashboard loads with black theme
- [ ] Buttons are black with white text
- [ ] Hover effects show subtle gray
- [ ] Sidebar navigation is clean
- [ ] Tables have minimal borders
- [ ] Forms have black focus state

**Vendor Dashboard:**
- [ ] Same as admin panel checks
- [ ] Subscription page looks good
- [ ] Product creation form is clean

**Mobile App:**
- [ ] Primary color is black throughout
- [ ] Buttons are black with white text
- [ ] Product cards are minimal
- [ ] Navigation is clean
- [ ] Checkout flow looks professional

**Customer Website:**
- [ ] Homepage hero section looks clean
- [ ] Product listings are image-first
- [ ] Product details page is minimal
- [ ] Cart/checkout is simple
- [ ] Footer is clean

---

## Comparison: Before & After

### Before (Default Pink Theme)

```
Primary Color: #EE456B (Pink)
Secondary: #FEE5E8 (Light Pink)
Style: Vibrant, playful, colorful
Buttons: Rounded, shadowed
Cards: Soft shadows, rounded corners
```

### After (ZARA Black Theme)

```
Primary Color: #000000 (Black)
Secondary: #F5F5F5 (Light Gray)
Style: Minimal, sophisticated, elegant
Buttons: Sharp corners, no shadows, flat
Cards: No shadows, minimal borders, sharp corners
```

---

## Color Palette Reference

### Primary Palette

| Color Name | Hex Code | RGB | Use Case |
|-----------|----------|-----|----------|
| Pure Black | `#000000` | `rgb(0, 0, 0)` | Primary buttons, headers, main text |
| Soft Black | `#1A1A1A` | `rgb(26, 26, 26)` | Body text, hover states |
| Dark Gray | `#333333` | `rgb(51, 51, 51)` | Secondary text |
| Medium Gray | `#666666` | `rgb(102, 102, 102)` | Labels, hints |
| Light Gray | `#D4D4D4` | `rgb(212, 212, 212)` | Borders, dividers |
| Subtle Gray | `#E5E5E5` | `rgb(229, 229, 229)` | Card borders |
| Off-Gray | `#F5F5F5` | `rgb(245, 245, 245)` | Backgrounds, hover states |
| Off-White | `#FAFAFA` | `rgb(250, 250, 250)` | Page background |
| Pure White | `#FFFFFF` | `rgb(255, 255, 255)` | Cards, content areas |

### Accent Colors (Use Sparingly)

| Color Name | Hex Code | Use Case |
|-----------|----------|----------|
| Error Red | `#DC2626` | Errors, required fields |
| Success Green | `#16A34A` | Success messages, completed states |
| Info Blue | `#2563EB` | Information, links |
| Warning Yellow | `#F59E0B` | Warnings, pending states |

---

## Advanced Customizations

### 1. Per-Vendor Theming (Premium Feature)

Allow premium vendors to customize their shop's colors:

**Database:** Already has `shops.primary_color`, `shops.secondary_color` fields

**Implementation:**

**File:** `app/Http/Middleware/SetShopContext.php`

```php
public function handle($request, Closure $next)
{
    $shop = generaleSetting('shop');

    // Inject shop colors into view
    if ($shop && $shop->primary_color) {
        view()->share('shopPrimaryColor', $shop->primary_color);
        view()->share('shopSecondaryColor', $shop->secondary_color);
    }

    return $next($request);
}
```

**File:** `resources/views/layouts/app.blade.php` (Add dynamic CSS)

```blade
@if(isset($shopPrimaryColor))
<style>
    :root {
        --theme-color: {{ $shopPrimaryColor }} !important;
        --theme-hover-bg: {{ $shopSecondaryColor }} !important;
    }
</style>
@endif
```

### 2. Dark Mode Enhancement

The system already supports dark mode. Enhance it for ZARA aesthetic:

**File:** `public/assets/css/style.css`

```css
/* Dark mode ZARA style */
.app-theme-dark {
    --body-color: #0A0A0A;
    --theme-color: #FFFFFF;  /* White primary in dark mode */
    --theme-hover-bg: #1A1A1A;
}

.app-theme-dark body {
    background: #0A0A0A;
    color: #E5E5E5;
}

.app-theme-dark .btn-primary {
    background: #FFFFFF;
    border-color: #FFFFFF;
    color: #000000;
}

.app-theme-dark .btn-primary:hover {
    background: #F5F5F5;
    border-color: #F5F5F5;
}
```

### 3. Animation Timing

ZARA-style animations are subtle and smooth:

```css
/* Smooth, elegant transitions */
* {
    transition-timing-function: cubic-bezier(0.4, 0.0, 0.2, 1);
    transition-duration: 300ms;
}

/* Hover effects */
a, button, .clickable {
    transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
}

/* Page transitions */
.page-transition {
    animation: fadeIn 0.5s cubic-bezier(0.4, 0.0, 0.2, 1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
```

---

## Rollback Instructions

If you want to revert to the original pink theme:

**Method 1: Via Admin Dashboard**

1. Go to: **Business Settings** → **Theme Colors**
2. Select the original pink color palette
3. Click "Save And Update"

**Method 2: Via Database**

```sql
UPDATE theme_colors SET is_default = false;
UPDATE theme_colors SET is_default = true WHERE primary = '#EE456B';

UPDATE general_settings SET primary_color = '#EE456B', secondary_color = '#FEE5E8';
```

**Method 3: Via Seeder**

```bash
php artisan db:seed --class=ThemeColorSeeder  # Original seeder
```

**Then update CSS files back:**

```bash
# style.css
sed -i 's/--theme-color: #000000;/--theme-color: #EE456B;/g' public/assets/css/style.css
sed -i 's/--theme-hover-bg: #F5F5F5;/--theme-hover-bg: #FEE5E8;/g' public/assets/css/style.css

# login.css
sed -i 's/--theme_color: #000000;/--theme_color: #EE456B;/g' public/assets/css/login.css
```

---

## Performance Considerations

### CSS File Size

Adding custom styles increases CSS filesize. Optimize:

```bash
# Minify CSS
npm install -g clean-css-cli
cleancss -o public/assets/css/custom-zara.min.css public/assets/css/custom-zara.css

# Update reference in app.blade.php to use .min.css
```

### Font Loading

Inter font is already being loaded. Ensure proper font-display:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

### Image Optimization

ZARA-style heavily relies on imagery:

- Use WebP format for faster loading
- Implement lazy loading
- Compress images (maintain quality at 80-85%)
- Use CDN for image delivery

---

## SEO Considerations

### Branding in Meta Tags

**File:** `resources/views/layouts/app.blade.php`

```blade
<head>
    <title>@yield('title', config('app.name')) - Minimal Luxury Fashion</title>
    <meta name="description" content="Discover minimal, sophisticated fashion at {{ config('app.name') }}. Clean design, premium quality.">
    <meta name="theme-color" content="#000000">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ config('app.name') }} - Minimal Luxury Fashion">
    <meta property="og:description" content="Clean, sophisticated ecommerce platform">
    <meta property="og:image" content="{{ asset('assets/images/og-image.png') }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }}">
</head>
```

---

## Accessibility Notes

Black-on-white has excellent contrast ratio (21:1), meeting WCAG AAA standards.

**Ensure:**
- Font size minimum 14px
- Line height 1.5 or greater
- Focus indicators visible (black outline on white, white outline on black)
- Alt text for all images
- ARIA labels for interactive elements

---

## Maintenance

### Regular Updates

1. **Monthly:** Check theme color consistency across new features
2. **Quarterly:** Review and update custom CSS
3. **Yearly:** Refresh branding assets (logo, images)

### Documentation

Keep a changelog of all branding customizations:

```
CHANGELOG.md

## [ZARA Style] - 2025-11-06
### Changed
- Primary color from #EE456B (pink) to #000000 (black)
- Button styles from rounded to sharp corners
- Card design from shadowed to flat with borders
- Typography from bold to regular weight

### Added
- custom-zara.css stylesheet
- Enhanced dark mode theme
- Minimal animation timing functions

### Removed
- Box shadows throughout UI
- Rounded corners on buttons and cards
- Vibrant accent colors
```

---

## Conclusion

This guide provides everything needed to transform QuteCart into a ZARA-style minimalist ecommerce platform. The built-in theme system makes color changes straightforward, and the optional custom CSS provides advanced styling control.

**Key Takeaways:**
1. Use admin dashboard for quick color changes
2. Update Flutter app for mobile consistency
3. Add custom CSS for advanced styling
4. Test thoroughly across all platforms
5. Maintain documentation for future updates

**Result:** A clean, sophisticated, professional-looking ecommerce platform that mirrors ZARA's minimalist aesthetic while maintaining full functionality.

---

**Document Version:** 1.0
**Last Updated:** 2025-11-06
**Next Review:** 2025-12-06
