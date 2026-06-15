{{ __('emails.two_factor_reminder_greeting', ['name' => $user->name]) }}

{{ __('emails.two_factor_reminder_body') }}

{{ __('emails.two_factor_reminder_cta') }}: {{ route('2fa.setup') }}

--
{{ __('emails.two_factor_reminder_footer') }}
{{ config('app.name') }}
