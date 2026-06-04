@extends('layouts.app')

@section('title', __('auth.two_factor_authentication'))

@section('content')
<div style="
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
">
    <div style="
        width: 100%;
        max-width: 400px;
        background: var(--card-bg, #1e1e2e);
        border: 1px solid var(--border-color, #2a2a3e);
        border-radius: 16px;
        padding: 2rem;
    ">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="
                width: 56px; height: 56px;
                border-radius: 50%;
                background: linear-gradient(135deg, #8b5cf6, #6366f1);
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 1rem;
                font-size: 1.5rem;
            " aria-hidden="true">🔒</div>
            <h1 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 .5rem;">{{ __('auth.two_factor_authentication') }}</h1>
            <p style="font-size: .875rem; opacity: .65; margin: 0;">{{ __('auth.two_factor_challenge_desc') }}</p>
        </div>

        @if ($errors->any())
            <div role="alert" style="background:#ef444420; border:1px solid #ef444440; border-radius:8px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.875rem; color:#ef4444;">
                {{ $errors->first('code') ?? __('auth.two_factor_invalid_code') }}
            </div>
        @endif

        <form method="POST" action="{{ route('2fa.challenge.post') }}">
            @csrf
            <div style="margin-bottom:1.25rem;">
                <input
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="{{ __('auth.two_factor_code_placeholder') }}"
                    autofocus
                    style="
                        width: 100%;
                        box-sizing: border-box;
                        padding: .75rem 1rem;
                        border-radius: 8px;
                        border: 1px solid var(--border-color, #2a2a3e);
                        background: var(--input-bg, #12121f);
                        color: inherit;
                        font-size: 1.125rem;
                        letter-spacing: .15em;
                        text-align: center;
                    "
                >
            </div>
            <button type="submit" style="
                width: 100%;
                padding: .75rem;
                border-radius: 8px;
                border: none;
                background: linear-gradient(135deg, #8b5cf6, #6366f1);
                color: #fff;
                font-size: .9375rem;
                font-weight: 600;
                cursor: pointer;
            ">{{ __('auth.two_factor_verify') }}</button>
        </form>

        <div style="text-align:center; margin-top:1rem; font-size:.8rem; opacity:.5;">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('2fa-logout-form').submit();" style="color:inherit; text-decoration:underline;">{{ __('auth.sign_out') }}</a>
            <form id="2fa-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>
    </div>
</div>
@endsection
