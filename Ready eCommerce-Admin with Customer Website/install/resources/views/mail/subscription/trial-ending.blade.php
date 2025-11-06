<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Ending Soon</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .countdown-box {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .countdown-number {
            font-size: 72px;
            font-weight: bold;
            margin: 10px 0;
        }
        .countdown-text {
            font-size: 20px;
            opacity: 0.9;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .price-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .price {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
        }
        .price-period {
            color: #7f8c8d;
            font-size: 18px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: bold;
        }
        .features ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .features li {
            padding: 8px 0;
            color: #34495e;
        }
        .features li::before {
            content: "✓ ";
            color: #27ae60;
            font-weight: bold;
            margin-right: 8px;
        }
        .email-footer {
            background-color: #f4f4f9;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>⏰ Your Trial is Ending Soon</h1>
        </div>

        <div class="email-body">
            <h2>Hi {{ $shopName }},</h2>

            <div class="countdown-box">
                <div class="countdown-number">{{ $daysRemaining }}</div>
                <div class="countdown-text">{{ $daysRemaining == 1 ? 'Day' : 'Days' }} Remaining in Your Trial</div>
            </div>

            <p>Your free trial of the <strong>{{ $planName }}</strong> plan ends on <strong>{{ $trialEndsAt }}</strong>.</p>

            <div class="info-box">
                <p style="margin: 0;"><strong>What happens next?</strong></p>
                <p style="margin: 10px 0 0;">After your trial ends, you'll be charged the regular price. Make sure your payment method is up to date to continue enjoying all the premium features!</p>
            </div>

            <div class="price-box">
                <div class="price">${{ number_format($planPrice, 2) }}</div>
                <div class="price-period">per month</div>
            </div>

            <div class="features">
                <h3 style="color: #2c3e50;">Continue enjoying:</h3>
                <ul>
                    <li>Your branded premium storefront</li>
                    <li>Expanded product and order limits</li>
                    <li>Priority customer support</li>
                    <li>Advanced analytics and insights</li>
                    <li>Custom branding options</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $billingPortalUrl }}" class="button">Manage Billing</a>
            </div>

            <p>If you're not ready to continue, you can cancel anytime before {{ $trialEndsAt }} and you won't be charged. You'll automatically be moved to the Free plan.</p>

            <p style="margin-top: 30px;">
                Questions? We're here to help!<br>
                <strong>The QuteCart Team</strong>
            </p>
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}">Dashboard</a> |
                <a href="{{ config('app.url') }}/help">Help Center</a> |
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>
