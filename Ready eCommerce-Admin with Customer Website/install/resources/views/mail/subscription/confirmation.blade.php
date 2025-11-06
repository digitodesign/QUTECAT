<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmation</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .email-header p {
            margin: 10px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .email-body {
            padding: 40px 30px;
        }
        .email-body h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 24px;
        }
        .trial-notice {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .trial-notice h3 {
            margin: 0 0 10px;
            color: #2c3e50;
            font-size: 18px;
        }
        .trial-notice p {
            margin: 5px 0;
            color: #34495e;
        }
        .trial-days {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        .subdomain-box {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .subdomain-box h3 {
            margin: 0 0 10px;
            color: #2c3e50;
        }
        .subdomain-url {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            word-break: break-all;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: bold;
        }
        .features {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .features h3 {
            margin: 0 0 15px;
            color: #2c3e50;
        }
        .features ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .features li {
            padding: 8px 0;
            color: #34495e;
            font-size: 15px;
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
        .email-footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🎉 Welcome to {{ $planName }}!</h1>
            <p>Thank you for subscribing to QuteCart</p>
        </div>

        <div class="email-body">
            <h2>Hi {{ $shopName }},</h2>
            <p>Your subscription is now active! We're excited to have you on the <strong>{{ $planName }}</strong> plan.</p>

            @if($trialDays > 0)
            <div class="trial-notice">
                <h3>🎁 Free Trial Included</h3>
                <div class="trial-days">{{ $trialDays }} Days Free</div>
                <p>Your trial ends on <strong>{{ $trialEndsAt->format('F d, Y') }}</strong></p>
                <p>You won't be charged until your trial ends. Enjoy full access to all features!</p>
            </div>
            @endif

            @if($subdomain)
            <div class="subdomain-box">
                <h3>🌐 Your Premium Storefront is Ready!</h3>
                <p style="margin: 10px 0;">Access your branded store at:</p>
                <div class="subdomain-url">{{ $subdomain }}</div>
                <a href="https://{{ $subdomain }}" class="button">Visit Your Store</a>
            </div>
            @endif

            <div class="features">
                <h3>What's Included in Your Plan:</h3>
                <ul>
                    <li>Create up to <strong>{{ number_format($productsLimit) }}</strong> products</li>
                    <li>Process up to <strong>{{ number_format($ordersLimit) }}</strong> orders per month</li>
                    <li><strong>{{ number_format($storageLimit) }}MB</strong> of storage space</li>
                    @if($subdomain)
                    <li>Branded premium subdomain</li>
                    <li>Custom branding options</li>
                    @endif
                    <li>Priority customer support</li>
                </ul>
            </div>

            <p style="margin-top: 30px;">
                <strong>Get Started:</strong> Log in to your dashboard and start building your store. If you have any questions, our support team is here to help.
            </p>

            <p style="margin-top: 20px;">
                Happy selling! 🚀<br>
                <strong>The QuteCart Team</strong>
            </p>
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit Dashboard</a> |
                <a href="{{ config('app.url') }}/help">Help Center</a> |
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>
