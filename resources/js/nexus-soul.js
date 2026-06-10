/**
 * Nexus Soul — haptics + sound feedback for key interactions.
 *
 * Two reasons sound is synthesized in code instead of shipped as files:
 *   1. No download. The whole module is < 4 KB minified.
 *   2. Programmatic — pitch / duration / envelope can vary per call so
 *      back-to-back likes don't sound robotic.
 *
 * Respect user preferences:
 *   - localStorage 'nexus_sound' = '0' | '1'  (default: 1 = on)
 *   - prefers-reduced-motion → still play sound (motion ≠ audio), but disable
 *     all haptic vibration patterns longer than a brief tap.
 */
(function () {
    'use strict';

    if (window.NexusSoul) return; // idempotent — surviving SPA-style page swaps

    // ──────────────────────────────────────────────────────────────────────────
    //  Haptics
    // ──────────────────────────────────────────────────────────────────────────
    const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const canVibrate = typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function';

    function vib(pattern) {
        if (!canVibrate) return false;
        if (reducedMotion) {
            pattern = (Array.isArray(pattern) ? pattern : [pattern])
                .map(v => Math.min(v, 12));
        }
        try { return navigator.vibrate(pattern); } catch (_) { return false; }
    }

    const Haptics = {
        // Feather tap — minimal confirmations
        feather:      () => vib(5),
        // Light tap — subtle confirmations (button press, picker open)
        light:        () => vib(8),
        // Medium — significant action (post sent, follow, react)
        impact:       (kind) => {
            if (kind === 'light')  return vib(10);
            if (kind === 'heavy')  return vib([20, 8, 20]);
            return vib(16);
        },
        // Sharp double — selection or toggle change
        selection:    () => vib([6, 4, 8]),
        // Like — single crisp tap
        like:         () => vib(12),
        // Unlike — soft double tap (reversed feel)
        unlike:       () => vib([6, 10, 6]),
        // React — firm single with slight tail
        react:        () => vib([18, 4, 8]),
        // Success — three escalating taps
        success:      () => vib([8, 20, 10, 20, 16]),
        // Post sent — strong then soft
        post:         () => vib([20, 10, 10]),
        // Delete — two firm pulses (destructive feel)
        delete:       () => vib([30, 40, 30]),
        // Warning — single longer pulse
        warning:      () => vib(30),
        // Error — two firm pulses
        error:        () => vib([40, 60, 40]),
        // Notification arrival — gentle double tap
        notification: () => vib([10, 60, 14]),
        // Bookmark — crisp double
        bookmark:     () => vib([8, 6, 12]),
    };

    // ──────────────────────────────────────────────────────────────────────────
    //  Sound
    // ──────────────────────────────────────────────────────────────────────────
    let ctx = null;
    let masterGain = null;
    let unlocked = false;

    const SOUND_KEY = 'nexus_sound';
    const enabled = () => localStorage.getItem(SOUND_KEY) !== '0';
    const isDND   = () => localStorage.getItem('nexus_dnd_enabled') === 'true';

    function ensureCtx() {
        if (ctx) return ctx;
        const C = window.AudioContext || window.webkitAudioContext;
        if (!C) return null;
        try {
            ctx = new C();
            masterGain = ctx.createGain();
            masterGain.gain.value = 0.18;
            masterGain.connect(ctx.destination);
        } catch (_) { ctx = null; }
        return ctx;
    }

    function unlock() {
        if (unlocked) return;
        const c = ensureCtx();
        if (!c) return;
        if (c.state === 'suspended') c.resume().catch(() => {});
        unlocked = true;
    }
    ['pointerdown', 'touchstart', 'keydown'].forEach(ev => {
        window.addEventListener(ev, unlock, { once: true, passive: true });
    });

    function _scheduleTone(c, o) {
        const now = c.currentTime;
        const osc = c.createOscillator();
        const g = c.createGain();
        osc.type = o.type || 'sine';
        osc.frequency.setValueAtTime(o.freq, now);
        if (o.freqEnd) {
            osc.frequency.exponentialRampToValueAtTime(o.freqEnd, now + o.dur);
        }
        g.gain.setValueAtTime(0, now);
        g.gain.linearRampToValueAtTime(o.peak ?? 0.6, now + (o.attack ?? 0.005));
        g.gain.exponentialRampToValueAtTime(0.0001, now + o.dur);
        osc.connect(g).connect(masterGain);
        osc.start(now);
        osc.stop(now + o.dur + 0.02);
    }

    function tone(o) {
        if (!enabled()) return;
        const c = ensureCtx();
        if (!c) return;
        if (c.state === 'suspended') {
            c.resume().then(() => _scheduleTone(c, o)).catch(() => {});
            return;
        }
        _scheduleTone(c, o);
    }

    const Sounds = {
        // Soft heart pop — like
        pop:      () => {
            tone({ freq: 580, freqEnd: 520, dur: 0.09, type: 'triangle', peak: 0.55, attack: 0.003 });
        },
        // Un-pop — unlike (reversed pitch)
        unpop:    () => tone({ freq: 420, freqEnd: 340, dur: 0.09, type: 'triangle', peak: 0.35, attack: 0.003 }),
        // Crisp tick — selection, toggle, bookmark
        tick:     () => tone({ freq: 1100, dur: 0.035, type: 'square', peak: 0.16, attack: 0.001 }),
        // Bookmark — two-tone click
        bookmark: () => {
            tone({ freq: 900, dur: 0.04, type: 'square', peak: 0.18, attack: 0.001 });
            setTimeout(() => tone({ freq: 1200, dur: 0.04, type: 'square', peak: 0.14, attack: 0.001 }), 50);
        },
        // Send — outgoing message / post (rising glide)
        send:     () => tone({ freq: 440, freqEnd: 820, dur: 0.16, type: 'triangle', peak: 0.42, attack: 0.005 }),
        // Receive — incoming message / notification (falling chime, two voices)
        receive:  () => {
            tone({ freq: 880, dur: 0.14, type: 'sine', peak: 0.38, attack: 0.005 });
            setTimeout(() => tone({ freq: 660, dur: 0.16, type: 'sine', peak: 0.28, attack: 0.005 }), 75);
        },
        // Success — three-step rising chord
        success:  () => {
            tone({ freq: 523, dur: 0.09, type: 'sine', peak: 0.38 });
            setTimeout(() => tone({ freq: 659, dur: 0.09, type: 'sine', peak: 0.38 }),  85);
            setTimeout(() => tone({ freq: 784, dur: 0.13, type: 'sine', peak: 0.42 }), 170);
        },
        // Delete — low thud
        delete:   () => tone({ freq: 200, freqEnd: 100, dur: 0.18, type: 'triangle', peak: 0.40 }),
        // Error — low downward glide
        error:    () => tone({ freq: 300, freqEnd: 160, dur: 0.22, type: 'triangle', peak: 0.42 }),
        // Whoosh — something exits / closes
        whoosh:   () => tone({ freq: 240, freqEnd: 130, dur: 0.13, type: 'sine', peak: 0.28 }),
        // React — warm chord hit
        react:    () => {
            tone({ freq: 560, dur: 0.10, type: 'triangle', peak: 0.50, attack: 0.003 });
            setTimeout(() => tone({ freq: 700, dur: 0.10, type: 'sine', peak: 0.30, attack: 0.005 }), 20);
        },
    };

    // ──────────────────────────────────────────────────────────────────────────
    //  High-level intents — single call per interaction, both layers fire.
    // ──────────────────────────────────────────────────────────────────────────
    const Feedback = {
        like:         () => { Haptics.like();           Sounds.pop();      },
        unlike:       () => { Haptics.unlike();         Sounds.unpop();    },
        react:        () => { Haptics.react();          Sounds.react();    },
        unreact:      () => { Haptics.unlike();         Sounds.whoosh();   },
        save:         () => { Haptics.bookmark();       Sounds.bookmark(); },
        unsave:       () => { Haptics.light();          Sounds.whoosh();   },
        follow:       () => { Haptics.impact('light');  Sounds.pop();      },
        unfollow:     () => { Haptics.light();          Sounds.whoosh();   },
        send:         () => { Haptics.impact();         Sounds.send();     },
        receive:      () => {                            Sounds.receive();  },
        post:         () => { Haptics.post();           Sounds.success();  },
        comment:      () => { Haptics.light();          Sounds.tick();     },
        delete:       () => { Haptics.delete();         Sounds.delete();   },
        notification: () => { if (!isDND()) { Haptics.notification(); Sounds.receive(); } },
        select:       () => { Haptics.selection();      Sounds.tick();     },
        error:        () => { Haptics.error();          Sounds.error();    },
        open:         () => { Haptics.feather();        Sounds.tick();     },
        close:        () => {                            Sounds.whoosh();   },
    };

    function setEnabled(on) {
        localStorage.setItem(SOUND_KEY, on ? '1' : '0');
    }

    window.NexusHaptics = Haptics;
    window.NexusSounds  = Sounds;
    window.NexusSoul    = {
        haptics: Haptics,
        sounds:  Sounds,
        feedback: Feedback,
        enabled,
        setEnabled,
    };
    window.nx = window.NexusSoul;
})();
