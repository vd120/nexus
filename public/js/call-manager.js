/**
 * CallManager — WebRTC 1-to-1 audio call manager
 * Integrates with window.socket (Socket.IO) and window.CallModal
 * Loaded as a plain browser script; no imports/exports needed.
 */

(function () {
    'use strict';

    // ---------------------------------------------------------------------------
    // Constants
    // ---------------------------------------------------------------------------

    // If the Blade template sets window.CALL_CONFIG.iceServers (from server-side env),
    // that list is merged in so TURN credentials can be injected without editing this file.
    const ICE_SERVERS = (function () {
        var base = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun.cloudflare.com:3478' },
        ];
        var extra = (window.CALL_CONFIG && Array.isArray(window.CALL_CONFIG.iceServers))
            ? window.CALL_CONFIG.iceServers : [];
        return base.concat(extra);
    })();

    // ---------------------------------------------------------------------------
    // Internal state
    // ---------------------------------------------------------------------------

    let currentCallId      = null;
    let targetUserId       = null;
    let localStream        = null;
    let peerConnection     = null;
    let callStartTime      = null;
    let timerInterval      = null;
    let noAnswerTimeout    = null;   // client-side 30s no-answer guard
    let remoteDescSet      = false;  // true after setRemoteDescription completes
    let iceCandidateBuffer = [];     // candidates received before remoteDesc was set
    let speakerEnabled     = true;   // loudspeaker toggle state

    /** Data stored when an incoming call arrives, used when accepting. */
    let pendingIncomingCall = null;

    // ---------------------------------------------------------------------------
    // CSRF helper
    // ---------------------------------------------------------------------------

    const getCsrf = () =>
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ---------------------------------------------------------------------------
    // Remote audio element
    // ---------------------------------------------------------------------------

    function getOrCreateRemoteAudio() {
        let el = document.getElementById('call-remote-audio');
        if (!el) {
            el = document.createElement('audio');
            el.id = 'call-remote-audio';
            // Visually hidden but in render tree — display:none blocks audio on some mobile browsers
            el.style.cssText = 'position:absolute;width:0;height:0;opacity:0;pointer-events:none;';
            document.body.appendChild(el);
        }
        el.autoplay = true;
        el.muted    = false;
        el.volume   = 1.0;
        el.setAttribute('playsinline', '');
        return el;
    }

    // ---------------------------------------------------------------------------
    // Ringtone — Web Audio API primary path, <audio> element fallback
    //
    // WHY Web Audio API:
    //   AudioContext, once resumed inside a user gesture, stays 'running' for the
    //   entire page session — even when audio is triggered later from a socket event.
    //   An <audio> element's autoplay permission is per-play-call on mobile and can
    //   be silently revoked after background/foreground cycles, making ring blocked
    //   even when the user has previously interacted with the page.
    // ---------------------------------------------------------------------------

    let ringtoneActive  = false;
    let vibrateTimer    = null;
    let ringSourceNode  = null;   // active Web Audio BufferSourceNode while ringing

    var _audioCtx   = null;
    var _ringBuffer = null;   // decoded AudioBuffer, built once when ctx first runs

    function getAudioCtx() {
        if (!_audioCtx) {
            try {
                _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {}
        }
        return _audioCtx;
    }

    // Build raw PCM ring buffer directly inside the AudioContext — no WAV encoding needed.
    function buildRingBuffer(ctx) {
        var SR         = ctx.sampleRate;
        var ringLen    = Math.ceil(SR * 1.0);
        var totalLen   = Math.ceil(SR * 3.0);   // 1s tone + 2s silence
        var buf        = ctx.createBuffer(1, totalLen, SR);
        var data       = buf.getChannelData(0);
        for (var i = 0; i < ringLen; i++) {
            var t    = i / SR;
            var fade = i < SR * 0.04 ? i / (SR * 0.04) :
                       i > ringLen - SR * 0.04 ? (ringLen - i) / (SR * 0.04) : 1.0;
            data[i]  = 0.28 * fade * (Math.sin(2 * Math.PI * 440 * t) + Math.sin(2 * Math.PI * 480 * t)) * 0.5;
        }
        return buf;
    }

    function _ensureRingBuffer() {
        var ctx = getAudioCtx();
        if (ctx && !_ringBuffer && ctx.state === 'running') {
            try { _ringBuffer = buildRingBuffer(ctx); } catch (e) {}
        }
    }

    function _playRingViaWebAudio() {
        var ctx = getAudioCtx();
        if (!ctx || !_ringBuffer) return false;
        if (ringSourceNode) {
            try { ringSourceNode.stop(); } catch (e) {}
            ringSourceNode = null;
        }
        var src  = ctx.createBufferSource();
        src.buffer = _ringBuffer;
        src.loop   = true;
        src.connect(ctx.destination);
        src.start(0);
        ringSourceNode = src;
        return true;
    }

    // WAV data URI kept for the <audio> fallback element only.
    var RING_SRC = (function () {
        var SR = 22050, ringLen = Math.ceil(SR * 1.0), totalLen = Math.ceil(SR * 3.0);
        var ab = new ArrayBuffer(44 + totalLen * 2), v = new DataView(ab);
        function ws(o, s) { for (var i = 0; i < s.length; i++) v.setUint8(o + i, s.charCodeAt(i)); }
        ws(0,'RIFF'); v.setUint32(4, 36 + totalLen * 2, true);
        ws(8,'WAVE'); ws(12,'fmt ');
        v.setUint32(16, 16, true); v.setUint16(20, 1, true); v.setUint16(22, 1, true);
        v.setUint32(24, SR, true); v.setUint32(28, SR * 2, true);
        v.setUint16(32, 2, true); v.setUint16(34, 16, true);
        ws(36,'data'); v.setUint32(40, totalLen * 2, true);
        for (var i = 0; i < ringLen; i++) {
            var t = i / SR;
            var fade = i < SR * 0.04 ? i / (SR * 0.04) : i > ringLen - SR * 0.04 ? (ringLen - i) / (SR * 0.04) : 1.0;
            var s = 0.28 * fade * (Math.sin(2 * Math.PI * 440 * t) + Math.sin(2 * Math.PI * 480 * t)) * 0.5;
            v.setInt16(44 + i * 2, Math.round(Math.max(-1, Math.min(1, s)) * 32767), true);
        }
        var bytes = new Uint8Array(ab), bin = '', chunk = 8192;
        for (var j = 0; j < bytes.length; j += chunk)
            bin += String.fromCharCode.apply(null, bytes.subarray(j, j + chunk));
        return 'data:audio/wav;base64,' + btoa(bin);
    })();

    var ringAudio = (function () {
        var el = document.createElement('audio');
        el.src     = RING_SRC;
        el.loop    = true;
        el.volume  = 0.9;
        el.preload = 'none';
        el.setAttribute('playsinline', '');
        document.body.appendChild(el);
        return el;
    })();

    function _resumeCtxAndBuildBuffer() {
        var ctx = getAudioCtx();
        if (!ctx) return;
        if (ctx.state === 'suspended') {
            ctx.resume().then(function () {
                _ensureRingBuffer();
                // If ringing but Web Audio hadn't started yet, start it now
                if (ringtoneActive && !ringSourceNode) {
                    _playRingViaWebAudio();
                    ringAudio.pause();
                    ringAudio.currentTime = 0;
                }
            }).catch(function () {});
        } else {
            _ensureRingBuffer();
        }
    }

    function tryUnlockElement(el) {
        if (!el || el.dataset.unlocked === 'resolved') return;
        el.play().then(function () {
            el.dataset.unlocked = 'resolved';
            // Keep playing if ring is active or remote audio has a live stream
            if (el === ringAudio && ringtoneActive) return;
            if (el === document.getElementById('call-remote-audio') && el.srcObject) return;
            el.pause();
            el.currentTime = 0;
        }).catch(function () {
            delete el.dataset.unlocked;
        });
    }

    function onGesture() {
        // Resume AudioContext — this is the critical unlock for ring-without-touch.
        // Once resumed inside a gesture, the context stays 'running' for the page lifetime.
        _resumeCtxAndBuildBuffer();

        // Fallback: also unlock <audio> elements for browsers without Web Audio API
        if (ringAudio.dataset.unlocked !== 'resolved') tryUnlockElement(ringAudio);
        var rem = document.getElementById('call-remote-audio');
        if (rem && rem.dataset.unlocked !== 'resolved') tryUnlockElement(rem);
    }

    ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(function (evt) {
        document.addEventListener(evt, onGesture, { passive: true });
    });

    // ---------------------------------------------------------------------------
    // Audio unlock banner
    // Mobile browsers block all audio until the first user gesture. We show a
    // small persistent banner asking the user to tap once to enable call audio.
    // It is dismissed on first interaction and never shown again (sessionStorage).
    // ---------------------------------------------------------------------------
    (function () {
        // Only show on touch devices and only if not already unlocked this session
        var isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
        if (!isTouchDevice) return;
        if (sessionStorage.getItem('_callAudioUnlocked') === '1') return;

        var banner = document.createElement('div');
        banner.id = '_call-audio-banner';
        banner.setAttribute('role', 'alert');
        banner.style.cssText = [
            'position:fixed',
            'bottom:calc(56px + env(safe-area-inset-bottom, 8px) + 8px)',
            'left:50%',
            'transform:translateX(-50%)',
            'z-index:99999',
            'background:rgba(30,30,30,0.92)',
            'color:#fff',
            'font-size:13px',
            'font-family:system-ui,sans-serif',
            'padding:10px 18px',
            'border-radius:24px',
            'box-shadow:0 4px 18px rgba(0,0,0,0.35)',
            'cursor:pointer',
            'white-space:nowrap',
            'backdrop-filter:blur(6px)',
            '-webkit-backdrop-filter:blur(6px)',
            'border:1px solid rgba(255,255,255,0.12)',
            'transition:opacity 0.4s',
            'display:flex',
            'align-items:center',
            'gap:8px',
        ].join(';');

        banner.innerHTML = '<span style="font-size:17px">🔔</span><span>Tap to enable call audio</span>';

        function dismissBanner() {
            sessionStorage.setItem('_callAudioUnlocked', '1');
            banner.style.opacity = '0';
            setTimeout(function () {
                if (banner.parentNode) banner.parentNode.removeChild(banner);
            }, 420);
        }

        // Dismiss on any real gesture — onGesture will handle the actual AudioContext unlock
        ['click', 'touchend', 'pointerdown'].forEach(function (e) {
            document.addEventListener(e, dismissBanner, { once: true, passive: true });
        });

        // Show after a short delay so it doesn't flash on every navigation
        setTimeout(function () {
            if (sessionStorage.getItem('_callAudioUnlocked') !== '1') {
                document.body.appendChild(banner);
            }
        }, 1200);
    })();

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState !== 'visible') return;
        // App came to foreground — resume context (iOS/Android suspend it on backgrounding)
        _resumeCtxAndBuildBuffer();
        if (ringtoneActive) {
            if (_audioCtx && _audioCtx.state === 'running') {
                if (!ringSourceNode) _playRingViaWebAudio();
            } else if (ringAudio.paused) {
                ringAudio.play().catch(function () {});
            }
        }
    });

    function startVibration() {
        if (!navigator.vibrate) return;
        function buzz() {
            if (!ringtoneActive) return;
            navigator.vibrate([700, 400, 700]);
            vibrateTimer = setTimeout(buzz, 3000);
        }
        buzz();
    }

    function stopVibration() {
        if (navigator.vibrate) navigator.vibrate(0);
        if (vibrateTimer) { clearTimeout(vibrateTimer); vibrateTimer = null; }
    }

    function _showRingUnblockBtn(show) {
        var btn = document.getElementById('call-ring-unblock-btn');
        if (btn) btn.style.display = show ? 'flex' : 'none';
    }

    // Called when user taps the in-modal "Tap to hear ringtone" button
    window._callUnblockRing = function () {
        _showRingUnblockBtn(false);
        sessionStorage.setItem('_callAudioUnlocked', '1');
        var banner = document.getElementById('_call-audio-banner');
        if (banner && banner.parentNode) banner.parentNode.removeChild(banner);

        var ctx = getAudioCtx();
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().then(function () {
                _ensureRingBuffer();
                if (ringtoneActive && !ringSourceNode) {
                    _playRingViaWebAudio();
                    ringAudio.pause();
                    ringAudio.currentTime = 0;
                }
            }).catch(function () {});
        }
        // Also unlock <audio> fallback
        if (ringAudio.dataset.unlocked !== 'resolved') {
            tryUnlockElement(ringAudio);
        } else if (ringtoneActive && ringAudio.paused && !ringSourceNode) {
            ringAudio.play().catch(function () {});
        }
    };

    function startRingtone() {
        if (ringtoneActive) return;
        ringtoneActive = true;
        startVibration();

        // Primary: Web Audio API — works immediately if context is already running
        // (which it is whenever the user has previously tapped anything on the page)
        var ctx = getAudioCtx();
        if (ctx && ctx.state === 'running') {
            _ensureRingBuffer();
            if (_playRingViaWebAudio()) {
                _showRingUnblockBtn(false);
                return;   // success — skip <audio> fallback
            }
        }

        // Context is suspended (no prior gesture on this page load) — try to resume.
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().then(function () {
                if (!ringtoneActive) return;
                _ensureRingBuffer();
                if (_playRingViaWebAudio()) {
                    _showRingUnblockBtn(false);
                    ringAudio.pause();
                    ringAudio.currentTime = 0;
                }
            }).catch(function () {});
        }

        // Fallback: <audio> element
        ringAudio.currentTime = 0;
        var p = ringAudio.play();
        if (p) p.catch(function (err) {
            console.warn('[CallManager] Ring blocked:', err.message);
            delete ringAudio.dataset.unlocked;
            // Audio is blocked — show the in-modal tap-to-ring button
            _showRingUnblockBtn(true);
        });
    }

    function stopRingtone() {
        ringtoneActive = false;
        stopVibration();
        _showRingUnblockBtn(false);
        if (ringSourceNode) {
            try { ringSourceNode.stop(); } catch (e) {}
            ringSourceNode = null;
        }
        ringAudio.pause();
        ringAudio.currentTime = 0;
    }

    function unlockAudio() {
        // Intentionally empty — unlock happens only via real user gesture (onGesture listeners above).
        // Do NOT call onGesture() speculatively here: a failed play() before any gesture would
        // leave dataset.unlocked cleared and the element looking perpetually locked.
    }

    function unlockRemoteAudio() {
        var rem = document.getElementById('call-remote-audio');
        if (rem && rem.dataset.unlocked !== 'resolved') tryUnlockElement(rem);
    }

    // ---------------------------------------------------------------------------
    // Call timer
    // ---------------------------------------------------------------------------

    function startTimer() {
        callStartTime = Date.now();
        timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            if (window.CallModal && typeof window.CallModal.updateTimer === 'function') {
                window.CallModal.updateTimer(elapsed);
            }
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        callStartTime = null;
    }

    // ---------------------------------------------------------------------------
    // RTCPeerConnection factory
    // ---------------------------------------------------------------------------

    function createPeerConnection(socket) {
        const pc = new RTCPeerConnection({ iceServers: ICE_SERVERS });

        pc.onicecandidate = (event) => {
            if (event.candidate && targetUserId && currentCallId) {
                socket.emit('call:ice-candidate', {
                    targetUserId : targetUserId,
                    callId       : currentCallId,
                    candidate    : event.candidate,
                });
            }
        };

        pc.ontrack = (event) => {
            const remoteAudio = getOrCreateRemoteAudio();

            if (event.streams && event.streams[0]) {
                // Normal path — browser provides a full MediaStream
                remoteAudio.srcObject = event.streams[0];
            } else if (event.track) {
                // Fallback — some browsers (older Safari, some mobile) fire
                // ontrack per-track without a streams array
                if (!remoteAudio.srcObject) {
                    remoteAudio.srcObject = new MediaStream();
                }
                remoteAudio.srcObject.addTrack(event.track);
            }

            // Must call play() explicitly — browsers block autoplay on
            // programmatic srcObject assignment, especially on mobile
            remoteAudio.play().catch((err) => {
                console.warn('[CallManager] Remote audio play() blocked:', err);
                // Retry on any user interaction — covers mute/speaker/end button taps
                var _retryEvts = ['click', 'touchstart', 'pointerdown', 'keydown'];
                var retryPlay = function () {
                    remoteAudio.play().then(function () {
                        _retryEvts.forEach(function (e) {
                            document.removeEventListener(e, retryPlay);
                        });
                    }).catch(function () {});
                };
                _retryEvts.forEach(function (e) {
                    document.addEventListener(e, retryPlay, { passive: true });
                });
            });
        };

        let iceDisconnectTimer = null;
        pc.oniceconnectionstatechange = () => {
            const state = pc.iceConnectionState;
            if (state === 'failed') {
                // Give a 5-second grace period in case ICE recovers on its own
                // (common with STUN when a candidate pair is tried and fails before another succeeds)
                if (!pc._iceFailedTimer) {
                    pc._iceFailedTimer = setTimeout(() => {
                        if (peerConnection === pc &&
                            pc.iceConnectionState !== 'connected' &&
                            pc.iceConnectionState !== 'completed') {
                            endCall(socket);
                        }
                    }, 5000);
                }
            } else if (state === 'disconnected') {
                // Give 8 seconds for transient network hiccups to recover before tearing down
                iceDisconnectTimer = setTimeout(() => {
                    if (pc.iceConnectionState !== 'connected' && pc.iceConnectionState !== 'completed') {
                        endCall(socket);
                    }
                }, 8000);
            } else if (state === 'connected' || state === 'completed') {
                if (iceDisconnectTimer) { clearTimeout(iceDisconnectTimer); iceDisconnectTimer = null; }
                if (pc._iceFailedTimer)  { clearTimeout(pc._iceFailedTimer); pc._iceFailedTimer = null; }
                // ICE is up — make absolutely sure the remote audio is playing.
                // The autoplay grace period may have expired since the user's last gesture,
                // so we re-attempt play() here when we know there is an active connection.
                var remAudio = document.getElementById('call-remote-audio');
                if (remAudio && remAudio.srcObject && remAudio.paused) {
                    remAudio.play().catch(function () {});
                }
            }
        };

        return pc;
    }

    // ---------------------------------------------------------------------------
    // Get user media helper
    // ---------------------------------------------------------------------------

    async function getLocalStream() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            localStream = stream;
            return stream;
        } catch (err) {
            const t = window.CallTranslations || {};
            const msg = (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError')
                ? (t.call_mic_denied   || 'Microphone access denied.')
                : (t.call_start_failed || 'Could not start the call.');
            if (window.CallModal) window.CallModal.showStatus(msg);
            throw err;
        }
    }

    function addLocalTracksToPc(pc, stream) {
        stream.getTracks().forEach((track) => pc.addTrack(track, stream));
    }

    // ---------------------------------------------------------------------------
    // Cleanup helper
    // ---------------------------------------------------------------------------

    function cleanupPeerConnection() {
        if (localStream) {
            localStream.getTracks().forEach((t) => t.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        const remoteAudio = document.getElementById('call-remote-audio');
        if (remoteAudio) {
            remoteAudio.srcObject = null;
        }
    }

    function clearNoAnswerTimeout() {
        if (noAnswerTimeout) { clearTimeout(noAnswerTimeout); noAnswerTimeout = null; }
    }

    async function flushIceCandidateBuffer() {
        if (!peerConnection || iceCandidateBuffer.length === 0) return;
        const toFlush = iceCandidateBuffer.splice(0);
        for (const candidate of toFlush) {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (err) {
                console.warn('[CallManager] Failed to add buffered ICE candidate:', err);
            }
        }
    }

    function resetState() {
        clearNoAnswerTimeout();
        currentCallId       = null;
        targetUserId        = null;
        pendingIncomingCall = null;
        remoteDescSet       = false;
        iceCandidateBuffer  = [];
        speakerEnabled      = true;
    }

    // ---------------------------------------------------------------------------
    // POST /call/{id}/end
    // ---------------------------------------------------------------------------

    async function postCallEnd(callId) {
        if (!callId) return;
        try {
            await fetch(`/call/${callId}/end`, {
                method  : 'POST',
                headers : {
                    'Content-Type'     : 'application/json',
                    'X-CSRF-TOKEN'     : getCsrf(),
                    'X-Requested-With' : 'XMLHttpRequest',
                },
            });
        } catch (err) {
            console.warn('[CallManager] Failed to POST call end:', err);
        }
    }

    // ---------------------------------------------------------------------------
    // Public: endCall
    // ---------------------------------------------------------------------------

    function endCall(socket) {
        stopTimer();
        stopRingtone();

        const callId = currentCallId;

        // Emit call:end to socket BEFORE closing peer connection so userInCall
        // is cleaned up on the socket server immediately — prevents the server's
        // disconnect cleanup from emitting a spurious call:ended to the other party
        if (socket && targetUserId && callId) {
            socket.emit('call:end', { targetUserId: targetUserId, callId: callId });
        }

        cleanupPeerConnection();
        resetState();

        if (window.CallModal && typeof window.CallModal.hide === 'function') {
            window.CallModal.hide();
        }

        if (callId) {
            postCallEnd(callId);
        }
    }

    // ---------------------------------------------------------------------------
    // Public: startCall (caller side, after POST /call/initiate)
    // ---------------------------------------------------------------------------

    function startCall(callId, toUserId, conversationId, calleeName, calleeAvatar, calleeUsername, calleeVerified) {
        currentCallId = callId;
        targetUserId  = toUserId;

        // Pre-unlock remote audio inside this user-gesture context (button click)
        const remoteAudio = getOrCreateRemoteAudio();
        remoteAudio.dataset.unlocked = '1';
        remoteAudio.play().catch(function () {});

        // Show outgoing "Calling..." UI immediately
        if (window.CallModal && typeof window.CallModal.showCalling === 'function') {
            window.CallModal.showCalling({
                calleeName:     calleeName     || '',
                calleeAvatar:   calleeAvatar   || '',
                calleeUsername: calleeUsername || '',
                calleeVerified: !!calleeVerified,
            });
        }

        // Client-side 30-second no-answer guard (covers sync queue driver
        // and cases where the server-side job is not running)
        clearNoAnswerTimeout();
        noAnswerTimeout = setTimeout(function () {
            const t = window.CallTranslations || {};
            cleanupPeerConnection();
            const cid = currentCallId;
            resetState();
            if (window.CallModal) window.CallModal.showStatus(t.call_no_answer || 'No answer');
            if (cid) postCallEnd(cid);
        }, 30000);
    }

    // ---------------------------------------------------------------------------
    // Socket event handlers
    // ---------------------------------------------------------------------------

    function attachSocketListeners(socket) {

        // ------------------------------------------------------------------
        // 1. Incoming call (callee receives this)
        // ------------------------------------------------------------------
        socket.on('call:incoming', (data) => {
            // data: { callId, callerId, callerName, callerAvatar, conversationId }
            pendingIncomingCall = data;
            currentCallId       = data.callId;
            targetUserId        = data.callerId;

            if (window.CallModal && typeof window.CallModal.showIncoming === 'function') {
                window.CallModal.showIncoming(data);
            }

            startRingtone();
        });

        // ------------------------------------------------------------------
        // 2. Call accepted (caller receives this after callee accepts)
        // ------------------------------------------------------------------
        socket.on('call:accepted', async (data) => {
            // data: { callId }
            stopRingtone();
            clearNoAnswerTimeout();   // callee answered — cancel client-side no-answer timer

            if (data.callId !== currentCallId) return;

            try {
                peerConnection = createPeerConnection(socket);

                const stream = await getLocalStream();
                addLocalTracksToPc(peerConnection, stream);

                const offer = await peerConnection.createOffer({ offerToReceiveAudio: true, offerToReceiveVideo: false });
                await peerConnection.setLocalDescription(offer);

                socket.emit('call:offer', {
                    targetUserId : targetUserId,
                    callId       : currentCallId,
                    sdp          : offer,
                });

                if (window.CallModal && typeof window.CallModal.showActive === 'function') {
                    window.CallModal.showActive();
                }
            } catch (err) {
                console.error('[CallManager] Error creating offer:', err);
                endCall(socket);
            }
        });

        // ------------------------------------------------------------------
        // 3. Call rejected (caller receives this)
        // ------------------------------------------------------------------
        socket.on('call:rejected', (data) => {
            if (!currentCallId) return;
            stopRingtone();

            const callId = currentCallId;
            cleanupPeerConnection();
            resetState();

            if (window.CallModal && typeof window.CallModal.showStatus === 'function') {
                window.CallModal.showStatus((window.CallTranslations || {}).call_declined || 'Call declined');
            }

            // Ensure server record is updated immediately (don't wait for 30s timeout)
            postCallEnd(callId);
        });

        // ------------------------------------------------------------------
        // 4. Offer received (callee receives this after accepting)
        // ------------------------------------------------------------------
        socket.on('call:offer', async (data) => {
            // data: { fromUserId, callId, sdp }
            if (data.callId !== currentCallId) return;

            try {
                peerConnection = createPeerConnection(socket);
                targetUserId   = data.fromUserId;

                const stream = await getLocalStream();
                addLocalTracksToPc(peerConnection, stream);

                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                remoteDescSet = true;
                await flushIceCandidateBuffer();   // apply any candidates that arrived early

                const answer = await peerConnection.createAnswer({ offerToReceiveAudio: true, offerToReceiveVideo: false });
                await peerConnection.setLocalDescription(answer);

                socket.emit('call:answer', {
                    targetUserId : data.fromUserId,
                    callId       : data.callId,
                    sdp          : answer,
                });

                startTimer();
            } catch (err) {
                console.error('[CallManager] Error creating answer:', err);
                endCall(socket);
            }
        });

        // ------------------------------------------------------------------
        // 5. Answer received (caller receives this)
        // ------------------------------------------------------------------
        socket.on('call:answer', async (data) => {
            // data: { fromUserId, callId, sdp }
            if (data.callId !== currentCallId) return;
            if (!peerConnection) return;

            try {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.sdp));
                remoteDescSet = true;
                await flushIceCandidateBuffer();   // apply any candidates that arrived early
                startTimer();
            } catch (err) {
                console.error('[CallManager] Error setting remote description:', err);
                endCall(socket);
            }
        });

        // ------------------------------------------------------------------
        // 6. ICE candidate exchange
        // ------------------------------------------------------------------
        socket.on('call:ice-candidate', async (data) => {
            // data: { fromUserId, callId, candidate }
            if (data.callId !== currentCallId) return;
            if (!data.candidate) return;

            // Buffer candidates that arrive before remote description is set —
            // this is the primary cause of "connection lost" after a few seconds
            if (!peerConnection || !remoteDescSet) {
                iceCandidateBuffer.push(data.candidate);
                return;
            }

            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
            } catch (err) {
                console.warn('[CallManager] Failed to add ICE candidate:', err);
            }
        });

        // ------------------------------------------------------------------
        // 7. Call ended remotely
        // ------------------------------------------------------------------
        socket.on('call:ended', (data) => {
            // data: { callId, reason }
            // Ignore stale events — can arrive after we already ended the call ourselves
            // (e.g. socket server's userInCall cleanup fires after the other party disconnects)
            if (!currentCallId) return;

            stopTimer();
            stopRingtone();
            cleanupPeerConnection();

            const callId = currentCallId;
            resetState();

            const t = window.CallTranslations || {};
            const message = (data && data.reason === 'disconnected')
                ? (t.call_lost   || 'Call ended (connection lost)')
                : (t.call_ended  || 'Call ended');

            if (window.CallModal && typeof window.CallModal.showStatus === 'function') {
                window.CallModal.showStatus(message);
            }

            if (callId) {
                postCallEnd(callId);
            }
        });

        // ------------------------------------------------------------------
        // 8. No answer
        // ------------------------------------------------------------------
        socket.on('call:no-answer', () => {
            if (!currentCallId) return;
            stopRingtone();
            cleanupPeerConnection();
            resetState();

            if (window.CallModal && typeof window.CallModal.showStatus === 'function') {
                window.CallModal.showStatus((window.CallTranslations || {}).call_no_answer || 'No answer');
            }
        });
    }

    // ---------------------------------------------------------------------------
    // PJAX navigation — keeps the call alive while the user browses
    // ---------------------------------------------------------------------------

    var _pjaxBusy = false;

    // Run <script> tags found inside a DOM element without triggering a page load.
    function _execScripts(container) {
        container.querySelectorAll('script').forEach(function (old) {
            var s = document.createElement('script');
            Array.from(old.attributes).forEach(function (a) { s.setAttribute(a.name, a.value); });
            if (old.src) {
                // External — skip if already on the page
                if (!document.querySelector('script[src="' + old.src + '"]')) {
                    document.body.appendChild(s);
                }
            } else {
                s.textContent = old.textContent;
                document.body.appendChild(s);
                document.body.removeChild(s);
            }
        });
    }

    // Swap page-specific stylesheets between PJAX navigations.
    // Global Vite bundle assets (same href already in <head>) are left untouched.
    // Previously-injected PJAX sheets are removed before adding new ones.
    function _swapHeadStyles(newDoc) {
        var head = document.head;

        // Collect hrefs already present in the live document
        var existing = {};
        head.querySelectorAll('link[rel="stylesheet"]').forEach(function (el) {
            existing[el.href] = true;
        });

        // Remove any <link>/<style> we injected during a previous PJAX navigation
        head.querySelectorAll('[data-pjax-injected]').forEach(function (el) {
            el.parentNode.removeChild(el);
        });

        // Add new <link rel="stylesheet"> tags from the fetched page's <head>
        // that are not already present (i.e. page-specific Vite CSS chunks)
        newDoc.head.querySelectorAll('link[rel="stylesheet"]').forEach(function (el) {
            if (!existing[el.href]) {
                var clone = el.cloneNode(true);
                clone.setAttribute('data-pjax-injected', '1');
                head.appendChild(clone);
            }
        });

        // Also carry over any inline <style> blocks pushed via @push('styles')
        newDoc.head.querySelectorAll('style').forEach(function (el) {
            var clone = el.cloneNode(true);
            clone.setAttribute('data-pjax-injected', '1');
            head.appendChild(clone);
        });
    }

    function pjaxNavigate(url) {
        if (_pjaxBusy) return;
        _pjaxBusy = true;

        // Leave the current conversation room so the server cleans up presence
        if (window.activeConversationId && window.NexusSocket) {
            window.NexusSocket.leaveConversation(window.activeConversationId);
        }

        var mainEl = document.getElementById('main-content');
        if (mainEl) mainEl.style.opacity = '0.4';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
        })
        .then(function (r) {
            if (!r.ok) throw new Error(r.status);
            return r.text();
        })
        .then(function (html) {
            var doc = (new DOMParser()).parseFromString(html, 'text/html');

            // ── Swap page-specific stylesheets FIRST so content renders styled ──
            _swapHeadStyles(doc);

            // ── Swap main content ──
            var newMain = doc.getElementById('main-content');
            if (mainEl && newMain) {
                mainEl.innerHTML = newMain.innerHTML;
                mainEl.className  = newMain.className;
                _execScripts(mainEl);
            }

            // ── Replay @stack('scripts') from the new page ──
            // Handles both a dedicated #pjax-scripts container (if present)
            // and any <script> tags found directly inside #main-content.
            var newPjax = doc.getElementById('pjax-scripts');
            var curPjax = document.getElementById('pjax-scripts');
            if (newPjax && curPjax) {
                curPjax.innerHTML = '';
                _execScripts(newPjax);
            }

            // ── Update browser chrome ──
            var titleEl = doc.querySelector('title');
            if (titleEl) document.title = titleEl.textContent;
            window.history.pushState({ pjax: true, url: url }, document.title, url);

            if (mainEl) mainEl.style.opacity = '';
            window.scrollTo(0, 0);
        })
        .catch(function () {
            // Fallback: proper end-call then hard navigate
            endCall(window.NexusSocket && window.NexusSocket.socket);
            window.location.href = url;
        })
        .finally(function () { _pjaxBusy = false; });
    }

    // Browser back/forward while inside a PJAX session
    window.addEventListener('popstate', function (e) {
        if (currentCallId && e.state && e.state.pjax) {
            pjaxNavigate(window.location.href);
        }
    });

    // Refresh / tab-close / external navigation — can't PJAX these, just warn
    window.addEventListener('beforeunload', function (e) {
        if (!currentCallId) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // In-app link clicks — PJAX them silently (no confirm needed)
    document.addEventListener('click', function (e) {
        if (!currentCallId) return;
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') ||
            href.startsWith('javascript:') || link.target === '_blank') return;

        e.preventDefault();
        pjaxNavigate(href);
    }, true);

    // ---------------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------------

    window.CallManager = {
        /**
         * Initialize CallManager, attach socket listeners, and check for a
         * pending incoming call that may have been missed during a page refresh.
         * @param {object} socket  Socket.IO client instance
         */
        init(socket) {
            if (!socket) {
                console.error('[CallManager] init() called without a socket instance.');
                return;
            }
            attachSocketListeners(socket);

            // Check if there's a call ringing for us that we missed due to refresh
            fetch('/call/pending', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.pending) {
                    // Replay call:incoming as if the socket event just arrived
                    pendingIncomingCall = data;
                    currentCallId       = data.callId;
                    targetUserId        = data.callerId;
                    if (window.CallModal && typeof window.CallModal.showIncoming === 'function') {
                        window.CallModal.showIncoming({
                            callId      : data.callId,
                            callerName  : data.callerName,
                            callerAvatar: data.callerAvatar,
                        });
                    }
                    startRingtone();
                }
            })
            .catch(function () {});
        },

        /**
         * Unlock AudioContext synchronously — must be called directly inside a
         * user gesture handler (onclick/ontouchstart), NOT inside a .then() callback.
         * Call this at the very start of any function triggered by a button click.
         */
        unlockAudioNow() {
            unlockAudio();
        },

        /** Called by the CALLER after POST /call/initiate succeeds. */
        startCall(callId, toUserId, conversationId, calleeName, calleeAvatar, calleeUsername, calleeVerified) {
            startCall(callId, toUserId, conversationId, calleeName, calleeAvatar, calleeUsername, calleeVerified);
        },

        /** Called by the CALLEE when they tap the Accept button. */
        acceptCall() {
            if (!pendingIncomingCall) return;
            stopRingtone();
            const { callId } = pendingIncomingCall;

            // Pre-unlock the remote audio element while inside this user-gesture
            // handler — critical for iOS Safari which blocks audio without gesture
            const remoteAudio = getOrCreateRemoteAudio();
            remoteAudio.dataset.unlocked = '1';
            remoteAudio.play().catch(function () {});

            fetch(`/call/${callId}/accept`, {
                method  : 'POST',
                headers : {
                    'Content-Type'     : 'application/json',
                    'X-CSRF-TOKEN'     : getCsrf(),
                    'X-Requested-With' : 'XMLHttpRequest',
                },
            }).catch((err) => console.warn('[CallManager] accept POST failed:', err));

            if (window.CallModal && typeof window.CallModal.showActive === 'function') {
                window.CallModal.showActive();
            }
            // RTCPeerConnection is created when call:offer arrives from the caller
        },

        /** Called by the CALLEE when they tap the Decline button. */
        rejectCall() {
            if (!pendingIncomingCall) return;
            stopRingtone();
            const { callId } = pendingIncomingCall;

            fetch(`/call/${callId}/reject`, {
                method  : 'POST',
                headers : {
                    'Content-Type'     : 'application/json',
                    'X-CSRF-TOKEN'     : getCsrf(),
                    'X-Requested-With' : 'XMLHttpRequest',
                },
            }).catch((err) => console.warn('[CallManager] reject POST failed:', err));

            cleanupPeerConnection();
            resetState();
            if (window.CallModal && typeof window.CallModal.hide === 'function') {
                window.CallModal.hide();
            }
        },

        /** Toggle microphone mute. */
        toggleMute() {
            if (!localStream) return;
            const audioTrack = localStream.getAudioTracks()[0];
            if (!audioTrack) return;
            audioTrack.enabled = !audioTrack.enabled;
            const muted = !audioTrack.enabled;
            if (window.CallModal && typeof window.CallModal.setMuted === 'function') {
                window.CallModal.setMuted(muted);
            }
        },

        /** Toggle speaker output (earpiece ↔ loudspeaker via setSinkId or volume). */
        async toggleSpeaker() {
            speakerEnabled = !speakerEnabled;
            const remoteAudio = document.getElementById('call-remote-audio');

            if (remoteAudio) {
                // setSinkId is supported on Chrome/Edge (desktop + Android Chrome).
                // On iOS Safari it's not available — we fall back to volume control.
                if (typeof remoteAudio.setSinkId === 'function') {
                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const speakers = devices.filter(d => d.kind === 'audiooutput');
                        // 'default' = system default (loudspeaker), '' = earpiece/first device
                        const target = speakerEnabled
                            ? (speakers.find(d => d.deviceId === 'default')?.deviceId || 'default')
                            : (speakers.find(d => d.deviceId !== 'default')?.deviceId || '');
                        await remoteAudio.setSinkId(target);
                    } catch (err) {
                        console.warn('[CallManager] setSinkId failed:', err);
                    }
                } else {
                    // Fallback: loudspeaker = full volume, earpiece simulation = 20% volume
                    remoteAudio.volume = speakerEnabled ? 1.0 : 0.2;
                }
            }

            if (window.CallModal && typeof window.CallModal.setSpeaker === 'function') {
                window.CallModal.setSpeaker(speakerEnabled);
            }
        },

        /** Hang up: clean up peer connection, stop streams, notify server. */
        endCall() {
            const socket = window.NexusSocket?.socket;
            endCall(socket);
        },
    };

})();
