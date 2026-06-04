<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.security_alert_title') }}</title>
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background-color: #f1f5f9; }
        .wrapper { padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { background-color: #ef4444; padding: 30px; text-align: center; color: white; }
        .content { padding: 40px; }
        .info-box { background-color: #f8fafc; border-radius: 12px; padding: 20px; margin: 25px 0; border: 1px solid #e2e8f0; }
        .info-row { margin-bottom: 10px; font-size: 14px; }
        .info-label { font-weight: 600; color: #64748b; width: 120px; display: inline-block; }
        .footer { padding: 20px 40px; background-color: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h2 style="margin:0;">{{ __('emails.security_alert_title') }}</h2>
            </div>
            <div class="content">
                <p>{{ __('emails.security_alert_greeting', ['name' => $user->name]) }}</p>
                <p>{{ __('emails.security_alert_body') }}</p>

                <div class="info-box">
                    <div class="info-row"><span class="info-label">{{ __('emails.security_alert_device') }}:</span> {{ $activity->device_type ?? __('emails.unknown') }} ({{ $activity->os ?? '' }})</div>
                    <div class="info-row"><span class="info-label">{{ __('emails.security_alert_browser') }}:</span> {{ $activity->browser ?? __('emails.unknown') }}</div>
                    <div class="info-row"><span class="info-label">{{ __('emails.security_alert_ip') }}:</span> {{ $activity->ip_address ?? __('emails.unknown') }}</div>
                    <div class="info-row"><span class="info-label">{{ __('emails.security_alert_location') }}:</span> {{ $activity->city ?? __('emails.unknown') }}, {{ $activity->country ?? __('emails.unknown') }}</div>
                    <div class="info-row"><span class="info-label">{{ __('emails.security_alert_time') }}:</span> {{ $activity->logged_at->format('M d, Y H:i:s') }} UTC</div>
                </div>

                <p>{{ __('emails.security_alert_footer') }}</p>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('emails.all_rights_reserved') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
