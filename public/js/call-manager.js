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

    const ICE_SERVERS = [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        // TURN relay — required for users behind symmetric NAT (most mobile networks).
        // Using Open Relay (free, no sign-up): https://www.metered.ca/tools/openrelay/
        {
            urls: 'turn:openrelay.metered.ca:80',
            username: 'openrelayproject',
            credential: 'openrelayproject',
        },
        {
            urls: 'turn:openrelay.metered.ca:443',
            username: 'openrelayproject',
            credential: 'openrelayproject',
        },
        {
            urls: 'turn:openrelay.metered.ca:443?transport=tcp',
            username: 'openrelayproject',
            credential: 'openrelayproject',
        },
    ];

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
    // Ringtone
    // ---------------------------------------------------------------------------

    let ringtoneActive  = false;
    let vibrateTimer    = null;

    // Generate a two-tone ring WAV (440+480 Hz, 1s ring + 2s silence) synchronously
    // using typed arrays — no OfflineAudioContext, no async, no src swap ever.
    // The element keeps its autoplay-unlock state for the lifetime of the page because
    // src is set ONCE here and never changed. Changing src resets Chrome's unlock state.
    function generateRingDataURI() {
        var SR = 22050;
        var ringSeconds = 1.0;
        var silenceSeconds = 2.0;
        var totalSeconds = ringSeconds + silenceSeconds;
        var numSamples = Math.ceil(SR * totalSeconds);
        var ringLen = Math.ceil(SR * ringSeconds);

        var ab = new ArrayBuffer(44 + numSamples * 2);
        var v = new DataView(ab);

        function ws(o, s) { for (var i = 0; i < s.length; i++) v.setUint8(o + i, s.charCodeAt(i)); }
        ws(0,'RIFF'); v.setUint32(4, 36 + numSamples * 2, true);
        ws(8,'WAVE'); ws(12,'fmt ');
        v.setUint32(16, 16, true); v.setUint16(20, 1, true); v.setUint16(22, 1, true);
        v.setUint32(24, SR, true); v.setUint32(28, SR * 2, true);
        v.setUint16(32, 2, true); v.setUint16(34, 16, true);
        ws(36,'data'); v.setUint32(40, numSamples * 2, true);

        for (var i = 0; i < ringLen; i++) {
            var t = i / SR;
            var fade = i < SR * 0.04 ? i / (SR * 0.04) :
                       i > ringLen - SR * 0.04 ? (ringLen - i) / (SR * 0.04) : 1.0;
            var sample = 0.28 * fade * (Math.sin(2 * Math.PI * 440 * t) + Math.sin(2 * Math.PI * 480 * t)) * 0.5;
            var pcm = Math.round(Math.max(-1, Math.min(1, sample)) * 32767);
            v.setInt16(44 + i * 2, pcm, true);
        }
        // Silence samples (ringLen to numSamples) are already 0 from ArrayBuffer init

        var bytes = new Uint8Array(ab);
        var binary = '';
        var chunkSize = 8192;
        for (var j = 0; j < bytes.length; j += chunkSize) {
            binary += String.fromCharCode.apply(null, bytes.subarray(j, j + chunkSize));
        }
        return 'data:audio/wav;base64,' + btoa(binary);
    }

    // Set the real ring sound as src SYNCHRONOUSLY — never swap src again.
    // This prevents Chrome Android from resetting the per-element autoplay unlock state.
    var RING_SRC = generateRingDataURI();

    var ringAudio = (function () {
        var el = document.createElement('audio');
        el.src    = RING_SRC;   // real ring sound, set once, never changes
        el.loop   = true;
        el.volume = 0.9;
        el.setAttribute('playsinline', '');
        // preload=none: src is a data URI already in memory — no fetch needed.
        // Omitting load() prevents the browser from claiming an audio session at
        // page-load time, which would duck background music on iOS/Android.
        el.preload = 'none';
        document.body.appendChild(el);
        return el;
    })();

    // Only mark unlocked after play() actually RESOLVES — never before.
    // A failed play() clears the flag so the next gesture will retry correctly.
    function tryUnlockElement(el) {
        if (!el || el.dataset.unlocked === 'resolved') return;
        el.play().then(function () {
            el.pause();
            el.currentTime = 0;
            el.dataset.unlocked = 'resolved';   // only set on confirmed success
        }).catch(function () {
            // Gesture failed to unlock — clear any partial flag so next gesture retries
            delete el.dataset.unlocked;
        });
    }

    function onGesture() {
        if (ringAudio.dataset.unlocked !== 'resolved') tryUnlockElement(ringAudio);
        var rem = document.getElementById('call-remote-audio');
        if (rem && rem.dataset.unlocked !== 'resolved') tryUnlockElement(rem);
    }

    // No { once: true } — retry on every gesture until successfully unlocked.
    // No speculative onGesture() call at parse time — wait for a real user gesture.
    ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(function (evt) {
        document.addEventListener(evt, onGesture, { passive: true });
    });

    // Re-resume if user returns from background
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && ringtoneActive && ringAudio.paused) {
            ringAudio.play().catch(function () {});
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

    function startRingtone() {
        if (ringtoneActive) return;
        ringtoneActive = true;
        startVibration();

        ringAudio.currentTime = 0;
        var p = ringAudio.play();
        if (p) p.catch(function (err) {
            console.warn('[CallManager] Ring blocked (not yet unlocked):', err.message);
            // Retry on next gesture — onGesture listeners above will re-attempt unlock
        });
    }

    function stopRingtone() {
        ringtoneActive = false;
        stopVibration();
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
                // Last resort: try again on next user interaction
                const retryPlay = () => {
                    remoteAudio.play().catch(() => {});
                    document.removeEventListener('click',      retryPlay);
                    document.removeEventListener('touchstart', retryPlay);
                };
                document.addEventListener('click',      retryPlay, { once: true });
                document.addEventListener('touchstart', retryPlay, { once: true, passive: true });
            });
        };

        let iceDisconnectTimer = null;
        pc.oniceconnectionstatechange = () => {
            const state = pc.iceConnectionState;
            if (state === 'failed') {
                endCall(socket);
            } else if (state === 'disconnected') {
                // Give 8 seconds for transient network hiccups to recover before tearing down
                iceDisconnectTimer = setTimeout(() => {
                    if (pc.iceConnectionState !== 'connected' && pc.iceConnectionState !== 'completed') {
                        endCall(socket);
                    }
                }, 8000);
            } else if (state === 'connected' || state === 'completed') {
                if (iceDisconnectTimer) { clearTimeout(iceDisconnectTimer); iceDisconnectTimer = null; }
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

    function startCall(callId, toUserId, conversationId, calleeName, calleeAvatar) {
        currentCallId = callId;
        targetUserId  = toUserId;

        // Pre-unlock remote audio inside this user-gesture context (button click)
        const remoteAudio = getOrCreateRemoteAudio();
        remoteAudio.dataset.unlocked = '1';
        remoteAudio.play().catch(function () {});

        // Show outgoing "Calling..." UI immediately
        if (window.CallModal && typeof window.CallModal.showCalling === 'function') {
            window.CallModal.showCalling({ calleeName: calleeName || '', calleeAvatar: calleeAvatar || '' });
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

                const offer = await peerConnection.createOffer();
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

                const answer = await peerConnection.createAnswer();
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
        startCall(callId, toUserId, conversationId, calleeName, calleeAvatar) {
            startCall(callId, toUserId, conversationId, calleeName, calleeAvatar);
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
