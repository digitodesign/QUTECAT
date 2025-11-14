<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $limitType }} Limit {{ $isAtLimit ? 'Reached' : 'Warning' }}</title>
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
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
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
        .usage-box {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background-color: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff9800 0%, #f57c00 100%);
            transition: width 0.3s ease;
        }
        .progress-text {
            text-align: center;
            margin: 10px 0;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .usage-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat {
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .alert-box {
            background-color: {{ $isAtLimit ? '#ffebee' : '#fff3e0' }};
            border-left: 4px solid {{ $isAtLimit ? '#e74c3c' : '#ff9800' }};
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box h3 {
            margin: 0 0 10px;
            color: {{ $isAtLimit ? '#c0392b' : '#f57c00' }};
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
        .plans-comparison {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .plan-box {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 10px 0;
            text-align: center;
        }
        .plan-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .plan-limit {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
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
            <h1>{{ $isAtLimit ? '🚫' : '⚠️' }} {{ $limitType }} Limit {{ $isAtLimit ? 'Reached' : 'Warning' }}</h1>
        </div>

        <div class="email-body">
            <h2>Hi {{ $shopName }},</h2>

            <div class="alert-box">
                <h3>{{ $isAtLimit ? 'Limit Reached' : 'Approaching Limit' }}</h3>
                <p>
                    @if($isAtLimit)
                        You've reached your {{ strtolower($limitType) }} limit on your <strong>{{ $planName }}</strong> plan.
                        You won't be able to {{ $action }} until you upgrade your plan.
                    @else
                        You've used <strong>{{ $percentUsed }}%</strong> of your {{ strtolower($limitType) }} limit on your <strong>{{ $planName }}</strong> plan.
                    @endif
                </p>
            </div>

            <div class="usage-box">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ min($percentUsed, 100) }}%;"></div>
                </div>
                <div class="progress-text">{{ $percentUsed }}% Used</div>

                <div class="usage-stats">
                    <div class="stat">
                        <div class="stat-number">{{ number_format($current) }}</div>
                        <div class="stat-label">Current</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">{{ number_format($limit) }}</div>
                        <div class="stat-label">Limit</div>
                    </div>
                </div>

                <p style="text-align: center; color: #7f8c8d; margin: 0;">
                    {{ number_format($current) }} / {{ number_format($limit) }} {{ $unit }}
                </p>
            </div>

            @if($isAtLimit)
                <p><strong>What this means:</strong></p>
                <ul>
                    <li>You cannot {{ $action }} on your current plan</li>
                    <li>Your existing {{ strtolower($limitType) }} will continue to work normally</li>
                    <li>Upgrade to a higher plan to increase your limits</li>
                </ul>
            @else
                <p><strong>What happens when you reach 100%?</strong></p>
                <ul>
                    <li>You won't be able to {{ $action }}</li>
                    <li>Your existing {{ strtolower($limitType) }} will continue to work</li>
                    <li>Consider upgrading before reaching your limit</li>
                </ul>
            @endif

            <div class="plans-comparison">
                <h3 style="margin-top: 0; text-align: center;">Upgrade to Get More</h3>
                <p style="text-align: center; color: #7f8c8d;">All plans include everything in {{ $planName }}, plus higher limits:</p>

                <div style="margin: 20px 0; text-align: center;">
                    <a href="{{ $upgradeUrl }}" class="button">View Upgrade Options</a>
                </div>
            </div>

            <p style="margin-top: 30px;">
                Questions about upgrading? Our team is happy to help you choose the right plan for your needs.
            </p>

            <p style="margin-top: 20px;">
                Best regards,<br>
                <strong>The QuteCart Team</strong>
            </p>
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}">Dashboard</a> |
                <a href="{{ $upgradeUrl }}">Upgrade Plan</a> |
                <a href="{{ config('app.url') }}/help">Help Center</a>
            </p>
        </div>
    </div>
</body>
</html>
