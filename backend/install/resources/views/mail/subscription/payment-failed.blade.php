<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
        .alert-box {
            background-color: #ffebee;
            border-left: 4px solid #e74c3c;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box h3 {
            margin: 0 0 10px;
            color: #c0392b;
        }
        .payment-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .payment-details p {
            margin: 8px 0;
            color: #34495e;
        }
        .payment-details strong {
            color: #2c3e50;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: bold;
        }
        .steps {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .steps h3 {
            margin: 0 0 15px;
            color: #2c3e50;
        }
        .steps ol {
            padding-left: 20px;
            color: #34495e;
        }
        .steps li {
            margin: 10px 0;
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
            <h1>⚠️ Payment Failed</h1>
        </div>

        <div class="email-body">
            <h2>Hi {{ $shopName }},</h2>

            <div class="alert-box">
                <h3>Action Required</h3>
                <p>We were unable to process your payment for your {{ $planName }} subscription.</p>
            </div>

            <div class="payment-details">
                <h3 style="margin-top: 0;">Payment Details:</h3>
                <p><strong>Amount:</strong> ${{ number_format($amount, 2) }} {{ $currency }}</p>
                <p><strong>Plan:</strong> {{ $planName }}</p>
                <p><strong>Attempt #:</strong> {{ $attemptCount }}</p>
                @if($nextAttemptDate)
                <p><strong>Next Retry:</strong> {{ $nextAttemptDate }}</p>
                @endif
            </div>

            <p>Don't worry - this can happen for several reasons:</p>
            <ul>
                <li>Insufficient funds</li>
                <li>Expired or invalid card</li>
                <li>Card security settings blocking the charge</li>
                <li>Billing address mismatch</li>
            </ul>

            <div class="steps">
                <h3>What to Do Next:</h3>
                <ol>
                    <li>Click the button below to update your payment method</li>
                    <li>Verify your card details are correct</li>
                    <li>Make sure you have sufficient funds</li>
                    <li>Contact your bank if the issue persists</li>
                </ol>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $billingPortalUrl }}" class="button">Update Payment Method</a>
            </div>

            @if($attemptCount >= 3)
            <div class="alert-box">
                <h3>⏰ Urgent: Multiple Failed Attempts</h3>
                <p>This is attempt #{{ $attemptCount }}. To avoid service interruption, please update your payment method as soon as possible.</p>
            </div>
            @endif

            <p style="margin-top: 30px;">
                If you need help, our support team is here for you. Reply to this email or visit our help center.
            </p>

            <p style="margin-top: 20px;">
                Best regards,<br>
                <strong>The QuteCart Team</strong>
            </p>
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}/help">Help Center</a> |
                <a href="{{ config('app.url') }}/contact">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>
