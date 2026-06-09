@php
$currentLocale = app()->getLocale();
@endphp

{{-- Push Notification Settings Modal --}}
<div id="pushSettingsModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) hidePushSettings()">
    <div class="modal-content push-settings-modal">
        <div class="modal-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="header-title">
                    <h3>{{ __('notifications.push_notifications') }}</h3>
                    <p>{{ __('notifications.stay_updated') }}</p>
                </div>
            </div>
            <button class="modal-close" onclick="hidePushSettings()" title="{{ __('messages.close') }}">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="push-settings-content">

                <!-- Not Enabled State -->
                <div id="pushNotEnabled" class="push-not-enabled">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-bell"></i>
                            <div class="pulse-ring"></div>
                        </div>
                        <h4>{{ __('notifications.never_miss_moment') }}</h4>
                        <p>{{ __('notifications.enable_push_desc_detailed') }}</p>
                        <button class="btn-enable" onclick="enablePushNotifications()">
                            {{ __('notifications.enable_push') }}
                        </button>
                        <p class="hint">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;opacity:0.6;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            {{ __('notifications.safe_secure') }}
                        </p>
                    </div>
                </div>

                <!-- Enabled State -->
                <div id="pushEnabled" class="push-enabled" style="display: none;">
                    <div class="success-state">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4>{{ __('notifications.all_set') }}</h4>
                        <p>{{ __('notifications.will_receive_notifications') }}</p>
                    </div>
                    <div class="success-actions">
                        <button class="btn btn-test" onclick="testPushNotification()">
                            <i class="fas fa-flask"></i> {{ __('notifications.test_notification') }}
                        </button>
                        <button class="btn btn-danger" onclick="disablePushNotifications()">
                            <i class="fas fa-bell-slash"></i> {{ __('notifications.disable_all') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Push Notification Modal - Modern & Responsive */
#pushSettingsModal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px;
    animation: fadeIn 0.2s ease;
}

#pushSettingsModal.modal-visible {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.push-settings-modal {
    max-width: 440px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    border-radius: 20px;
    background: var(--surface, #141416);
    border: 1px solid var(--border, rgba(255,255,255,0.07));
    box-shadow: 0 24px 64px rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border, rgba(255,255,255,0.07));
    flex-shrink: 0;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.header-title h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text, #fff);
    margin: 0 0 2px;
}

.header-title p {
    font-size: 13px;
    color: var(--text-muted, #888);
    margin: 0;
}

.modal-close {
    background: rgba(255,255,255,0.05);
    border: none;
    font-size: 18px;
    color: var(--text-muted, #888);
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.modal-close:hover {
    background: rgba(255,255,255,0.1);
    color: var(--text, #fff);
    transform: rotate(90deg);
}

/* Body */
.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.push-settings-content {
    text-align: center;
}

/* Empty State */
.empty-state {
    padding: 20px 10px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: white;
    position: relative;
    box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
}

.pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid #6366f1;
    border-radius: 50%;
    animation: pulse 2s ease-out infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(1.6); opacity: 0; }
}

.empty-state h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text, #fff);
    margin: 0 0 10px;
}

.empty-state p {
    color: var(--text-muted, #888);
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 20px;
}

.btn-enable {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 13px 32px;
    font-size: 15px;
    font-weight: 700;
    border-radius: 999px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
    margin-top: 4px;
}

.btn-enable:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.45);
}

.hint {
    font-size: 12px;
    color: var(--text-muted, rgba(255,255,255,0.4));
    margin-top: 12px;
    margin-bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
    line-height: 1;
}

/* Form Section */
.form-section {
    margin-bottom: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 6px;
}

.section-title i {
    color: #6366f1;
    font-size: 18px;
}

.section-title span {
    font-size: 16px;
    font-weight: 600;
    color: var(--text, #fff);
}

.section-desc {
    font-size: 13px;
    color: var(--text-muted, #888);
    margin: 0;
}

/* Settings List */
.settings-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}

.setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
    transition: all 0.2s;
}

.setting-row:hover {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.1);
}

.setting-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.setting-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.setting-icon.likes { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.setting-icon.comments { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.setting-icon.follows { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.setting-icon.messages { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.setting-icon.mentions { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

.setting-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    text-align: left;
}

.setting-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text, #fff);
}

.setting-desc {
    font-size: 12px;
    color: var(--text-muted, #888);
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}

.toggle-switch input {
    display: none;
}

.toggle-slider {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.2);
    border-radius: 13px;
    cursor: pointer;
    transition: all 0.3s;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(22px);
}

/* Form Actions */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.btn-block {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.2);
}

/* Success State */
.success-state {
    padding: 20px 10px;
}

.success-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 16px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: white;
    animation: popIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes popIn {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.success-state h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text, #fff);
    margin: 0 0 8px;
}

.success-state p {
    font-size: 14px;
    color: var(--text-muted, #888);
    margin: 0 0 20px;
}

.success-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.btn-secondary {
    background: rgba(255,255,255,0.1);
    color: var(--text, #fff);
    border: 1px solid rgba(255,255,255,0.2);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.15);
}

.btn-test {
    background: rgba(139, 92, 246, 0.1);
    color: #8b5cf6;
    border: 1px solid rgba(139, 92, 246, 0.3);
}

.btn-test:hover {
    background: rgba(139, 92, 246, 0.2);
}

/* RTL Support */
[dir="rtl"] .setting-text {
    text-align: right;
}

[dir="rtl"] .toggle-switch {
    margin-right: 0;
}

/* Responsive */
@media (max-width: 480px) {
    #pushSettingsModal {
        padding: 12px;
        align-items: flex-end;
    }
    
    .push-settings-modal {
        max-height: 95vh;
        border-radius: 16px 16px 0 0;
        animation: slideUpBottom 0.3s ease;
    }
    
    @keyframes slideUpBottom {
        from { opacity: 0; transform: translateY(100%); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .modal-header {
        padding: 16px;
    }
    
    .header-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .header-title h3 {
        font-size: 16px;
    }
    
    .modal-body {
        padding: 16px;
    }
    
    .empty-icon {
        width: 70px;
        height: 70px;
        font-size: 30px;
    }
    
    .setting-row {
        padding: 12px;
    }
    
    .setting-icon {
        width: 36px;
        height: 36px;
        font-size: 14px;
    }
    
    .setting-label {
        font-size: 13px;
    }
    
    .setting-desc {
        font-size: 11px;
    }
}

/* Scrollbar */
.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: transparent;
}

.modal-body::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
</style>

<script>
// Push Notification UI Functions
function showPushSettings() {
    const modal = document.getElementById('pushSettingsModal');
    modal.style.display = 'flex';
    // Trigger animation
    setTimeout(() => modal.classList.add('modal-visible'), 10);
    checkPushStatus();
}

function hidePushSettings() {
    const modal = document.getElementById('pushSettingsModal');
    modal.classList.remove('modal-visible');
    setTimeout(() => modal.style.display = 'none', 200);
}

async function checkPushStatus() {
    const notEnabled = document.getElementById('pushNotEnabled');
    const enabled = document.getElementById('pushEnabled');

    if (!window.pushManager || !window.pushManager.isSupported) {
        notEnabled.style.display = 'block';
        enabled.style.display = 'none';
        return;
    }

    if (window.pushManager.isEnabled()) {
        // Confirm subscription exists in DB
        try {
            const response = await fetch('/api/push/settings', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (data.success && data.subscribed === false) {
                notEnabled.style.display = 'block';
                enabled.style.display = 'none';
                return;
            }
        } catch (e) {}

        notEnabled.style.display = 'none';
        enabled.style.display = 'block';
    } else {
        notEnabled.style.display = 'block';
        enabled.style.display = 'none';
    }
}

async function enablePushNotifications() {
    const result = await window.pushManager.requestPermission();
    if (result) {
        // After enabling, show success state
        checkPushStatus();
    }
}

function showPushSettingsForm() {
    // Show the settings form to edit preferences
    const form = document.getElementById('pushSettingsForm');
    const notEnabled = document.getElementById('pushNotEnabled');
    const enabled = document.getElementById('pushEnabled');
    
    form.style.display = 'block';
    notEnabled.style.display = 'none';
    enabled.style.display = 'none';
}

async function disablePushNotifications() {
    if (!confirm('Disable push notifications?')) {
        return;
    }

    try {
        await window.pushManager.unsubscribe();
        checkPushStatus();
        // Toast already shown by unsubscribe()
    } catch (error) {
        window.pushManager.showToast(error.message || 'Error disabling notifications', 'error');
    }
}

async function testPushNotification() {
    try {
        const response = await fetch('/api/push/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (data.success) {
            window.pushManager.showToast(data.message, 'success');
        } else {
            window.pushManager.showToast(data.message || '{{ __('messages.error') }}', 'error');
        }
    } catch (error) {
        window.pushManager.showToast('{{ __('messages.error') }}', 'error');
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hidePushSettings();
    }
});
</script>
