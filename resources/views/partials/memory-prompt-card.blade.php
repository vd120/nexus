@php
    $variant = $variant ?? 'sidebar';
    try { $rsMemory = \App\Models\PulsePrompt::currentMemory(); } catch (\Throwable $e) { $rsMemory = null; }
    $rsMemoryAnswered = false;
    $rsMemoryCount = 0;
    if ($rsMemory) {
        $rsMemoryCount = $rsMemory->answers()->count();
        if (auth()->check()) {
            $rsMemoryAnswered = $rsMemory->answers()->where('user_id', auth()->id())->exists();
        }
    }
@endphp
@if($rsMemory)
{{-- Pre-paint check: if this memory was dismissed in localStorage, inject a style
     rule BEFORE the section is parsed so it never flashes on screen. --}}
<script>
(function () {
    try {
        var ids = JSON.parse(localStorage.getItem('nexus_memory_dismissed_ids') || '[]');
        if (ids.indexOf('{{ $rsMemory->id }}') !== -1) {
            var s = document.createElement('style');
            s.textContent = '[data-memory-id="{{ $rsMemory->id }}"]{display:none!important}';
            (document.head || document.documentElement).appendChild(s);
        }
    } catch (e) { /* private mode / quota — ignore */ }
})();
</script>
<section class="fsr-card memory-card {{ $variant === 'mobile' ? 'memory-card-mobile' : '' }}" data-fsr-section="memory" data-memory-id="{{ $rsMemory->id }}" data-memory-ends="{{ optional($rsMemory->ends_at)->toIso8601String() }}">
    <div class="memory-card-bg" aria-hidden="true"></div>
    <button type="button" class="pulse-close" data-memory-close aria-label="{{ __('messages.close') }}" title="{{ __('messages.close') }}">
        <i class="fas fa-times" aria-hidden="true"></i>
    </button>
    <header class="memory-card-head">
        <span class="memory-eyebrow">
            <i class="fas fa-book-open" aria-hidden="true"></i>
            {{ __('messages.memory_prompt_eyebrow') }}
        </span>
        <span class="memory-countdown" data-memory-countdown>—</span>
    </header>
    <h3 class="memory-question">{{ $rsMemory->question }}</h3>
    <footer class="memory-card-foot">
        <a href="{{ route('memories.index') }}" class="memory-cta">
            @if($rsMemoryAnswered)
                <i class="fas fa-check" aria-hidden="true"></i>
                <span>{{ __('messages.memory_already_answered') }}</span>
            @else
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                <span>{{ __('messages.memory_add_yours') }}</span>
            @endif
        </a>
        <span class="memory-count">
            <i class="fas fa-feather" aria-hidden="true"></i>
            {{ number_format($rsMemoryCount) }}
        </span>
    </footer>
</section>
<script>
// Memory countdown + dismissal — idempotent across multiple include sites (sidebar + mobile).
if (!window._nexusMemoryInit) {
    window._nexusMemoryInit = true;
    document.addEventListener('DOMContentLoaded', function () {
        const cards = Array.from(document.querySelectorAll('[data-fsr-section="memory"]'));
        if (!cards.length) return;
        const isAr = document.documentElement.lang === 'ar';

        // ----- Dismissal -----
        const STORE_KEY = 'nexus_memory_dismissed_ids';
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
            const mid = card.getAttribute('data-memory-id');
            if (isDismissed(mid)) {
                card.remove();
                return;
            }
            const closeBtn = card.querySelector('[data-memory-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    markDismissed(mid);
                    // Remove every instance of this memory on the page (sidebar + mobile).
                    document.querySelectorAll('[data-fsr-section="memory"][data-memory-id="' + mid + '"]')
                        .forEach(el => {
                            el.classList.add('is-dismissing');
                            setTimeout(() => el.remove(), 180);
                        });
                });
            }
        });

        // ----- Countdown -----
        function tickCard(card) {
            const endsRaw = card.getAttribute('data-memory-ends');
            const el = card.querySelector('[data-memory-countdown]');
            if (!endsRaw || !el) return;
            const endsAt = new Date(endsRaw).getTime();
            const diff = endsAt - Date.now();
            if (diff <= 0) { el.textContent = '—'; return; }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            if (d > 0) {
                el.textContent = isAr ? (d + 'ي ' + h + 'س') : (d + 'd ' + h + 'h');
            } else {
                const m = Math.floor((diff % 3600000) / 60000);
                el.textContent = isAr ? (h + 'س ' + m + 'د') : (h + 'h ' + m + 'm');
            }
        }
        function tickAll() {
            document.querySelectorAll('[data-fsr-section="memory"]').forEach(tickCard);
        }
        tickAll();
        setInterval(tickAll, 60000);
    });
}
</script>
@endif
