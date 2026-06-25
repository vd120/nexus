<div id="e2e-key-setup-banner" class="e2e-banner" style="display:none;">
    <div class="e2e-banner-content">
        <div class="e2e-banner-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="e2e-banner-text">
            <strong>{{ __('chat.e2e_secure_conversations') }}</strong>
            <p>{{ __('chat.e2e_backup_prompt') }}</p>
        </div>
        <div class="e2e-banner-actions">
            <input type="password" id="e2e-passphrase-input" class="e2e-passphrase-input"
                placeholder="{{ __('chat.e2e_passphrase_placeholder') }}"
                autocomplete="new-password" />
            <span id="e2e-passphrase-strength" class="e2e-strength-indicator"></span>
            <button id="e2e-set-backup-btn" class="e2e-btn e2e-btn-primary"
                onclick="window.e2eManager?.handleSetBackup()">
                {{ __('chat.e2e_set_backup') }}
            </button>
            <button id="e2e-dismiss-banner-btn" class="e2e-btn e2e-btn-ghost"
                onclick="window.e2eManager?.dismissBanner()">
                {{ __('chat.e2e_later') }}
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
.e2e-banner {
    background: linear-gradient(135deg, #0b4f6c 0%, #1a7a5c 100%);
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    animation: slideDown 0.3s ease-out;
}
@keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.e2e-banner-content {
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 800px;
    margin: 0 auto;
    flex-wrap: wrap;
}
.e2e-banner-icon {
    font-size: 24px;
    color: rgba(255,255,255,0.9);
    flex-shrink: 0;
}
.e2e-banner-text {
    flex: 1;
    min-width: 200px;
}
.e2e-banner-text strong {
    color: #fff;
    font-size: 14px;
}
.e2e-banner-text p {
    color: rgba(255,255,255,0.8);
    font-size: 12px;
    margin: 2px 0 0;
}
.e2e-banner-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.e2e-passphrase-input {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-size: 13px;
    min-width: 180px;
    outline: none;
    transition: border-color 0.2s;
}
.e2e-passphrase-input::placeholder {
    color: rgba(255,255,255,0.5);
}
.e2e-passphrase-input:focus {
    border-color: rgba(255,255,255,0.6);
}
.e2e-strength-indicator {
    font-size: 11px;
    color: rgba(255,255,255,0.7);
    min-width: 80px;
    text-align: center;
}
.e2e-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    font-size: 13px;
    cursor: pointer;
    transition: opacity 0.2s;
    white-space: nowrap;
}
.e2e-btn:hover { opacity: 0.85; }
.e2e-btn-primary {
    background: #25D366;
    color: #fff;
    font-weight: 600;
}
.e2e-btn-ghost {
    background: transparent;
    color: rgba(255,255,255,0.7);
    border: 1px solid rgba(255,255,255,0.2);
}
.e2e-btn-ghost:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}
</style>
@endpush
