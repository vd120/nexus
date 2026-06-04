{{ config('app.name') }} - {{ __('emails.verification_code_prompt') }}

========================================

{{ __('emails.verification_code_welcome', ['app' => config('app.name')]) }}

{{ __('emails.verification_code_prompt') }} {{ $verificationCode }}

{{ __('emails.verification_code_expire') }}

{{ __('emails.verification_code_notice') }}

========================================
&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('emails.all_rights_reserved') }}
