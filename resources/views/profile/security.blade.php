@php
    $e2eHasBackup = app(\App\Services\KeyStorageService::class)->getBackupKeys(auth()->id()) !== null;
@endphp

<div class="e2e-security-section" id="e2e-security-section">
    <h3><i class="fas fa-shield-alt"></i> {{ __('chat.e2e_encrypted') }} — {{ __('chat.e2e_encrypted_description') }}</h3>

    <div class="e2e-passphrase-form" id="e2e-passphrase-form">
        @if($e2eHasBackup)
            <p class="e2e-status e2e-status-backed-up">
                <i class="fas fa-check-circle"></i> {{ __('Your encryption keys are backed up.') }}
            </p>

            <div id="e2e-update-passphrase-form" class="e2e-form-container">
                @csrf
                <div class="field">
                    <label for="e2e-current-passphrase">{{ __('Current Backup Passphrase') }}</label>
                    <input type="password" id="e2e-current-passphrase" class="e2e-input"
                        placeholder="{{ __('Enter your current backup passphrase') }}"
                        autocomplete="off" />
                </div>

                <div class="field">
                    <label for="e2e-new-passphrase">{{ __('New Backup Passphrase') }}</label>
                    <input type="password" id="e2e-new-passphrase" class="e2e-input"
                        placeholder="{{ __('Enter a new backup passphrase (min 8 characters)') }}"
                        autocomplete="new-password" />
                    <div class="strength-track"><div class="strength-fill" id="e2e-strength-fill"></div></div>
                    <div class="strength-label" id="e2e-strength-label"></div>
                </div>

                <div class="field">
                    <label for="e2e-confirm-passphrase">{{ __('Confirm New Passphrase') }}</label>
                    <input type="password" id="e2e-confirm-passphrase" class="e2e-input"
                        placeholder="{{ __('Confirm your new passphrase') }}"
                        autocomplete="new-password" />
                    <div class="field-status" id="e2e-match-status"></div>
                </div>

                <button type="button" id="e2e-update-passphrase-btn" class="btn btn-primary"
                    onclick="window.handleE2EPassphraseUpdate()">
                    <i class="fas fa-sync-alt"></i> {{ __('Update Passphrase') }}
                </button>

                <button type="button" id="e2e-reset-passphrase-btn" class="btn btn-outline-warning"
                    onclick="window.handleE2EPassphraseReset()">
                    <i class="fas fa-exclamation-triangle"></i> {{ __('chat.e2e_forgot_passphrase_reset') }}
                </button>

                <div id="e2e-passphrase-result" class="e2e-result" style="display:none;"></div>
            </div>
        @else
            <p class="e2e-status e2e-status-not-backed-up">
                <i class="fas fa-exclamation-triangle"></i> {{ __('chat.e2e_set_backup_description') }}
            </p>

            <div id="e2e-setup-passphrase-form" class="e2e-form-container">
                @csrf
                <div class="field">
                    <label for="e2e-setup-passphrase">{{ __('chat.e2e_passphrase_label') }}</label>
                    <input type="password" id="e2e-setup-passphrase" class="e2e-input"
                        placeholder="{{ __('chat.e2e_passphrase_placeholder') }}"
                        autocomplete="new-password" />
                </div>

                <div class="field">
                    <label for="e2e-setup-confirm-passphrase">{{ __('chat.e2e_confirm_passphrase_label') }}</label>
                    <input type="password" id="e2e-setup-confirm-passphrase" class="e2e-input"
                        placeholder="{{ __('Confirm your passphrase') }}"
                        autocomplete="new-password" />
                </div>

                <button type="button" id="e2e-setup-passphrase-btn" class="btn btn-primary"
                    onclick="window.handleE2EPassphraseSetup()">
                    <i class="fas fa-shield-alt"></i> {{ __('chat.e2e_set_backup') }}
                </button>

                <div id="e2e-passphrase-result" class="e2e-result" style="display:none;"></div>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.e2e-security-section {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 20px;
    margin-top: 16px;
    border: 1px solid var(--border);
}
.e2e-security-section h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    margin: 0 0 16px;
    color: var(--text);
}
.e2e-passphrase-form .field {
    margin-bottom: 14px;
}
.e2e-passphrase-form .field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
    color: var(--text-muted);
}
.e2e-input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
}
.e2e-input:focus {
    border-color: var(--primary);
}
.e2e-status {
    font-size: 13px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.e2e-status-backed-up { color: #25D366; }
.e2e-status-not-backed-up { color: #f5a623; }
.e2e-result {
    margin-top: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
}
.e2e-result.success {
    background: rgba(37, 211, 102, 0.1);
    color: #25D366;
    border: 1px solid rgba(37, 211, 102, 0.3);
}
.e2e-result.error {
    background: rgba(255, 59, 48, 0.1);
    color: #ff3b30;
    border: 1px solid rgba(255, 59, 48, 0.3);
}
.btn-outline-warning {
    background: transparent;
    color: #e67e22;
    border: 1px solid #e67e22;
    transition: all 0.2s;
}
.btn-outline-warning:hover {
    background: #e67e22;
    color: #fff;
}
.btn-outline-warning:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
@endpush

@push('scripts')
@vite(['resources/js/e2e/e2e-manager.js'])
<script type="module">

window.handleE2EPassphraseUpdate = async function() {
    const currentInput = document.getElementById('e2e-current-passphrase');
    const newInput = document.getElementById('e2e-new-passphrase');
    const confirmInput = document.getElementById('e2e-confirm-passphrase');
    const resultEl = document.getElementById('e2e-passphrase-result');
    const btn = document.getElementById('e2e-update-passphrase-btn');

    const current = currentInput?.value?.trim();
    const newPass = newInput?.value?.trim();
    const confirm = confirmInput?.value?.trim();

    if (!current || !newPass || !confirm) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('All fields are required.') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (newPass.length < 8) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('Passphrase must be at least 8 characters.') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (newPass !== confirm) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('Passphrases do not match.') }}';
        resultEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.textContent = '{{ __('Updating...') }}';
    resultEl.style.display = 'none';

    try {
        const manager = new window.E2EManager();
        await manager.init();

        // First restore with current passphrase
        await manager.restoreKeys(current);

        // Then re-encrypt and re-upload with new passphrase
        await manager.backupKeys(newPass);

        resultEl.className = 'e2e-result success';
        resultEl.textContent = '{{ __('Passphrase updated successfully!') }}';
        resultEl.style.display = 'block';
        currentInput.value = '';
        newInput.value = '';
        confirmInput.value = '';
    } catch (err) {
        console.error('[E2E Update Error]', err);
        resultEl.className = 'e2e-result error';
        resultEl.textContent = err.message.includes('No backup')
            ? '{{ __('No backup found. Please set up a passphrase from chat first.') }}'
            : '{{ __('Failed to update passphrase. Check your current passphrase and try again.') }}';
        resultEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = '{{ __('Update Passphrase') }}';
    }
};

window.handleE2EPassphraseReset = async function() {
    const newInput = document.getElementById('e2e-new-passphrase');
    const confirmInput = document.getElementById('e2e-confirm-passphrase');
    const resultEl = document.getElementById('e2e-passphrase-result');
    const btn = document.getElementById('e2e-reset-passphrase-btn');

    const newPass = newInput?.value?.trim();
    const confirm = confirmInput?.value?.trim();

    if (!newPass || !confirm) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_new_confirm_required') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (newPass.length < 8) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_passphrase_min_length') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (newPass !== confirm) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_passphrases_mismatch') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (!confirm('{{ __('chat.e2e_reset_confirm') }}')) {
        return;
    }

    btn.disabled = true;
    btn.textContent = '{{ __('chat.e2e_resetting') }}';
    resultEl.style.display = 'none';

    try {
        const manager = new window.E2EManager();
        await manager.init();

        const hasKeys = await manager.db.hasKeys();
        if (!hasKeys) {
            if (!confirm('You do not have E2E keys stored locally on this device. Resetting will generate new keys, and you will lose access to older messages. Do you want to proceed?')) {
                btn.disabled = false;
                btn.textContent = '{{ __('chat.e2e_forgot_passphrase_reset') }}';
                return;
            }

            const response = await fetch("/api/e2e/keys/reset", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                }
            });
            if (!response.ok) {
                throw new Error('Failed to reset keys on server');
            }

            await manager.db.clear("user-keys");
            await manager.db.clear("peer-keys");
            await manager.db.clear("group-keys");

            await manager.generateKeys();
            await manager.registerKeys();
            await manager.backupKeys(newPass);
        } else {
            await manager.backupKeys(newPass);
        }

        resultEl.className = 'e2e-result success';
        resultEl.textContent = '{{ __('chat.e2e_reset_success') }}';
        resultEl.style.display = 'block';
        
        const currentInput = document.getElementById('e2e-current-passphrase');
        if (currentInput) currentInput.value = '';
        newInput.value = '';
        confirmInput.value = '';

        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        console.error('[E2E Reset Error]', err);
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_reset_failed') }}';
        resultEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = '{{ __('chat.e2e_forgot_passphrase_reset') }}';
    }
};

window.handleE2EPassphraseSetup = async function() {
    const input = document.getElementById('e2e-setup-passphrase');
    const confirmInput = document.getElementById('e2e-setup-confirm-passphrase');
    const resultEl = document.getElementById('e2e-passphrase-result');
    const btn = document.getElementById('e2e-setup-passphrase-btn');

    const passphrase = input?.value?.trim();
    const confirm = confirmInput?.value?.trim();

    if (!passphrase || !confirm) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_new_confirm_required') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (passphrase.length < 8) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_passphrase_min_length') }}';
        resultEl.style.display = 'block';
        return;
    }

    if (passphrase !== confirm) {
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_passphrases_mismatch') }}';
        resultEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.textContent = '{{ __('chat.e2e_resetting') }}';
    resultEl.style.display = 'none';

    try {
        const manager = new window.E2EManager();
        await manager.init();

        // Ensure keys exist (generates them if not)
        await manager.ensureKeys();
        
        // Register public keys on server
        await manager.registerKeys();

        // Backup keys using the passphrase
        await manager.backupKeys(passphrase);

        resultEl.className = 'e2e-result success';
        resultEl.textContent = '{{ __('chat.e2e_backup_success') }}';
        resultEl.style.display = 'block';
        
        // Reload page to reflect that backup now exists
        setTimeout(() => location.reload(), 1500);
    } catch (err) {
        console.error('[E2E Setup Error]', err);
        resultEl.className = 'e2e-result error';
        resultEl.textContent = '{{ __('chat.e2e_backup_failed') }}';
        resultEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = '{{ __('chat.e2e_set_backup') }}';
    }
};
</script>
@endpush
