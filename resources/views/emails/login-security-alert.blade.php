<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Security Alert</title>
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background-color: #f1f5f9; }
        .wrapper { padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { background-color: #ef4444; padding: 30px; text-align: center; color: white; }
        .content { padding: 40px; }
        .info-box { background-color: #f8fafc; border-radius: 12px; padding: 20px; margin: 25px 0; border: 1px solid #e2e8f0; }
        .info-row { margin-bottom: 10px; font-size: 14px; }
        .info-label { font-weight: 600; color: #64748b; width: 100px; display: inline-block; }
        .footer { padding: 20px 40px; background-color: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">Security Alert: Suspicious Login</h2>
            </div>
            <div class="content">
                <p>Hello <strong>{{ $user->name }}</strong>,</p>
                <p>We detected a login attempt that looks suspicious compared to your usual activity. As a security measure, we have temporarily blocked this access until it is verified.</p>
                
                <div class="info-box">
                    <div class="info-row"><span class="info-label">Device:</span> {{ $activity->device_type ?? 'Unknown' }} ({{ $activity->os ?? '' }})</div>
                    <div class="info-row"><span class="info-label">Browser:</span> {{ $activity->browser ?? 'Unknown' }}</div>
                    <div class="info-row"><span class="info-label">IP Address:</span> {{ $activity->ip_address ?? 'Unknown' }}</div>
                    <div class="info-row"><span class="info-label">Location:</span> {{ $activity->city ?? 'Unknown' }}, {{ $activity->country ?? 'Unknown' }}</div>
                    <div class="info-row"><span class="info-label">Time:</span> {{ $activity->logged_at->format('M d, Y H:i:s') }} UTC</div>
                </div>
                
                <p>If this was you, please complete the verification on the login page. If you did not attempt to login, we recommend changing your password immediately.</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
