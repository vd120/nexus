<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; line-height: 1.8; color: #1e293b; margin: 0; padding: 20px; background-color: #f1f5f9; }
        .container { max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #5e60ce, #6366f1); padding: 24px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
        .content { padding: 32px; }
        .greeting { font-size: 17px; font-weight: 700; margin-bottom: 16px; }
        .message { font-size: 15px; color: #334155; line-height: 2; }
        .footer { padding: 16px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h1>{{ config('app.name') }}</h1></div>
        <div class="content">
            <div class="greeting">{{ __('emails.inactive_greeting', ['name' => $user->name]) }}</div>
            <div class="message">{!! nl2br(e(__('emails.inactive_message', ['app' => config('app.name')]))) !!}</div>
        </div>
        <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('emails.all_rights_reserved') }}</div>
    </div>
</body>
</html>
