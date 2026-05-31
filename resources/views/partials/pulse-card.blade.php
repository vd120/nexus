@php
    $variant = $variant ?? 'sidebar';
    try { $rsPulse = \App\Models\PulsePrompt::current(); } catch (\Throwable $e) { $rsPulse = null; }
    $rsPulseAnswered = false;
    $rsPulseCount = 0;
    if ($rsPulse) {
        $rsPulseCount = $rsPulse->answers()->count();
        if (auth()->check()) {
            $rsPulseAnswered = $rsPulse->answers()->where('user_id', auth()->id())->exists();
        }
    }
@endphp
@if($rsPulse)
{{-- Pre-paint check: if this pulse was dismissed in localStorage, inject a style
     rule BEFORE the section is parsed so it never flashes on screen. --}}
<script>
(function () {
    try {
        var ids = JSON.parse(localStorage.getItem('nexus_pulse_dismissed_ids') || '[]');
        if (ids.indexOf('{{ $rsPulse->id }}') !== -1) {
            var s = document.createElement('style');
            s.textContent = '[data-pulse-id="{{ $rsPulse->id }}"]{display:none!important}';
            (document.head || document.documentElement).appendChild(s);
        }
    } catch (e) { /* private mode / quota — ignore */ }
})();
</script>
<section class="fsr-card pulse-card {{ $variant === 'mobile' ? 'pulse-card-mobile' : '' }}" data-fsr-section="pulse" data-pulse-id="{{ $rsPulse->id }}" data-pulse-ends="{{ optional($rsPulse->ends_at)->toIso8601String() }}">
    <div class="pulse-card-bg" aria-hidden="true"></div>
    <button type="button" class="pulse-close" data-pulse-close aria-label="{{ __('messages.close') }}" title="{{ __('messages.close') }}">
        <i class="fas fa-times" aria-hidden="true"></i>
    </button>
    <header class="pulse-card-head">
        <span class="pulse-eyebrow">
            <span class="pulse-dot" aria-hidden="true"></span>
            {{ __('messages.pulse_today') }}
        </span>
        <span class="pulse-countdown" data-pulse-countdown>—</span>
    </header>
    <h3 class="pulse-question">{{ $rsPulse->question }}</h3>
    <footer class="pulse-card-foot">
        <a href="{{ route('pulse.index') }}" class="pulse-cta">
            @if($rsPulseAnswered)
                <i class="fas fa-check" aria-hidden="true"></i>
                <span>{{ __('messages.pulse_already_answered') }}</span>
            @else
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                <span>{{ __('messages.pulse_join_in') }}</span>
            @endif
        </a>
        <span class="pulse-count">
            <i class="fas fa-comment-dots" aria-hidden="true"></i>
            {{ number_format($rsPulseCount) }}
        </span>
    </footer>
</section>
<script>
// Pulse countdown + dismissal — idempotent across multiple include sites (sidebar + mobile).
if (!window._nexusPulseInit) {
    window._nexusPulseInit = true;
    document.addEventListener('DOMContentLoaded', function () {
        const cards = Array.from(document.querySelectorAll('[data-fsr-section="pulse"]'));
        if (!cards.length) return;
        const isAr = document.documentElement.lang === 'ar';

        // ----- Dismissal -----
        const STORE_KEY = 'nexus_pulse_dismissed_ids';
        function getDismissed() {
            try { return JSON.parse(localStorage.getItem(STORE_KEY) || '[]'); }
            catch (e) { return []; }
        }
        function setDismissed(ids) {
            try { localStorage.setItem(STORE_KEY, JSON.stringify(ids.slice(-20))); }
            catch (e) { /* quota / private mode — ignore */ }
        }
        function isDismissed(id) {
            return id && getDismissed().indexOf(String(id)) !== -1;
        }
        function markDismissed(id) {
            if (!id) return;
            const list = getDismissed();
            const sid = String(id);
            if (list.indexOf(sid) === -1) { list.push(sid); setDismissed(list); }
        }

        cards.forEach(card => {
            const pid = card.getAttribute('data-pulse-id');
            if (isDismissed(pid)) {
                card.remove();
                return;
            }
            const closeBtn = card.querySelector('[data-pulse-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    markDismissed(pid);
                    // Remove every instance of this pulse on the page (sidebar + mobile).
                    document.querySelectorAll('[data-fsr-section="pulse"][data-pulse-id="' + pid + '"]')
                        .forEach(el => {
                            el.classList.add('is-dismissing');
                            setTimeout(() => el.remove(), 180);
                        });
                });
            }
        });

        // ----- Countdown -----
        function tickCard(card) {
            const endsRaw = card.getAttribute('data-pulse-ends');
            const el = card.querySelector('[data-pulse-countdown]');
            if (!endsRaw || !el) return;
            const endsAt = new Date(endsRaw).getTime();
            const diff = endsAt - Date.now();
            if (diff <= 0) { el.textContent = '—'; return; }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            el.textContent = isAr ? (h + 'س ' + m + 'د') : (h + 'h ' + m + 'm');
        }
        function tickAll() {
            document.querySelectorAll('[data-fsr-section="pulse"]').forEach(tickCard);
        }
        tickAll();
        setInterval(tickAll, 60000);
    });
}
</script>
@endif
