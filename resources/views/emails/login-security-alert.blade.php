<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.login_notification_subject', ['app_name' => config('app.name')]) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f9fafb; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
        .header { background: linear-gradient(135deg, {{ $activity->is_suspicious ? '#ef4444' : '#10b981' }}, {{ $activity->is_suspicious ? '#dc2626' : '#059669' }}); padding: 24px; text-align: center; }
        .alert-box { background: {{ $activity->is_suspicious ? 'rgba(239, 68, 68, 0.1)' : 'rgba(59, 130, 246, 0.1)' }}; border-left: 4px solid {{ $activity->is_suspicious ? '#ef4444' : '#3b82f6' }}; padding: 16px; margin-bottom: 20px; border-radius: 8px; }
        .alert-box p { color: {{ $activity->is_suspicious ? '#dc2626' : '#2563eb' }}; font-size: 14px; font-weight: 600; margin: 0; }
        .security-tips { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .security-tips h3 { color: #92400e; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .security-tips ul { list-style: none; padding: 0; }
        .security-tips li { color: #78350f; font-size: 13px; padding: 6px 0; padding-left: 24px; position: relative; }
        .security-tips li::before { content: '✓'; position: absolute; left: 0; color: #059669; font-weight: 700; }
        .header p { color: rgba(255, 255, 255, 0.95); font-size: 14px; }
        .header h1 { color: #ffffff; font-size: 22px; margin-bottom: 6px; font-weight: 600; }
        .content { padding: 24px; }
        .greeting { font-size: 16px; color: #1f2937; margin-bottom: 16px; }
        .message { font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 24px; }
        .info-section { margin-bottom: 24px; }
        .info-section h2 { color: #374151; font-size: 16px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .info-item { background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; }
        .info-item label { color: #6b7280; font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; display: block; letter-spacing: 0.5px; }
        .info-item value { color: #1f2937; font-size: 14px; font-weight: 500; display: block; }
        .full-width { grid-column: 1 / -1; }
        .map-link { color: #059669; text-decoration: none; font-weight: 500; }
        .map-link:hover { text-decoration: underline; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #6b7280; font-size: 13px; line-height: 1.6; }
        .footer a { color: #10b981; text-decoration: none; }
        .btn { display: inline-block; padding: 10px 20px; background: #10b981; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 12px; font-size: 14px; }
        .btn:hover { background: #059669; }
        
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .header { padding: 20px; }
            .content { padding: 16px; }
        }
        
        [dir="rtl"] { text-align: right; }
        [dir="rtl"] .info-item { text-align: right; }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>{{ __('emails.login_notification_title') }}</h1>
            <p>{{ __('emails.login_notification_subtitle') }}</p>
        </div>

        {{-- Content --}}
        <div class="content">
            {{-- Alert Box for Suspicious Logins --}}
            @if($activity->is_suspicious)
            <div class="alert-box">
                <p>⚠️ {{ __('emails.suspicious_login_alert') }}</p>
            </div>
            @endif

            {{-- Greeting --}}
            <div class="greeting">
                <strong>{{ __('emails.hello') }} {{ $userName }},</strong>
            </div>

            <p class="message">{{ __('emails.login_notification_message') }}</p>

            {{-- Login Information --}}
            <div class="info-section">
                <h2>{{ __('emails.login_details') }}</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>{{ __('emails.ip_address') }} : </label>
                        <value>{{ $activity->ip_address }}</value>
                    </div>
                    
                    @if($activity->isp)
                    <div class="info-item">
                        <label>{{ __('emails.isp') }} : </label>
                        <value>{{ $activity->isp }}</value>
                    </div>
                    @endif
                    
                    @if($activity->country || $activity->city)
                    <div class="info-item full-width">
                        <label>{{ __('emails.location') }} : </label>
                        <value>{{ $activity->city ?? '' }}{{ $activity->city && $activity->region ? ', ' : '' }}{{ $activity->region ?? '' }}{{ ($activity->city || $activity->region) && $activity->country ? ', ' : '' }}{{ $activity->country ?? '' }}</value>
                    </div>
                    @endif
                    
                    @if($activity->latitude && $activity->longitude)
                    <div class="info-item full-width">
                        <label>{{ __('emails.coordinates') }} : </label>
                        <value>
                            <a href="https://www.google.com/maps?q={{ $activity->latitude }},{{ $activity->longitude }}" class="map-link" target="_blank">
                                {{ number_format($activity->latitude, 4) }}, {{ number_format($activity->longitude, 4) }}
                            </a>
                        </value>
                    </div>
                    @endif
                    
                    <div class="info-item">
                        <label>{{ __('emails.device_type') }} : </label>
                        <value>{{ ucfirst($activity->device_type ?? __('emails.unknown')) }}</value>
                    </div>
                    <div class="info-item">
                        <label>{{ __('emails.browser') }} : </label>
                        <value>{{ $activity->browser }}</value>
                    </div>
                    <div class="info-item">
                        <label>{{ __('emails.operating_system') }} : </label>
                        <value>{{ $activity->os }}</value>
                    </div>
                    <div class="info-item">
                        <label>{{ __('emails.login_time') }} : </label>
                        <value>{{ $activity->logged_at->format('Y-m-d h:i a') }}</value>
                    </div>
                    
                    @if($activity->timezone)
                    <div class="info-item full-width">
                        <label>{{ __('emails.timezone') }} : </label>
                        <value>{{ $activity->timezone }} @if($activity->logged_at)({{ $activity->logged_at->timezone(str_replace('_', '/', $activity->timezone))->format('h:i a') }})@endif</value>
                    </div>
                    @endif
                </div>
            </div>

            {{-- CTA Button --}}
            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ route('activity.index') }}" class="btn">{{ __('emails.view_activity_logs') }}</a>
            </div>

            {{-- Security Tips for Suspicious Logins --}}
            @if($activity->is_suspicious)
            <div class="security-tips">
                <h3>{{ __('emails.security_recommendations') }}</h3>
                <ul>
                    <li>{{ __('emails.security_tip_change_password') }}</li>
                    <li>{{ __('emails.security_tip_enable_2fa') }}</li>
                    <li>{{ __('emails.security_tip_review_sessions') }}</li>
                    <li>{{ __('emails.security_tip_never_share_password') }}</li>
                </ul>
            </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ __('emails.footer_notification_text') }}<br>
                {{ __('emails.footer_ignore') }}
            </p>
            <p style="margin-top: 12px;">
                <a href="{{ route('home') }}">{{ config('app.name') }}</a>
            </p>
        </div>
    </div>
</body>
</html>
