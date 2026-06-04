@extends('layouts.app')

@section('title', __('messages.pulse_today'))

@push('styles')
@vite(['resources/css/pulse.css'])
@endpush

@section('content')
<div class="pulse-page">
    @if(!$prompt)
        <div class="pulse-page-empty">
            <span class="pulse-page-empty-icon" aria-hidden="true"><i class="fas fa-wave-square"></i></span>
            <h1>{{ __('messages.pulse_no_active_prompt') }}</h1>
            <a href="{{ route('home') }}" class="pulse-page-back">{{ __('messages.back_to_feed') ?? 'Back to feed' }}</a>
        </div>
    @else
        <article class="pulse-hero" data-pulse-ends="{{ optional($prompt->ends_at)->toIso8601String() }}">
            <div class="pulse-hero-bg" aria-hidden="true"></div>
            <header class="pulse-hero-head">
                <span class="pulse-eyebrow">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    {{ __('messages.pulse_today') }}
                </span>
                <span class="pulse-countdown" data-pulse-page-countdown>—</span>
            </header>
            <h1 class="pulse-hero-question">{{ $prompt->question }}</h1>
            <p class="pulse-hero-sub">{{ __('messages.pulse_subtitle') }}</p>
            @auth
            @if($userAnswer)
            <div class="pulse-answered-badge">
                <i class="fas fa-circle-check"></i>
                {{ __('messages.pulse_already_answered') }}
            </div>
            @endif
            @endauth
        </article>

        @auth
        <section class="pulse-compose" id="pulse-compose">
            <div class="pulse-compose-row">
                <img src="{{ auth()->user()->avatar_url }}" alt="" class="pulse-compose-avatar" loading="lazy">
                <div class="pulse-compose-input">
                    <textarea id="pulse-answer-input" placeholder="{{ __('messages.pulse_placeholder') }}" maxlength="600" rows="{{ $userAnswer ? 4 : 2 }}" dir="auto" autocomplete="off">{{ $userAnswer?->content }}</textarea>
                </div>
            </div>
            <div class="pulse-compose-actions">
                <label class="pulse-anon">
                    <input type="checkbox" id="pulse-anon-toggle" {{ $userAnswer?->is_anonymous ? 'checked' : '' }}>
                    <span><i class="fas fa-user-secret" aria-hidden="true"></i> {{ __('messages.pulse_share_anonymously') }}</span>
                </label>
                <button type="button" class="pulse-share-btn" id="pulse-share-btn">
                    <span>{{ __('messages.pulse_share') }}</span>
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                </button>
            </div>
        </section>
        @endauth

        <section class="pulse-answers" id="pulse-answers">
            <header class="pulse-answers-head">
                <h2>{{ __('messages.pulse_answers_count', ['count' => number_format($answers->count())]) }}</h2>
            </header>
            @if($answers->isEmpty())
                <div class="pulse-empty">
                    <span class="pulse-empty-icon" aria-hidden="true"><i class="far fa-comment-dots"></i></span>
                    <p>{{ __('messages.pulse_empty') }}</p>
                </div>
            @else
                <ul class="pulse-answer-list">
                    @foreach($answers as $a)
                        <li class="pulse-answer" data-answer-id="{{ $a->id }}">
                            <div class="pulse-answer-avatar">
                                @if($a->is_anonymous)
                                    <span class="pulse-anon-avatar"><i class="fas fa-user-secret"></i></span>
                                @else
                                    <a href="{{ route('users.show', $a->user) }}" style="display:flex;flex-shrink:0;"><img src="{{ $a->user->avatar_url }}" alt="" loading="lazy" style="pointer-events:none;"></a>
                                @endif
                            </div>
                            <div class="pulse-answer-body">
                                <div class="pulse-answer-head">
                                    <span class="pulse-answer-name">
                                        @if($a->is_anonymous)
                                            {{ __('messages.anonymous_participant') }}
                                        @else
                                            <a href="{{ route('users.show', $a->user) }}" style="text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:.2em;"><span class="pulse-answer-fullname">{{ optional($a->user->profile)->full_name ?: $a->user->name }}</span><x-verified-badge :user="$a->user" size=".85em" /></a>
                                            <a href="{{ route('users.show', $a->user) }}" style="text-decoration:none;"><span class="pulse-answer-handle" dir="ltr">&#64;{{ $a->user->username }}</span></a>
                                        @endif
                                    </span>
                                    <div class="pulse-answer-head-right">
                                        <span class="pulse-answer-time" data-timestamp="{{ $a->created_at->toIso8601String() }}">{{ $a->created_at->diffForHumans() }}</span>
                                        @auth @if(auth()->id() === $a->user_id)
                                        <button class="pulse-delete-btn" title="{{ __('messages.delete') }}"><i class="fas fa-trash-can"></i></button>
                                        @endif @endauth
                                    </div>
                                </div>
                                <p class="pulse-answer-content">{{ $a->content }}</p>
                                @if($a->updated_at->gt($a->created_at))
                                    <span class="pulse-edited-badge"><i class="fas fa-pen"></i> {{ __('messages.edited') }}</span>
                                @endif
                                <div class="pulse-answer-footer">
                                    @auth
                                    <button class="pulse-like-btn {{ isset($userLikedIds[$a->id]) ? 'liked' : '' }}" data-answer-id="{{ $a->id }}">
                                        <i class="{{ isset($userLikedIds[$a->id]) ? 'fas' : 'far' }} fa-heart"></i>
                                        <span class="pulse-like-count">{{ $a->likes->count() }}</span>
                                    </button>
                                    @else
                                    <span class="pulse-like-btn pulse-like-btn--guest">
                                        <i class="far fa-heart"></i>
                                        <span class="pulse-like-count">{{ $a->likes->count() }}</span>
                                    </span>
                                    @endauth
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>

@push('scripts')
<script>
(function () {
    function escapeHtml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    // ── Prevent bfcache form restoration ───────────────────────────────────
    const taEl = document.getElementById('pulse-answer-input');
    let savedAnswer = @json($userAnswer?->content ?? '');
    let savedAnonState = @json($userAnswer?->is_anonymous ?? false);
    function resetTextarea() { if (taEl) taEl.value = savedAnswer; }
    resetTextarea();
    window.addEventListener('pageshow', resetTextarea);

    // ── Countdown ──────────────────────────────────────────────────────────
    const hero = document.querySelector('.pulse-hero');
    const countdownEl = hero?.querySelector('[data-pulse-page-countdown]');
    if (countdownEl && hero?.dataset.pulseEnds) {
        const endsAt = new Date(hero.dataset.pulseEnds).getTime();
        const isAr = document.documentElement.lang === 'ar';
        const tick = () => {
            const diff = endsAt - Date.now();
            if (diff <= 0) { countdownEl.textContent = '—'; return; }
            const h = Math.floor(diff / 3600000), m = Math.floor((diff % 3600000) / 60000);
            countdownEl.textContent = isAr ? `${h}س ${m}د` : `${h}h ${m}m`;
        };
        tick(); setInterval(tick, 60000);
    }

    // ── Shared ─────────────────────────────────────────────────────────────
    const isAr = document.documentElement.lang === 'ar';
    const anonLabel = @json(__('messages.anonymous_participant'));
    const currentUserId = @json(auth()->id());
    const answersHead = document.querySelector('.pulse-answers-head h2');
    const answersSection = document.getElementById('pulse-answers');
    const knownIds = new Set([...document.querySelectorAll('.pulse-answer[data-answer-id]')].map(el => el.dataset.answerId));

    function getOrCreateList() {
        let ul = document.querySelector('.pulse-answer-list');
        if (!ul) {
            answersSection?.querySelector('.pulse-empty')?.remove();
            ul = document.createElement('ul');
            ul.className = 'pulse-answer-list';
            answersSection?.appendChild(ul);
        }
        return ul;
    }

    function timeAgo(iso) {
        const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
        if (diff < 60) return isAr ? 'الآن' : 'just now';
        if (diff < 3600) return isAr ? `${Math.floor(diff/60)} د` : `${Math.floor(diff/60)}m`;
        return isAr ? `${Math.floor(diff/3600)} س` : `${Math.floor(diff/3600)}h`;
    }

    // Update all visible timestamps every 60s
    setInterval(() => {
        document.querySelectorAll('.pulse-answer-time[data-timestamp]').forEach(el => {
            el.textContent = timeAgo(el.dataset.timestamp);
        });
    }, 60000);

    function renderAnswer(a) {
        const isOwn = a.author_id && String(a.author_id) === String(currentUserId);
        const deleteBtn = isOwn ? `<button class="pulse-delete-btn" title="{{ __('messages.delete') }}"><i class="fas fa-trash-can"></i></button>` : '';
        const avatar = a.is_anonymous
            ? `<span class="pulse-anon-avatar"><i class="fas fa-user-secret"></i></span>`
            : `<a href="/users/${encodeURIComponent(a.author.username)}" style="display:flex;flex-shrink:0;"><img src="${a.author.avatar_url}" alt="" loading="lazy" style="pointer-events:none;"></a>`;
        const verifiedSvg = a.author && a.author.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".85em" height=".85em" style="display:inline-block;vertical-align:middle;margin-left:.2em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
        const nameHtml = a.is_anonymous ? anonLabel
            : `<a href="/users/${encodeURIComponent(a.author.username)}" style="text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:.2em;"><span class="pulse-answer-fullname">${escapeHtml(a.author.name)}</span>${verifiedSvg}</a><a href="/users/${encodeURIComponent(a.author.username)}" style="text-decoration:none;"><span class="pulse-answer-handle" dir="ltr">@${escapeHtml(a.author.username)}</span></a>`;
        return `<li class="pulse-answer pulse-answer--new" data-answer-id="${a.id}">
            <div class="pulse-answer-avatar">${avatar}</div>
            <div class="pulse-answer-body">
                <div class="pulse-answer-head">
                    <span class="pulse-answer-name">${nameHtml}</span>
                    <div class="pulse-answer-head-right">
                        <span class="pulse-answer-time" data-timestamp="${a.created_at}">${timeAgo(a.created_at)}</span>
                        ${deleteBtn}
                    </div>
                </div>
                <p class="pulse-answer-content">${a.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>
                <div class="pulse-answer-footer">
                    <button class="pulse-like-btn" data-answer-id="${a.id}">
                        <i class="far fa-heart"></i>
                        <span class="pulse-like-count">${a.likes_count ?? 0}</span>
                    </button>
                </div>
            </div>
        </li>`;
    }

    function updateCounter(count) {
        if (!answersHead || count === undefined) return;
        answersHead.textContent = answersHead.textContent.replace(/[\d,]+/, Number(count).toLocaleString());
    }

    // ── Socket.io real-time ────────────────────────────────────────────────
    let socketReady = false;
    const waitForSocket = setInterval(() => {
        if (!window.NexusSocket?.socket?.connected) return;
        clearInterval(waitForSocket);
        socketReady = true;
        window.NexusSocket.socket.emit('pulse:join');
        window.NexusSocket.socket.on('pulse:answer', (a) => {
            if (a.is_update && knownIds.has(String(a.id))) {
                const existing = document.querySelector(`.pulse-answer[data-answer-id="${a.id}"]`);
                if (existing) {
                    // Preserve current likes count from DOM
                    const existingLikes = existing.querySelector('.pulse-like-count')?.textContent ?? '0';
                    a.likes_count = parseInt(existingLikes) || (a.likes_count ?? 0);
                    const newHtml = renderAnswer(a);
                    const tmp = document.createElement('ul');
                    tmp.innerHTML = newHtml;
                    const newCard = tmp.firstElementChild;
                    newCard.classList.remove('pulse-answer--new');
                    newCard.querySelector('.pulse-answer-content')?.insertAdjacentHTML('afterend',
                        `<span class="pulse-edited-badge"><i class="fas fa-pen" style="font-size:9px"></i> {{ __('messages.edited') }}</span>`);
                    existing.replaceWith(newCard);
                }
                return;
            }
            if (knownIds.has(String(a.id))) return;
            knownIds.add(String(a.id));
            getOrCreateList().insertAdjacentHTML('afterbegin', renderAnswer(a));
            updateCounter(a.answers_count);
        });
        window.NexusSocket.socket.on('pulse:answer:deleted', (a) => {
            knownIds.delete(String(a.id));
            document.querySelector(`.pulse-answer[data-answer-id="${a.id}"]`)?.remove();
            updateCounter(a.answers_count);
        });
        window.NexusSocket.socket.on('pulse:answer:liked', (data) => {
            const card = document.querySelector(`.pulse-answer[data-answer-id="${data.answer_id}"]`);
            if (!card) return;
            const btn = card.querySelector('.pulse-like-btn');
            if (!btn) return;
            btn.querySelector('.pulse-like-count').textContent = data.likes_count;
            // Only update liked state for the user who triggered it
            if (String(data.liked_by) === String(currentUserId)) {
                btn.classList.toggle('liked', data.liked);
                btn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
            }
        });
    }, 300);
    setTimeout(() => clearInterval(waitForSocket), 10000);

    // ── Like ──────────────────────────────────────────────────────────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.pulse-like-btn[data-answer-id]');
        if (!btn || btn.classList.contains('pulse-like-btn--guest')) return;
        const answerId = btn.dataset.answerId;
        btn.disabled = true;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch('{{ route('pulse.answer.like') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ answer_id: answerId }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                btn.classList.toggle('liked', data.liked);
                btn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                btn.querySelector('.pulse-like-count').textContent = data.likes_count;
                data.liked ? window.NexusSoul?.feedback.like() : window.NexusSoul?.feedback.unlike();
            }
        } finally { btn.disabled = false; }
    });

    // ── Delete ─────────────────────────────────────────────────────────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.pulse-delete-btn');
        if (!btn) return;
        btn.disabled = true;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch('{{ route('pulse.answer.delete') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                btn.closest('.pulse-answer')?.remove();
                document.querySelector('.pulse-answered-badge')?.remove();
                window.NexusSoul?.feedback.delete();
                // Reset compose textarea
                if (taEl) taEl.value = '';
                savedAnswer = '';
                savedAnonState = false;
                // Reset share button label
                const shareBtn = document.getElementById('pulse-share-btn');
                if (shareBtn) {
                    shareBtn.disabled = false;
                    shareBtn.innerHTML = '<span>{{ __('messages.pulse_share') }}</span><i class="fas fa-paper-plane" aria-hidden="true"></i>';
                }
                // Reset anon toggle
                const anonToggle = document.getElementById('pulse-anon-toggle');
                if (anonToggle) anonToggle.checked = false;
                if (window.showToast) window.showToast('{{ __('messages.deleted') }}', 'success');
            } else { btn.disabled = false; }
        } catch { btn.disabled = false; }
    });

    // ── Submit ─────────────────────────────────────────────────────────────
    const shareBtn = document.getElementById('pulse-share-btn');
    if (!shareBtn) return;
    const ta = document.getElementById('pulse-answer-input');
    const anon = document.getElementById('pulse-anon-toggle');
    shareBtn.addEventListener('click', async () => {
        const content = (ta?.value || '').trim();
        if (!content) { window.NexusSoul?.feedback.error(); ta?.focus(); return; }
        const isAnon = !!anon?.checked;
        // If nothing changed, show message
        if (savedAnswer && content === savedAnswer.trim() && isAnon === savedAnonState) {
            window.showToast?.('{{ __('messages.pulse_no_changes') ?? 'No changes to save.' }}', 'info');
            return;
        }
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        shareBtn.disabled = true;
        const origLabel = shareBtn.innerHTML;
        shareBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const res = await fetch('{{ route('pulse.answer') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ content, is_anonymous: isAnon }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                window.NexusSoul?.feedback.post();
                window.showToast?.('{{ __('messages.pulse_share') }} ✓', 'success');
                const wasUpdate = !!savedAnswer;
                savedAnswer = content;
                savedAnonState = isAnon;
                // Update own card in place only if this was a true edit (not a fresh post after delete)
                if (wasUpdate && data.answer) {
                    const ownCard = document.querySelector(`.pulse-answer[data-answer-id="${data.answer.id}"]`);
                    if (ownCard) {
                        const existingLikes = ownCard.querySelector('.pulse-like-count')?.textContent ?? '0';
                        const a = { ...data.answer, author_id: currentUserId, is_anonymous: isAnon, likes_count: parseInt(existingLikes) || (data.answer.likes_count ?? 0) };
                        const newHtml = renderAnswer(a);
                        const tmp = document.createElement('ul');
                        tmp.innerHTML = newHtml;
                        const newCard = tmp.firstElementChild;
                        newCard.classList.remove('pulse-answer--new');
                        newCard.querySelector('.pulse-answer-content')?.insertAdjacentHTML('afterend',
                            `<span class="pulse-edited-badge"><i class="fas fa-pen" style="font-size:9px"></i> {{ __('messages.edited') }}</span>`);
                        ownCard.replaceWith(newCard);
                    }
                }
                shareBtn.disabled = false;
                shareBtn.innerHTML = '<i class="fas fa-check"></i> {{ __('messages.pulse_share') }}';
                if (!document.querySelector('.pulse-answered-badge')) {
                    document.querySelector('.pulse-hero-sub')?.insertAdjacentHTML('afterend',
                        `<div class="pulse-answered-badge"><i class="fas fa-circle-check"></i> {{ __('messages.pulse_already_answered') }}</div>`
                    );
                }
                // Fallback: add to list manually if socket not connected
                if (!socketReady && data.answer) {
                    const a = { ...data.answer, author_id: currentUserId };
                    if (!knownIds.has(String(a.id))) {
                        knownIds.add(String(a.id));
                        getOrCreateList().insertAdjacentHTML('afterbegin', renderAnswer(a));
                        updateCounter(data.answers_count);
                    }
                }
            } else {
                window.NexusSoul?.feedback.error();
                window.showToast?.(data.message || 'Something went wrong', 'error');
                shareBtn.disabled = false;
                shareBtn.innerHTML = origLabel;
            }
        } catch {
            window.NexusSoul?.feedback.error();
            shareBtn.disabled = false;
            shareBtn.innerHTML = origLabel;
        }
    });
})();
</script>
@endpush
@endsection
