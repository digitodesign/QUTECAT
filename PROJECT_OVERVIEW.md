# QuteCart - Project Overview

**Version:** 2.0 SaaS Edition
**Status:** ✅ Production Ready
**Last Updated:** 2025-11-06

---

## 🎯 What is QuteCart?

**QuteCart** is a **multi-tenant SaaS e-commerce platform** that enables vendors to create their own online stores with subscription-based pricing tiers. Built from the Ready eCommerce template, QuteCart has been extensively customized with:

- ✅ **Multi-tenant SaaS architecture** with subdomain-based shops
- ✅ **Subscription management** with Stripe integration (Free, Starter, Growth, Enterprise plans)
- ✅ **ZARA-style minimalist branding** (black/white/gray color scheme)
- ✅ **Usage-based limits** and enforcement (products, orders, storage)
- ✅ **Automated billing** with webhooks and email notifications
- ✅ **Flutter mobile app** with production configuration
- ✅ **Product video upload** support (uploaded files + YouTube/Vimeo/Dailymotion)
- ✅ **Real-time chat** via Pusher
- ✅ **Push notifications** via Firebase
- ✅ **Docker-based development** environment

---

## 🏗️ Architecture

### Backend
- **Framework:** Laravel 11.31
- **Language:** PHP 8.2
- **Database:** PostgreSQL 16 (single database multi-tenancy)
- **Cache/Queue:** Redis 7
- **Storage:** MinIO (S3-compatible) for local, AWS S3/DigitalOcean Spaces for production
- **Real-time:** Pusher Channels
- **Payments:** Stripe API
- **Email:** Resend (production) / Mailpit (local testing)

### Frontend
- **Web:** Blade templates + Vue.js components + Tailwind CSS
- **Mobile:** Flutter 3.5+ with Dart
- **State Management:** Riverpod
- **Local Storage:** Hive (offline cart)
- **Video Player:** Chewie
- **Real-time:** Pusher Channels Flutter SDK
- **Push Notifications:** Firebase Cloud Messaging

### Infrastructure
- **Development:** Docker Compose (8 containers)
- **Production:** Ubuntu 22.04 + Nginx + Supervisor
- **SSL:** Let's Encrypt (via Certbot)
- **Domain:** qutekart.com (with wildcard subdomain support)

---

## 📦 What's Inside

### SaaS Features

**1. Subscription Plans**
- **Free Plan:** 0 products, 10 orders/month, 500MB storage
- **Starter Plan:** $29/month - 100 products, 500 orders/month, 5GB storage
- **Growth Plan:** $99/month - 1,000 products, 5,000 orders/month, 50GB storage
- **Enterprise Plan:** $299/month - Unlimited everything

**2. Multi-Tenancy**
- Subdomain-based shop isolation (e.g., `myshop.qutekart.com`)
- Context-aware API (subdomain → header → query param)
- Single database with row-level filtering
- Shared infrastructure, isolated data

**3. Stripe Integration**
- Production-ready webhook handling (6 event types)
- Automated subscription lifecycle management
- Proration on plan changes
- Trial period support
- Failed payment handling

**4. Usage Tracking & Limits**
- Real-time usage monitoring
- Automatic enforcement of limits
- Usage alerts (90% threshold)
- Admin dashboard with progress bars
- Middleware-based blocking

**5. Email Notifications**
- Subscription created
- Subscription updated/changed
- Payment succeeded
- Payment failed
- Trial ending (7 days notice)
- Subscription cancelled

**6. Product Video Support**
- **File Upload:** MP4, AVI, MOV, WMV (stored in MinIO/S3)
- **External Embeds:** YouTube, Vimeo, Dailymotion
- Vendor dashboard UI for video upload
- Mobile app video player (Chewie)
- Web video player (HTML5 + iframe)

### Customizations

**1. ZARA-Style Branding**
- Minimalist black/white/gray color palette
- Flat design (no shadows, no gradients)
- Sharp corners (no border-radius)
- Clean typography (Inter font family)
- Database-driven theme system
- Automated CSS generation

**2. Enhanced Admin Dashboard**
- Subscription management menu
- Plan badges in shop list
- Usage statistics columns
- Subscription information cards
- Usage & limits with color-coded progress bars
- Trial status indicators

**3. Mobile App Customizations**
- Production API configuration (qutekart.com)
- ZARA black primary color
- Firebase integration guides
- Pusher real-time messaging
- Offline cart persistence
- Product video support

---

## 📚 Documentation Index

### Getting Started
- **[COMPLETE_SETUP_GUIDE.md](COMPLETE_SETUP_GUIDE.md)** ⭐ **START HERE**
  - Part 1: Local development setup
  - Part 2: SaaS features configuration
  - Part 3: Branding customization
  - Part 4: Flutter mobile app setup
  - Part 5: Production deployment
  - Part 6: Testing & verification
  - Troubleshooting

- **[SETUP.md](SETUP.md)** - Quick local development setup (5 minutes)

### Architecture & Design
- **[docs/architecture/QUTECAT_HYBRID_ARCHITECTURE.md](docs/architecture/QUTECAT_HYBRID_ARCHITECTURE.md)**
  - Complete architecture documentation
  - Multi-tenancy design
  - Database schema
  - API design

- **[DOCKER_ARCHITECTURE.md](DOCKER_ARCHITECTURE.md)**
  - 8-container Docker setup
  - Service descriptions
  - Data flow diagrams
  - Volume mounts

- **[docs/CODEBASE_ORGANIZATION.md](docs/CODEBASE_ORGANIZATION.md)**
  - Directory structure
  - File organization
  - Naming conventions

### Features
- **[PRODUCTION_READY.md](PRODUCTION_READY.md)**
  - SaaS features overview
  - Subscription system details
  - Usage tracking implementation
  - Stripe webhook handling

- **[docs/features/VIDEO_UPLOAD_FEATURE.md](docs/features/VIDEO_UPLOAD_FEATURE.md)**
  - Product video upload documentation
  - Backend implementation
  - Vendor dashboard UI
  - Mobile app integration

- **[COMPATIBILITY_ANALYSIS.md](COMPATIBILITY_ANALYSIS.md)**
  - Dashboard compatibility review
  - API backward compatibility
  - Mobile app integration

### Branding
- **[docs/branding/ZARA_STYLE_CUSTOMIZATION_GUIDE.md](docs/branding/ZARA_STYLE_CUSTOMIZATION_GUIDE.md)**
  - Complete ZARA branding guide
  - Color palette
  - Typography
  - Component styles
  - Implementation methods

### Mobile App
- **[docs/mobile-app/FLUTTER_APP_SETUP_GUIDE.md](docs/mobile-app/FLUTTER_APP_SETUP_GUIDE.md)**
  - Complete Flutter app documentation
  - Architecture overview
  - Dependencies explained
  - Configuration details

- **[docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md](docs/mobile-app/PRODUCTION_CONFIGURATION_GUIDE.md)**
  - Production deployment guide
  - API configuration
  - Firebase setup
  - Pusher configuration

- **[FlutterApp/CONFIGURATION_CHECKLIST.md](FlutterApp/CONFIGURATION_CHECKLIST.md)**
  - Quick reference checklist
  - Commands summary
  - Testing procedures

- **[FlutterApp/PRODUCTION_DEPLOYMENT_STATUS.md](FlutterApp/PRODUCTION_DEPLOYMENT_STATUS.md)**
  - Current deployment status
  - Completed configurations
  - Pending manual steps

### Implementation History
- **[PHASE_1_COMPLETE.md](PHASE_1_COMPLETE.md)** - Infrastructure setup
- **[PHASE_2_COMPLETE.md](PHASE_2_COMPLETE.md)** - Subscription system
- **[SESSION_SUMMARY.md](SESSION_SUMMARY.md)** - Development session notes

---

## 🚀 Quick Start

### For Local Development (30 minutes)

```bash
# 1. Clone and navigate
cd "backend/install"

# 2. Setup environment
cp .env.example .env

# 3. Add to /etc/hosts
echo "127.0.0.1    qutekart.local" | sudo tee -a /etc/hosts

# 4. Start Docker
docker-compose up -d

# 5. Install dependencies
docker-compose exec php composer install
docker-compose exec php php artisan key:generate

# 6. Run migrations
docker-compose exec php php artisan migrate

# 7. Seed data
docker-compose exec php php artisan db:seed
docker-compose exec php php artisan db:seed --class=PlansTableSeeder
docker-compose exec php php artisan db:seed --class=ZaraThemeSeeder

# 8. Access
open http://qutekart.local
```

**Detailed instructions:** [COMPLETE_SETUP_GUIDE.md - Part 1](COMPLETE_SETUP_GUIDE.md#part-1-local-development-setup)

### For Production Deployment (2-3 hours)

See [COMPLETE_SETUP_GUIDE.md - Part 5](COMPLETE_SETUP_GUIDE.md#part-5-production-deployment)

---

## 🛠️ Tech Stack Summary

### Backend Stack
| Technology | Version | Purpose |
|------------|---------|---------|
| Laravel | 11.31 | PHP framework |
| PHP | 8.2 | Programming language |
| PostgreSQL | 16 | Primary database |
| Redis | 7 | Cache & queue |
| Nginx | Latest | Web server |
| MinIO | Latest | Local S3 storage |
| Mailpit | Latest | Email testing |
| Pusher | SDK | Real-time features |
| Stripe | API | Payment processing |
| Resend | API | Production emails |

### Frontend Stack
| Technology | Version | Purpose |
|------------|---------|---------|
| Flutter | 3.5+ | Mobile framework |
| Dart | Latest | Programming language |
| Riverpod | 2.4+ | State management |
| Hive | 2.2+ | Local database |
| Dio | 5.4+ | HTTP client |
| Firebase | Latest | Push notifications |
| Chewie | 1.9+ | Video player |
| Blade | - | Laravel templates |
| Vue.js | 3.x | Web components |
| Tailwind | 3.x | CSS framework |

---

## 🌟 Key Features

### For Platform Admins
- ✅ Manage subscription plans
- ✅ Monitor all shop subscriptions
- ✅ View usage statistics and limits
- ✅ Automated billing via Stripe
- ✅ Email notification management
- ✅ Webhook event handling
- ✅ Theme customization (ZARA branding)

### For Vendors/Shop Owners
- ✅ Create custom subdomain shop
- ✅ Choose subscription plan (Free to Enterprise)
- ✅ Upload products with images and videos
- ✅ Real-time order management
- ✅ Customer chat messaging
- ✅ Usage monitoring dashboard
- ✅ Upgrade/downgrade plans
- ✅ Trial periods

### For Customers
- ✅ Browse products on web and mobile
- ✅ Watch product videos
- ✅ Add to cart (persists offline in mobile app)
- ✅ Secure checkout with Stripe
- ✅ Real-time order tracking
- ✅ Push notifications (mobile)
- ✅ Chat with vendors
- ✅ Clean ZARA-style interface

---

## 📊 Current Status

### ✅ Completed
- [x] Multi-tenant SaaS architecture
- [x] Subscription management system
- [x] Stripe integration with webhooks
- [x] Usage tracking and enforcement
- [x] Email notification system
- [x] ZARA branding customization
- [x] Product video upload feature
- [x] Enhanced admin dashboards
- [x] Flutter mobile app setup
- [x] Production configuration guides
- [x] Docker development environment
- [x] Complete documentation

### 🔄 Production Deployment Pending
- [ ] Server setup and configuration
- [ ] Domain DNS configuration
- [ ] SSL certificate installation
- [ ] Stripe production webhook setup
- [ ] Flutter app Firebase configuration
- [ ] Flutter app Pusher configuration
- [ ] App store deployment (Google Play / App Store)

**See:** [FlutterApp/PRODUCTION_DEPLOYMENT_STATUS.md](FlutterApp/PRODUCTION_DEPLOYMENT_STATUS.md) for detailed status.

---

## 🎨 Branding

### Color Palette (ZARA Style)
- **Primary:** #000000 (Pure Black)
- **Secondary:** #F5F5F5 (Light Gray)
- **Grayscale Variants:** 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950

### Design Principles
- Minimalist and clean
- Flat design (no shadows)
- Sharp corners (no rounded borders)
- High contrast (black on white)
- Generous white space
- Clean typography (Inter font)

---

## 🔐 Security Features

- ✅ HTTPS/SSL enforcement
- ✅ CSRF protection
- ✅ XSS protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Authentication & authorization
- ✅ Rate limiting
- ✅ Secure file upload validation
- ✅ Environment variable configuration
- ✅ Webhook signature verification (Stripe)
- ✅ Password hashing (bcrypt)

---

## 📱 Access Points

### Local Development
- **Main Site:** http://qutekart.local
- **Admin Panel:** http://qutekart.local/admin
- **API:** http://qutekart.local/api
- **MinIO Console:** http://localhost:9001 (minioadmin/minioadmin)
- **Mailpit:** http://localhost:8025 (email testing)
- **PostgreSQL:** localhost:5432 (qutekart/secret)

### Production
- **Main Site:** https://qutekart.com
- **Admin Panel:** https://qutekart.com/admin
- **API:** https://qutekart.com/api
- **Shop Subdomain:** https://{shopname}.qutekart.com

---

## 🧪 Testing

### Backend Tests
```bash
# Run all tests
docker-compose exec php php artisan test

# Run specific test
docker-compose exec php php artisan test --filter SubscriptionTest

# With coverage
docker-compose exec php php artisan test --coverage
```

### Frontend Tests
```bash
# Flutter tests
cd FlutterApp/Flutter-App-ReadyeCommerce-Customer-App-SourceCode
flutter test

# Widget tests
flutter test test/widget_test.dart

# Integration tests
flutter test integration_test/
```

---

## 🤝 Contributing

### Development Workflow
1. Create feature branch: `git checkout -b feature/your-feature`
2. Make changes and test locally
3. Commit with clear messages
4. Push and create pull request
5. Code review and merge

### Code Standards
- **PHP:** PSR-12 coding standard
- **Laravel:** Follow Laravel best practices
- **Dart/Flutter:** Follow Effective Dart guidelines
- **Git:** Conventional commits format

---

## 📞 Support & Resources

### Documentation
- All guides in `docs/` directory
- Inline code comments
- README files in each major directory

### External Resources
- **Laravel Docs:** https://laravel.com/docs
- **Flutter Docs:** https://flutter.dev/docs
- **Stripe Docs:** https://stripe.com/docs
- **Pusher Docs:** https://pusher.com/docs
- **Firebase Docs:** https://firebase.google.com/docs

### Troubleshooting
See [COMPLETE_SETUP_GUIDE.md - Troubleshooting](COMPLETE_SETUP_GUIDE.md#troubleshooting)

---

## 📈 Roadmap (Potential Future Features)

- [ ] Multi-language support (i18n)
- [ ] Multi-currency support
- [ ] Advanced analytics dashboard
- [ ] Automated marketing tools
- [ ] Inventory management
- [ ] Shipping integrations
- [ ] Tax calculation automation
- [ ] Customer loyalty programs
- [ ] Referral system
- [ ] API rate limiting per plan
- [ ] White-label options

---

## 📄 License

[Add your license information here]

---

## 🙏 Credits

Built on top of:
- **Ready eCommerce** template
- Laravel framework by Taylor Otwell
- Flutter framework by Google
- Stripe payment processing
- Pusher real-time messaging
- And many open-source packages (see `composer.json` and `pubspec.yaml`)

---

## 📝 Version History

### v2.0 - SaaS Edition (2025-11-06)
- ✅ Multi-tenant SaaS architecture
- ✅ Subscription management with Stripe
- ✅ ZARA-style branding
- ✅ Product video upload
- ✅ Enhanced admin dashboards
- ✅ Flutter app production configuration
- ✅ Complete documentation

### v1.0 - Original Template
- Ready eCommerce multi-vendor marketplace
- Basic product management
- Order management
- Customer accounts

---

**Last Updated:** 2025-11-06
**Maintained by:** QuteCart Development Team
**Status:** ✅ Production Ready
