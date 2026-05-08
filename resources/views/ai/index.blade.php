@extends('layouts.app')

@section('title', __('ai.title') . ' - Nexus')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ai-chat.css') }}">
<style>
    body {
        overflow: hidden !important;
    }
    @media (max-width: 480px) {
        body {
            padding-top: 48px !important;
        }
    }
    .app-layout {
        padding: 0 !important;
        max-width: 100% !important;
    }
    .main-content {
        max-width: 100% !important;
    }
</style>
@endpush

@section('content')
<div class="ai-page">
    <header class="ai-header">
        <a href="{{ route('home') }}" class="back-btn" title="{{ __('home.back') }}">
            <i class="fas fa-chevron-left"></i>
        </a>
        <div class="ai-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="ai-info">
            <h1>
                {{ __('ai.assistant_name') }}
                <i class="fas fa-check-circle" style="color: #6366f1; font-size: 14px;"></i>
            </h1>
            <div class="ai-status">
                <span class="status-dot"></span>
                {{ __('ai.online') }}
            </div>
        </div>
        <div class="header-actions">
            <button class="icon-btn" onclick="clearChat()" title="{{ __('ai.clear_chat') }}">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </header>

    <div class="chat-container" id="chatContainer" 
         data-user-avatar="{{ auth()->user()->avatar_url }}"
         data-user-name="{{ auth()->user()->username }}">
        <!-- Welcome Message -->
        <div class="message ai" id="welcomeMessage">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-bubble">
                <p><strong>🤖 {{ __('ai.welcome_greeting') }}, {{ auth()->user()->name }}!</strong></p>
                <p style="margin-top: 12px; color: var(--wa-text-muted);">{{ __('ai.welcome_instruction') }}</p>

                <div class="quick-suggestions">
                    <button class="suggest-btn" onclick="sendQuickMessage('{{ __('ai.writing_posts') }}')">
                        <i class="fas fa-pen-nib"></i> {{ __('ai.writing_posts') }}
                    </button>
                    <button class="suggest-btn" onclick="sendQuickMessage('{{ __('ai.trending_topics') }}')">
                        <i class="fas fa-trending-up"></i> {{ __('ai.trending_topics') }}
                    </button>
                    <button class="suggest-btn" onclick="sendQuickMessage('{{ __('ai.profile_setup') }}')">
                        <i class="fas fa-user-cog"></i> {{ __('ai.profile_setup') }}
                    </button>
                </div>

                <div class="message-time">{{ now()->format('h:i a') }}</div>
            </div>
        </div>
    </div>

    <div class="input-area">
        <div class="input-wrapper">
            <textarea id="chatInputNative" placeholder="{{ __('ai.input_placeholder') }}" maxlength="1000" rows="1" autocomplete="off"></textarea>
            <button type="button" id="stopBtn" class="stop-btn" style="display:none;" title="{{ __('ai.stop') }}">
                <i class="fas fa-stop"></i>
            </button>
            <button type="button" id="sendBtn" class="send-btn" disabled title="{{ __('ai.send') }}">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

@vite(['resources/js/legacy/ai-chat.js'])
@endsection
