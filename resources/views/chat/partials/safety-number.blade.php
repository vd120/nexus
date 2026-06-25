<style>
.e2e-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}
.e2e-modal-panel {
    background: rgba(20, 20, 25, 0.9);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 16px;
    padding: 28px;
    max-width: 420px;
    width: 90%;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.7);
    color: #ffffff;
    animation: e2e-modal-fade 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes e2e-modal-fade {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.safety-number-title-box {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.safety-number-title-box i {
    font-size: 22px;
    background: linear-gradient(135deg, #8c52ff, #00d4ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.safety-number-title-box h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: -0.01em;
}
.safety-number-desc-text {
    font-size: 13px;
    color: #b3b3b3;
    line-height: 1.6;
    margin: 0 0 20px 0;
}
.safety-number-code-container {
    font-family: 'Outfit', 'Inter', monospace;
    font-size: 16px;
    font-weight: 600;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.06);
    padding: 20px;
    border-radius: 12px;
    word-break: break-all;
    line-height: 1.8;
    text-align: center;
    color: #00d4ff;
    letter-spacing: 2px;
    user-select: all;
    text-shadow: 0 0 12px rgba(0, 212, 255, 0.35);
    transition: all 0.2s ease;
}
.safety-number-btn-group {
    margin-top: 28px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}
.safety-number-btn-close {
    padding: 10px 20px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.05);
    color: #e0e0e0;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}
.safety-number-btn-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.2);
}
.safety-number-btn-copy {
    padding: 10px 20px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #8c52ff, #00d4ff);
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(140, 82, 255, 0.35);
}
.safety-number-btn-copy:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(140, 82, 255, 0.5);
    opacity: 0.95;
}
.safety-number-btn-copy:active {
    transform: translateY(0);
}
</style>

<div id="safety-number-modal" class="e2e-modal-overlay">
    <div class="e2e-modal-panel">
        <div class="safety-number-title-box">
            <i class="fas fa-shield-alt"></i>
            <h3>{{ __('chat.safety_number_title') }}</h3>
        </div>
        <p class="safety-number-desc-text">{{ __('chat.safety_number_desc') }}</p>
        <div id="safety-number-display" class="safety-number-code-container">
            {{-- Populated by JS --}}
        </div>
        <div class="safety-number-btn-group">
            <button onclick="document.getElementById('safety-number-modal').style.display='none'" class="safety-number-btn-close">{{ __('chat.close') }}</button>
            <button onclick="copySafetyNumber()" class="safety-number-btn-copy">{{ __('chat.copy') }}</button>
        </div>
    </div>
</div>

<script>
window.showSafetyNumber = async function () {
    const modal = document.getElementById('safety-number-modal');
    const display = document.getElementById('safety-number-display');
    const title = modal.querySelector('.safety-number-title-box h3');
    const desc = modal.querySelector('.safety-number-desc-text');
    const copyBtn = modal.querySelector('.safety-number-btn-copy');

    if (window.isGroupChat) {
        if (title) title.textContent = 'Group Encryption';
        if (desc) desc.textContent = 'This group chat is protected with end-to-end encryption. All messages, media, and files are encrypted client-side using group keys. Safety numbers are only available in 1-to-1 private chats.';
        display.textContent = 'GROUP ENCRYPTED';
        if (copyBtn) copyBtn.style.display = 'none';
        modal.style.display = 'flex';
        return;
    }

    if (title) title.innerHTML = '{{ __('chat.safety_number_title') }}';
    if (desc) desc.innerHTML = '{{ __('chat.safety_number_desc') }}';
    if (copyBtn) copyBtn.style.display = 'block';

    display.innerHTML = '{{ __('chat.calculating') }}';
    modal.style.display = 'flex';

    if (window.e2eManager && typeof window.e2eManager.computeSafetyNumber === 'function') {
        try {
            const hash = await window.e2eManager.computeSafetyNumber(window.activeRecipientId);
            const formatted = hash.match(/.{1,4}/g).join(' ');
            display.textContent = formatted;
        } catch (e) {
            console.error('[SafetyNumber] Error computing safety number:', e);
            display.innerHTML = '{{ __('chat.safety_number_unavailable') }}';
        }
    }
};

window.copySafetyNumber = function () {
    const text = document.getElementById('safety-number-display').textContent;
    navigator.clipboard.writeText(text).then(() => {
        if (typeof showToast === 'function') {
            showToast('{{ __('chat.copied') }}', 'success');
        } else {
            alert('{{ __('chat.copied') }}');
        }
    });
};
</script>
