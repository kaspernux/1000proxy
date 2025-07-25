<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .subject {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        .admin-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .customer-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌐 1000 PROXIES</div>
            <span class="admin-badge">Official Notification</span>
        </div>

        <div class="subject">
            📢 {{ $subject }}
        </div>

        <div class="greeting">
            Hello {{ $customer->name }},
        </div>

        <div class="message-content">
            {!! nl2br(e($messageContent)) !!}
        </div>

        <div class="customer-info">
            <strong>Account Information:</strong><br>
            <strong>Name:</strong> {{ $customer->name }}<br>
            <strong>Email:</strong> {{ $customer->email }}<br>
            <strong>Customer ID:</strong> #{{ $customer->id }}<br>
            <strong>Account Status:</strong> {{ $customer->is_active ? '✅ Active' : '❌ Inactive' }}
        </div>

        <div class="footer">
            <p>This is an official notification from 1000 Proxies administration.</p>
            <p>
                <strong>Need assistance?</strong><br>
                📧 Contact us at: support@1000proxies.com<br>
                💬 Join our Telegram: @1000ProxiesSupport<br>
                🌐 Visit our website: <a href="{{ config('app.url') }}">1000proxies.com</a>
            </p>
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                You are receiving this email because you are a registered customer of 1000 Proxies.<br>
                If you believe this email was sent in error, please contact our support team.
            </p>
        </div>
    </div>
</body>
</html>
