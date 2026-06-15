<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.two_factor_reminder_title') }}</title>
    <style>
        body { font-family: 'Inter', Tahoma, Arial, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; background-color: #f1f5f9; }
        .wrapper { padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #5e60ce, #6366f1); padding: 30px; text-align: center; color: white; }
        .header h2 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 8px 0 0; font-size: 14px; opacity: 0.85; }
        .content { padding: 40px; }
        .shield-icon { text-align: center; font-size: 48px; margin-bottom: 20px; }
        .cta-box { background-color: #f8fafc; border-radius: 12px; padding: 24px; margin: 25px 0; border: 1px solid #e2e8f0; text-align: center; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #5e60ce, #6366f1); color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; }
        .footer { padding: 20px 40px; background-color: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h2>{{ config('app.name') }}</h2>
                <p>{{ __('emails.two_factor_reminder_title') }}</p>
            </div>
            <div class="content">
                <div class="shield-icon">🔐</div>
                <p>{{ __('emails.two_factor_reminder_greeting', ['name' => $user->name]) }}</p>
                <p>{{ __('emails.two_factor_reminder_body') }}</p>
                <div class="cta-box">
                    <a href="{{ route('2fa.setup') }}" class="cta-button">
                        {{ __('emails.two_factor_reminder_cta') }}
                    </a>
                </div>
            </div>
            <div class="footer">
                <p>{{ __('emails.two_factor_reminder_footer') }}</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('emails.all_rights_reserved') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
