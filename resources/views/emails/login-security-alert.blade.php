<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Security Alert</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            color: #856404;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Security Alert: New Login Attempt</h2>
        <p>Hello,</p>
        <p>A new login attempt was detected for your account on {{ config('app.name') }}.</p>
        
        <div class="warning-box">
            <strong>Device:</strong> {{ $device ?? 'Unknown Device' }}<br>
            <strong>IP Address:</strong> {{ $ip ?? 'Unknown' }}
        </div>
        
        <p>If this was you, you can ignore this email. If this was not you, please secure your account immediately.</p>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
