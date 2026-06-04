<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.verification_code_prompt') }}</title>
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
        .header {
            margin-bottom: 20px;
        }
        .code-box {
            background-color: #f0f7ff;
            border: 1px solid #cce3ff;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
            margin: 20px 0;
            border-radius: 4px;
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
        <div class="header">
            <h2>{{ __('emails.verification_code_welcome', ['app' => config('app.name')]) }}</h2>
        </div>

        <p>{{ __('emails.verification_code_prompt') }}</p>

        <div class="code-box">
            {{ $verificationCode }}
        </div>

        <p>{{ __('emails.verification_code_notice') }}</p>
        <p>{{ __('emails.verification_code_expire') }}</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('emails.all_rights_reserved') }}</p>
        </div>
    </div>
</body>
</html>
