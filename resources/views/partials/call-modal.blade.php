{{-- ============================================================
     Call Modal — Nexus VoIP  (Full-screen)
     Controlled via window.CallModal JS API (see <script> below).
     ============================================================ --}}

{{-- Minimized pill — shown when user hides the call window --}}
<div id="call-minimized" style="display:none;" onclick="window.CallModal && window.CallModal.expand()" title="{{ __('messages.voice_call') }}">
    <span class="call-minimized-dot"></span>
    <i class="fa-solid fa-phone call-minimized-icon"></i>
    <span id="call-minimized-timer">00:00</span>
    <button class="call-minimized-end" onclick="event.stopPropagation(); window.CallManager && window.CallManager.endCall()" title="{{ __('messages.end_call') }}">
        <i class="fa-solid fa-phone-slash"></i>
    </button>
</div>

<div id="call-modal" style="display:none;" aria-live="assertive" role="dialog" aria-label="Phone call">

    {{-- Top bar --}}
    <div class="call-topbar">
        <button id="call-btn-minimize" class="call-topbar-btn" style="display:none;"
            aria-label="Minimize" onclick="window.CallModal && window.CallModal.minimize()">
            <i class="fa-solid fa-chevron-down"></i>
        </button>
    </div>

    {{-- ── Incoming call state ── --}}
    <div id="call-state-incoming" class="call-state">
        <div class="call-avatar-area">
            <div class="call-avatar-wrapper">
                <img id="call-incoming-avatar" src="" alt="" class="call-avatar">
                <div class="call-ring-pulse"></div>
                <div class="call-ring-pulse call-ring-pulse--delay"></div>
            </div>
        </div>
        <div class="call-info">
            <p id="call-incoming-name" class="call-name"></p>
            <p class="call-label">{{ __('messages.incoming_call') }}</p>
        </div>
        <div class="call-actions call-actions--incoming">
            <div class="call-action-item">
                <button id="call-btn-reject" class="call-btn call-btn-decline"
                    aria-label="{{ __('messages.decline_call') }}"
                    onclick="window.CallManager && window.CallManager.rejectCall()">
                    <i class="fa-solid fa-phone-slash"></i>
                </button>
                <span class="call-btn-label">{{ __('messages.decline_call') }}</span>
            </div>
            <div class="call-action-item">
                <button id="call-btn-accept" class="call-btn call-btn-accept"
                    aria-label="{{ __('messages.accept_call') }}"
                    onclick="window.CallManager && (window.CallManager.unlockAudioNow(), window.CallManager.acceptCall())">
                    <i class="fa-solid fa-phone"></i>
                </button>
                <span class="call-btn-label">{{ __('messages.accept_call') }}</span>
            </div>
        </div>
    </div>

    {{-- ── Outgoing / Calling state ── --}}
    <div id="call-state-calling" class="call-state" style="display:none;">
        <div class="call-avatar-area">
            <div class="call-avatar-wrapper">
                <img id="call-calling-avatar" src="" alt="" class="call-avatar">
                <div class="call-ring-pulse"></div>
                <div class="call-ring-pulse call-ring-pulse--delay"></div>
            </div>
        </div>
        <div class="call-info">
            <p id="call-calling-name" class="call-name"></p>
            <p class="call-label">{{ __('messages.calling') }}</p>
        </div>
        <div class="call-actions">
            <div class="call-action-item">
                <button id="call-btn-cancel" class="call-btn call-btn-decline"
                    aria-label="{{ __('messages.end_call') }}"
                    onclick="window.CallManager && window.CallManager.endCall()">
                    <i class="fa-solid fa-phone-slash"></i>
                </button>
                <span class="call-btn-label">{{ __('messages.end_call') }}</span>
            </div>
        </div>
    </div>

    {{-- ── Active call state ── --}}
    <div id="call-state-active" class="call-state" style="display:none;">
        <div class="call-avatar-area">
            <div class="call-avatar-wrapper call-avatar-wrapper--active">
                <img id="call-active-avatar" src="" alt="" class="call-avatar">
            </div>
        </div>
        <div class="call-info">
            <p id="call-active-name" class="call-name"></p>
            <p id="call-timer" class="call-timer">00:00</p>
        </div>
        <div class="call-actions">
            <div class="call-action-item">
                <button id="call-btn-mute" class="call-btn call-btn-mute"
                    aria-label="{{ __('messages.toggle_mute') }}"
                    onclick="window.CallManager && window.CallManager.toggleMute()">
                    <i class="fa-solid fa-microphone"></i>
                </button>
                <span class="call-btn-label">{{ __('messages.toggle_mute') }}</span>
            </div>
            <div class="call-action-item">
                <button id="call-btn-speaker" class="call-btn call-btn-mute"
                    aria-label="{{ __('messages.toggle_speaker') }}"
                    onclick="window.CallManager && window.CallManager.toggleSpeaker()">
                    <i class="fa-solid fa-volume-high"></i>
                </button>
                <span class="call-btn-label">{{ __('messages.toggle_speaker') }}</span>
            </div>
            <div class="call-action-item">
                <button id="call-btn-end" class="call-btn call-btn-end"
                    aria-label="{{ __('messages.end_call') }}"
                    onclick="window.CallManager && window.CallManager.endCall()">
                    <i class="fa-solid fa-phone-slash"></i>
                </button>
                <span class="call-btn-label">{{ __('messages.end_call') }}</span>
            </div>
        </div>
    </div>

    {{-- ── Status state (declined / no answer / ended) ── --}}
    <div id="call-state-status" class="call-state" style="display:none;">
        <p id="call-status-text" class="call-status-text"></p>
    </div>

</div>

{{-- Remote peer audio — visually hidden but in render tree (display:none blocks audio on some mobile browsers) --}}
<audio id="call-remote-audio" autoplay playsinline
    style="position:absolute;width:0;height:0;opacity:0;pointer-events:none;"></audio>

<script>
window.CallTranslations = {
    calling:           "{{ __('messages.calling') }}",
    toggle_speaker:    "{{ __('messages.toggle_speaker') }}",
    incoming_call:     "{{ __('messages.incoming_call') }}",
    accept_call:       "{{ __('messages.accept_call') }}",
    decline_call:      "{{ __('messages.decline_call') }}",
    end_call:          "{{ __('messages.end_call') }}",
    toggle_mute:       "{{ __('messages.toggle_mute') }}",
    call_declined:     "{{ __('messages.call_declined') }}",
    call_no_answer:    "{{ __('messages.call_no_answer') }}",
    call_ended:        "{{ __('messages.call_ended') }}",
    call_lost:         "{{ __('messages.call_lost') }}",
    call_mic_denied:   "{{ __('messages.call_mic_denied') }}",
    call_start_failed: "{{ __('messages.call_start_failed') }}",
    user_busy:         "{{ __('messages.user_busy') }}",
};
</script>

<script>
(function () {
    'use strict';

    var ALL_STATES = ['call-state-incoming','call-state-calling','call-state-active','call-state-status'];

    function _showOnly(stateId) {
        ALL_STATES.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.style.display = (id === stateId) ? 'flex' : 'none';
        });
    }

    function _formatTime(s) {
        var sec = s % 60;
        var min = Math.floor(s / 60) % 60;
        var hr  = Math.floor(s / 3600);
        var p   = function (n) { return n < 10 ? '0' + n : String(n); };
        return hr > 0 ? p(hr)+':'+p(min)+':'+p(sec) : p(min)+':'+p(sec);
    }

    // Use getAttribute to avoid JS returning full page URL for empty src=""
    function _getImgSrc(id) {
        var el = document.getElementById(id);
        if (!el) return '';
        return el.getAttribute('src') || '';
    }

    var _statusTimer = null;

    function _cancelStatusTimer() {
        if (_statusTimer) { clearTimeout(_statusTimer); _statusTimer = null; }
    }

    window.CallModal = {

        showCalling: function (opts) {
            _cancelStatusTimer();
            opts = opts || {};
            var modal  = document.getElementById('call-modal');
            var minBtn = document.getElementById('call-btn-minimize');
            if (!modal) return;
            var nameEl = document.getElementById('call-calling-name');
            var avatar = document.getElementById('call-calling-avatar');
            if (nameEl) nameEl.textContent = opts.calleeName || '';
            if (avatar) { avatar.src = opts.calleeAvatar || ''; avatar.alt = opts.calleeName || ''; }
            if (minBtn) minBtn.style.display = 'none';
            _showOnly('call-state-calling');
            modal.style.display = 'flex';
        },

        showIncoming: function (opts) {
            _cancelStatusTimer();
            opts = opts || {};
            var modal  = document.getElementById('call-modal');
            var minBtn = document.getElementById('call-btn-minimize');
            if (!modal) return;
            var nameEl = document.getElementById('call-incoming-name');
            var avatar = document.getElementById('call-incoming-avatar');
            if (nameEl) nameEl.textContent = opts.callerName || '';
            if (avatar) { avatar.src = opts.callerAvatar || ''; avatar.alt = opts.callerName || ''; }
            if (minBtn) minBtn.style.display = 'none';
            modal.dataset.callId = opts.callId || '';
            _showOnly('call-state-incoming');
            modal.style.display = 'flex';
        },

        showActive: function (opts) {
            _cancelStatusTimer();
            opts = opts || {};
            var modal  = document.getElementById('call-modal');
            var minBtn = document.getElementById('call-btn-minimize');
            if (!modal) return;
            // Use textContent for name and getAttribute for src to avoid
            // the JS gotcha where img.src returns the full page URL when src=""
            var inNameEl  = document.getElementById('call-incoming-name');
            var callNameEl= document.getElementById('call-calling-name');
            var name      = opts.callerName
                         || (inNameEl   && inNameEl.textContent)
                         || (callNameEl && callNameEl.textContent) || '';
            var avatarSrc = opts.callerAvatar
                         || _getImgSrc('call-incoming-avatar')
                         || _getImgSrc('call-calling-avatar') || '';

            var activeName   = document.getElementById('call-active-name');
            var activeAvatar = document.getElementById('call-active-avatar');
            var timerEl      = document.getElementById('call-timer');
            if (activeName)   activeName.textContent = name;
            if (activeAvatar) { activeAvatar.src = avatarSrc; activeAvatar.alt = name; }
            if (timerEl)      timerEl.textContent = '00:00';
            if (minBtn)       minBtn.style.display = 'flex';

            var muteBtn = document.getElementById('call-btn-mute');
            if (muteBtn) { muteBtn.classList.remove('is-muted'); var mi = muteBtn.querySelector('i'); if(mi) mi.className='fa-solid fa-microphone'; }
            var spkBtn = document.getElementById('call-btn-speaker');
            if (spkBtn) { spkBtn.classList.remove('is-speaker-on'); spkBtn.style.color=''; }

            _showOnly('call-state-active');
            modal.style.display = 'flex';
        },

        showStatus: function (msg) {
            var modal  = document.getElementById('call-modal');
            var textEl = document.getElementById('call-status-text');
            if (!modal || !textEl) return;
            textEl.textContent = msg || '';
            _showOnly('call-state-status');
            modal.style.display = 'flex';
            _cancelStatusTimer();
            _statusTimer = setTimeout(function () { window.CallModal.hide(); }, 3000);
        },

        minimize: function () {
            var modal = document.getElementById('call-modal');
            var pill  = document.getElementById('call-minimized');
            if (modal) modal.style.display = 'none';
            if (pill)  pill.style.display  = 'flex';
        },

        expand: function () {
            var pill  = document.getElementById('call-minimized');
            var modal = document.getElementById('call-modal');
            if (pill)  pill.style.display  = 'none';
            if (modal) modal.style.display = 'flex';
        },

        hide: function () {
            _cancelStatusTimer();
            var modal = document.getElementById('call-modal');
            var pill  = document.getElementById('call-minimized');
            if (modal) modal.style.display = 'none';
            if (pill)  pill.style.display  = 'none';
        },

        updateTimer: function (seconds) {
            var f = _formatTime(seconds);
            var t = document.getElementById('call-timer');
            var p = document.getElementById('call-minimized-timer');
            if (t) t.textContent = f;
            if (p) p.textContent = f;
        },

        setMuted: function (muted) {
            var btn = document.getElementById('call-btn-mute');
            if (!btn) return;
            var icon = btn.querySelector('i');
            btn.classList.toggle('is-muted', muted);
            btn.setAttribute('aria-pressed', String(muted));
            if (icon) icon.className = muted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone';
        },

        setSpeaker: function (on) {
            var btn = document.getElementById('call-btn-speaker');
            if (!btn) return;
            var icon = btn.querySelector('i');
            btn.classList.toggle('is-speaker-on', on);
            btn.style.color = on ? 'var(--primary,#818cf8)' : '';
            if (icon) icon.className = on ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
        },
    };
})();
</script>
