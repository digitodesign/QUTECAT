# Resend Email Configuration Guide

**QuteCart Email Delivery via Resend**

Resend is the email delivery service for QuteCart. This guide will help you configure it.

---

## Why Resend?

✅ **99.9% Deliverability** - Industry-leading email delivery rates
✅ **Developer-Friendly** - Simple API, excellent Laravel integration
✅ **Real-Time Analytics** - Track opens, clicks, bounces, complaints
✅ **Free Tier** - 3,000 emails/month free (perfect for getting started)
✅ **Fast Delivery** - Sub-second email delivery worldwide
✅ **Dedicated IPs** - Available for high-volume senders

---

## Step 1: Create Resend Account

1. Go to [https://resend.com](https://resend.com)
2. Click "Start Building" or "Sign Up"
3. Sign up with your email or GitHub
4. Verify your email address

---

## Step 2: Get API Key

1. Log in to [Resend Dashboard](https://resend.com/dashboard)
2. Go to **API Keys** in the sidebar
3. Click **Create API Key**
4. Name it: `QuteCart Production` (or `QuteCart Development` for testing)
5. Select permissions: **Sending access** (Full access for production)
6. Click **Create**
7. **Copy the API key** (shown only once!)

Example API key format: `re_123abc456def789ghi012jkl345mno678`

---

## Step 3: Verify Domain (Production)

For production, verify your sending domain to improve deliverability and remove "via resend.dev" from emails.

1. Go to **Domains** in Resend Dashboard
2. Click **Add Domain**
3. Enter your domain: `qutekart.com` (or your custom domain)
4. Click **Add**

You'll see DNS records to add:

### DNS Records to Add

**SPF Record** (TXT)
```
Host: @
Value: v=spf1 include:resend.net ~all
```

**DKIM Record** (TXT)
```
Host: resend._domainkey
Value: [provided by Resend - looks like: p=MIGfMA0GC...]
```

**DMARC Record** (TXT)
```
Host: _dmarc
Value: v=DMARC1; p=none; rua=mailto:dmarc@yourdomain.com
```

**Return-Path (Optional)**
```
Host: bounces
Value: [CNAME value provided by Resend]
```

5. Add these records in your domain's DNS settings (Cloudflare, Namecheap, GoDaddy, etc.)
6. Wait 5-15 minutes for DNS propagation
7. Click **Verify** in Resend Dashboard

✅ When verified, you'll see a green checkmark

---

## Step 4: Configure Laravel

### Environment Variables

Add to your `.env` file:

```env
# Resend Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=re_123abc456def789ghi012jkl345mno678
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@qutekart.com
MAIL_FROM_NAME="${APP_NAME}"

# Alternative: Use Resend API directly (recommended)
RESEND_API_KEY=re_123abc456def789ghi012jkl345mno678
```

### Option 1: SMTP (Simpler)

Use the SMTP settings above. Laravel's built-in mail system will work immediately.

### Option 2: Resend API (Recommended)

For better performance and features, use Resend's native Laravel integration:

**Install Resend Package:**
```bash
composer require resend/resend-php
composer require resend/resend-laravel
```

**Publish Config:**
```bash
php artisan vendor:publish --tag=resend-config
```

**Update `config/mail.php`:**
```php
'mailers' => [
    'resend' => [
        'transport' => 'resend',
    ],
],

'default' => env('MAIL_MAILER', 'resend'),
```

**Update `.env`:**
```env
MAIL_MAILER=resend
RESEND_API_KEY=re_123abc456def789ghi012jkl345mno678
MAIL_FROM_ADDRESS=no-reply@qutekart.com
MAIL_FROM_NAME="QuteCart"
```

---

## Step 5: Test Email Sending

### Quick Test (Artisan Tinker)

```bash
php artisan tinker
```

```php
Mail::raw('Test email from QuteCart!', function ($message) {
    $message->to('your-email@example.com')
            ->subject('QuteCart Test Email');
});
```

Check your inbox. You should receive the test email within seconds.

### Test Subscription Email

```php
use App\Models\Subscription;
use App\Mail\Subscription\SubscriptionConfirmation;

$subscription = Subscription::with('shop', 'plan')->first();

Mail::to('your-email@example.com')->send(
    new SubscriptionConfirmation($subscription)
);
```

---

## Step 6: Monitor Email Delivery

### Resend Dashboard

1. Go to **Emails** in Resend Dashboard
2. See real-time email delivery status:
   - ✅ **Delivered** - Email successfully delivered
   - 📬 **Opened** - Recipient opened email
   - 🔗 **Clicked** - Recipient clicked link
   - ❌ **Bounced** - Email bounced (invalid address)
   - 🚫 **Complained** - Marked as spam

### Laravel Logs

All email sending is logged in `storage/logs/laravel.log`:

```
[2025-11-06 12:34:56] INFO: Subscription confirmation email sent
  subscription_id: 123
  vendor_email: vendor@example.com
  plan: Starter
```

---

## Email Types Configured

QuteCart sends these automated emails via Resend:

| Email Type | Trigger | Template |
|------------|---------|----------|
| **Subscription Confirmation** | Vendor subscribes to paid plan | `mail.subscription.confirmation` |
| **Payment Failed** | Payment declines | `mail.subscription.payment-failed` |
| **Trial Ending** | 3 days before trial ends | `mail.subscription.trial-ending` |
| **Limit Warning** | Usage reaches 80%, 90%, 100% | `mail.subscription.limit-warning` |

All emails are:
- ✅ Mobile-responsive
- ✅ Beautifully designed
- ✅ Queued for performance (async sending)
- ✅ Logged for debugging

---

## Pricing

### Resend Pricing (as of 2025)

**Free Tier:**
- 3,000 emails/month
- 100 emails/day
- All features included
- Perfect for testing and small deployments

**Pro Plan:** $20/month
- 50,000 emails/month
- Unlimited domains
- Priority support
- Advanced analytics

**Enterprise:** Custom pricing
- Custom volume
- Dedicated IPs
- 99.99% SLA
- Dedicated support

**Calculate your needs:**
- Average: 100 vendors × 5 emails/month = 500 emails
- Growth: 500 vendors × 10 emails/month = 5,000 emails
- Scale: 2,000 vendors × 15 emails/month = 30,000 emails

**Recommendation:** Start with Free tier, upgrade to Pro when you hit 100+ active vendors.

---

## Best Practices

### 1. **Use Verified Domain**
- Don't use @gmail.com or @yahoo.com for `MAIL_FROM_ADDRESS`
- Use your verified domain: `no-reply@qutekart.com`
- Improves deliverability and removes "via resend.dev"

### 2. **Queue Emails**
All QuteCart email listeners use `ShouldQueue` - ensure queues are running:

```bash
# Development
php artisan queue:work

# Production (supervisor)
# See docs/deployment/PRODUCTION_SETUP.md
```

### 3. **Monitor Bounce Rates**
- Check Resend Dashboard weekly
- Bounce rate > 5% = problem (bad email list)
- Remove bounced emails from database

### 4. **Respect Unsubscribes**
- Resend automatically handles "List-Unsubscribe" headers
- Honor unsubscribe requests immediately
- Add unsubscribe preference to vendor settings

### 5. **Test Before Sending**
Always test emails in development:

```bash
# Use Mailpit for local testing
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

View emails at: http://localhost:8025

---

## Troubleshooting

### Emails Not Sending

**Check queue is running:**
```bash
php artisan queue:work --verbose
```

**Check logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "email\|mail"
```

**Test SMTP connection:**
```bash
php artisan tinker

use Illuminate\Support\Facades\Mail;
Mail::raw('test', function($m) { $m->to('your@email.com')->subject('test'); });
```

### Emails Going to Spam

1. ✅ **Verify domain** in Resend Dashboard
2. ✅ **Add SPF/DKIM/DMARC** DNS records
3. ✅ **Use verified sender** email address
4. ✅ **Avoid spam trigger words** in subject lines
5. ✅ **Include unsubscribe** link (automatic with Resend)
6. ✅ **Maintain low bounce rate** (< 5%)

### API Key Not Working

- Regenerate key in Resend Dashboard
- Check for extra spaces in `.env` file
- Ensure `RESEND_API_KEY` starts with `re_`
- Clear config cache: `php artisan config:clear`

---

## Development vs Production

### Development

```env
# Use Mailpit (no actual emails sent)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_ENCRYPTION=null
```

Access at: http://localhost:8025

### Staging

```env
# Use Resend with test API key
MAIL_MAILER=resend
RESEND_API_KEY=re_test_123...
MAIL_FROM_ADDRESS=staging@qutekart.com
```

### Production

```env
# Use Resend with production API key and verified domain
MAIL_MAILER=resend
RESEND_API_KEY=re_prod_456...
MAIL_FROM_ADDRESS=no-reply@qutekart.com
MAIL_FROM_NAME="QuteCart"
```

---

## Next Steps

1. ✅ Create Resend account
2. ✅ Get API key
3. ✅ Update `.env` file
4. ✅ Send test email
5. ✅ Verify domain (production only)
6. ✅ Monitor dashboard
7. ✅ Set up queue workers

---

## Support

- **Resend Docs:** https://resend.com/docs
- **Resend Support:** support@resend.com
- **QuteCart Email Issues:** Check `storage/logs/laravel.log`

---

**Email system ready!** 🚀📧
