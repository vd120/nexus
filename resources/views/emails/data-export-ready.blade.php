<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f0f1a;color:#e2e8f0;margin:0;padding:2rem 1rem;">
    <div style="max-width:520px;margin:0 auto;background:#1e1e2e;border-radius:16px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:2rem;text-align:center;">
            <div style="font-size:2rem;margin-bottom:.5rem;">📦</div>
            <h1 style="color:#fff;margin:0;font-size:1.25rem;font-weight:700;">{{ __('emails.data_export_title') }}</h1>
        </div>
        <div style="padding:2rem;">
            <p style="margin:0 0 1rem;">{{ __('emails.data_export_greeting', ['name' => $user->name]) }}</p>
            <p style="margin:0 0 1.5rem;opacity:.8;">{{ __('emails.data_export_body', ['app' => config('app.name'), 'expires' => $expiresAt]) }}</p>
            <a href="{{ $downloadUrl }}" style="display:block;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;text-align:center;padding:1rem;border-radius:10px;font-weight:600;margin-bottom:1.5rem;">
                {{ __('emails.data_export_button') }}
            </a>
            <p style="font-size:.8rem;opacity:.5;margin:0;">{{ __('emails.data_export_footer') }}</p>
        </div>
    </div>
</body>
</html>
