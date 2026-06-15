{{ __('emails.data_export_greeting', ['name' => $user->name]) }}

{{ __('emails.data_export_body', ['app' => config('app.name'), 'expires' => $expiresAt]) }}

{{ __('emails.data_export_button') }}: {{ $downloadUrl }}

--
{{ __('emails.data_export_footer') }}
{{ config('app.name') }}
