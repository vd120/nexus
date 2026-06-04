<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.password_reset_title') }}</title>
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
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
        <h2>{{ __('emails.password_reset_title') }}</h2>
        <p>{{ __('emails.password_reset_greeting') }}</p>
        <p>{{ __('emails.password_reset_body') }}</p>

        <div style="text-align: center;">
            <a href="{{ $url }}" class="btn">{{ __('emails.password_reset_button') }}</a>
        </div>

        <p>{{ __('emails.password_reset_expire') }}</p>
        <p>{{ __('emails.password_reset_ignore') }}</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('emails.all_rights_reserved') }}</p>
        </div>
    </div>
</body>
</html>
