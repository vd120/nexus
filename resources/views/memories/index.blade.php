@extends('layouts.app')

@section('title', __('messages.community_memories'))

@push('styles')
@vite(['resources/css/pulse.css'])
@endpush

@section('content')
<div class="pulse-page memory-page">
    @if(!$prompt)
        <div class="pulse-page-empty">
            <span class="pulse-page-empty-icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
            <h1>{{ __('messages.no_active_memory_prompt') }}</h1>
            <p style="color:var(--text-muted);font-size:14px;margin-top:8px;">{{ __('messages.no_active_memory_prompt_desc') }}</p>
            <a href="{{ route('home') }}" class="pulse-page-back">{{ __('messages.back_to_feed') }}</a>
        </div>
    @else
        <article class="pulse-hero memory-hero" data-memory-ends="{{ optional($prompt->ends_at)->toIso8601String() }}">
            <div class="pulse-hero-bg memory-hero-bg" aria-hidden="true"></div>
            <header class="pulse-hero-head">
                <span class="pulse-eyebrow memory-eyebrow">
                    <i class="fas fa-book-open" aria-hidden="true"></i>
                    {{ __('messages.memory_prompt_eyebrow') }}
                </span>
                <span class="pulse-countdown memory-countdown" data-memory-page-countdown>—</span>
            </header>
            <h1 class="pulse-hero-question">{{ $prompt->question }}</h1>
            <p class="pulse-hero-sub">{{ __('messages.memory_subtitle') }}</p>
            @auth
            @if($userAnswer)
            <div class="pulse-answered-badge memory-answered-badge">
                <i class="fas fa-circle-check"></i>
                {{ __('messages.memory_already_answered') }}
            </div>
            @endif
            @endauth
        </article>

        @auth
        <section class="pulse-compose memory-compose" id="memory-compose">
            <div class="pulse-compose-row">
                <img src="{{ auth()->user()->avatar_url }}" alt="" class="pulse-compose-avatar" loading="lazy">
                <div class="pulse-compose-input">
                    <textarea id="memory-answer-input"
                              placeholder="{{ __('messages.memory_placeholder') }}"
                              maxlength="5000"
                              rows="{{ $userAnswer ? 5 : 3 }}"
                              dir="auto"
                              autocomplete="off">{{ $userAnswer?->content }}</textarea>
                    <div class="memory-char-row">
                        <span class="memory-char-count" id="memory-char-count">0 / 5000</span>
                    </div>
                </div>
            </div>
            <div class="memory-visibility-pills" id="memory-visibility-group" data-memory-visibility="{{ optional($userAnswer)->visibility ?? 'public' }}">
                <button type="button" class="memory-pill {{ (optional($userAnswer)->visibility ?? 'public') === 'self' ? 'active' : '' }}" data-vis="self">
                    <i class="fas fa-lock"></i> {{ __('messages.memory_prompt_visibility_self') }}
                </button>
                <button type="button" class="memory-pill {{ (optional($userAnswer)->visibility ?? 'public') === 'followers' ? 'active' : '' }}" data-vis="followers">
                    <i class="fas fa-users"></i> {{ __('messages.memory_prompt_visibility_followers') }}
                </button>
                <button type="button" class="memory-pill {{ (optional($userAnswer)->visibility ?? 'public') === 'public' ? 'active' : '' }}" data-vis="public">
                    <i class="fas fa-globe"></i> {{ __('messages.memory_prompt_visibility_public') }}
                </button>
            </div>
            <div class="pulse-compose-actions">
                <label class="pulse-anon">
                    <input type="checkbox" id="memory-anon-toggle" {{ optional($userAnswer)->is_anonymous ? 'checked' : '' }}>
                    <span><i class="fas fa-user-secret" aria-hidden="true"></i> {{ __('messages.memory_prompt_anonymous') }}</span>
                </label>
                <button type="button" class="pulse-share-btn memory-share-btn" id="memory-share-btn">
                    <span>{{ __('messages.memory_share') }}</span>
                    <i class="fas fa-bookmark" aria-hidden="true"></i>
                </button>
            </div>
        </section>
        @else
        <div class="pulse-compose memory-compose memory-login-prompt">
            <a href="{{ route('login.view') }}" class="memory-login-cta">
                <i class="fas fa-book-open"></i>
                {{ __('messages.login_to_share_memory') }}
            </a>
        </div>
        @endauth

        <section class="pulse-answers memory-answers" id="memory-answers">
            <header class="pulse-answers-head">
                <h2>{{ __('messages.memory_answers_count', ['count' => number_format($answers->total())]) }}</h2>
            </header>
            @if($answers->isEmpty())
                <div class="pulse-empty">
                    <span class="pulse-empty-icon" aria-hidden="true"><i class="fas fa-feather"></i></span>
                    <p>{{ __('messages.memory_empty') }}</p>
                </div>
            @else
                <ul class="pulse-answer-list memory-answer-list">
                    @foreach($answers as $a)
                        <li class="pulse-answer memory-answer" data-answer-id="{{ $a->id }}">
                            <div class="pulse-answer-avatar">
                                @if($a->is_anonymous)
                                    <span class="pulse-anon-avatar"><i class="fas fa-user-secret"></i></span>
                                @else
                                    <img src="{{ $a->user->avatar_url }}" alt="" loading="lazy">
                                @endif
                            </div>
                            <div class="pulse-answer-body">
                                <div class="pulse-answer-head">
                                    <span class="pulse-answer-name">
                                        @if($a->is_anonymous)
                                            {{ __('messages.anonymous_participant') }}
                                        @else
                                            <span class="pulse-answer-fullname">{{ optional($a->user->profile)->full_name ?: $a->user->name }}</span>
                                            <span class="pulse-answer-handle" dir="ltr">&#64;{{ $a->user->username }}</span>
                                        @endif
                                    </span>
                                    <div class="pulse-answer-head-right">
                                        <span class="pulse-answer-time memory-answer-time" data-timestamp="{{ $a->created_at->toIso8601String() }}">{{ $a->created_at->diffForHumans() }}</span>
                                        @auth @if(auth()->id() === $a->user_id)
                                        <button class="pulse-delete-btn memory-delete-btn" title="{{ __('messages.delete') }}"><i class="fas fa-trash-can"></i></button>
                                        @endif @endauth
                                    </div>
                                </div>
                                <p class="pulse-answer-content memory-answer-content" dir="auto">{{ $a->content }}</p>
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
                                @if($a->visibility === 'followers')
                                    <span class="memory-vis-badge memory-vis-followers"><i class="fas fa-users"></i> {{ __('messages.memory_prompt_visibility_followers') }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if($answers->hasPages())
                    <div class="memory-pagination">
                        {{ $answers->links() }}
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const isAr = document.documentElement.lang === 'ar';
    const currentUserId = @json(auth()->id());
    const anonLabel = @json(__('messages.anonymous_participant'));
    let savedAnswer = @json($userAnswer?->content ?? '');

    // ── Prevent bfcache form restoration ──────────────────────────────────
    const taEl = document.getElementById('memory-answer-input');
    function resetTextarea() { if (taEl) taEl.value = savedAnswer; }
    resetTextarea();
    window.addEventListener('pageshow', resetTextarea);

    // ── Countdown ─────────────────────────────────────────────────────────
    const hero = document.querySelector('.memory-hero');
    const countdownEl = hero?.querySelector('[data-memory-page-countdown]');
    if (countdownEl && hero?.dataset.memoryEnds) {
        const endsAt = new Date(hero.dataset.memoryEnds).getTime();
        const tick = () => {
            const diff = endsAt - Date.now();
            if (diff <= 0) { countdownEl.textContent = '—'; return; }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            if (d > 0) {
                countdownEl.textContent = isAr ? `${d}ي ${h}س` : `${d}d ${h}h`;
            } else {
                countdownEl.textContent = isAr ? `${h}س ${m}د` : `${h}h ${m}m`;
            }
        };
        tick(); setInterval(tick, 60000);
    }

    // ── Char counter ──────────────────────────────────────────────────────
    const charCount = document.getElementById('memory-char-count');
    if (taEl && charCount) {
        const update = () => { charCount.textContent = (taEl.value.length || 0) + ' / 5000'; };
        taEl.addEventListener('input', update);
        update();
    }

    // ── Visibility pills ──────────────────────────────────────────────────
    const visGroup = document.getElementById('memory-visibility-group');
    if (visGroup) {
        visGroup.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-vis]');
            if (!btn) return;
            visGroup.querySelectorAll('.memory-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            visGroup.setAttribute('data-memory-visibility', btn.getAttribute('data-vis'));
        });
    }

    // ── Shared helpers ────────────────────────────────────────────────────
    const answersHead = document.querySelector('.pulse-answers-head h2');
    const answersSection = document.getElementById('memory-answers');
    const knownIds = new Set([...document.querySelectorAll('.memory-answer[data-answer-id]')].map(el => el.dataset.answerId));

    function getOrCreateList() {
        let ul = document.querySelector('.memory-answer-list');
        if (!ul) {
            answersSection?.querySelector('.pulse-empty')?.remove();
            ul = document.createElement('ul');
            ul.className = 'pulse-answer-list memory-answer-list';
            answersSection?.appendChild(ul);
        }
        return ul;
    }

    function timeAgo(iso) {
        const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
        if (diff < 60) return isAr ? 'الآن' : 'just now';
        if (diff < 3600) return isAr ? `${Math.floor(diff/60)} د` : `${Math.floor(diff/60)}m`;
        if (diff < 86400) return isAr ? `${Math.floor(diff/3600)} س` : `${Math.floor(diff/3600)}h`;
        return isAr ? `${Math.floor(diff/86400)} ي` : `${Math.floor(diff/86400)}d`;
    }

    // Update all visible timestamps every 60s
    setInterval(() => {
        document.querySelectorAll('.memory-answer-time[data-timestamp]').forEach(el => {
            el.textContent = timeAgo(el.dataset.timestamp);
        });
    }, 60000);

    function updateCounter(count) {
        if (!answersHead || count === undefined) return;
        answersHead.textContent = answersHead.textContent.replace(/[\d,]+/, Number(count).toLocaleString());
    }

    function renderAnswer(a) {
        const avatar = a.is_anonymous
            ? `<span class="pulse-anon-avatar"><i class="fas fa-user-secret"></i></span>`
            : `<img src="${a.author.avatar_url}" alt="" loading="lazy">`;
        const nameHtml = a.is_anonymous
            ? anonLabel
            : `<span class="pulse-answer-fullname">${a.author.name}</span><span class="pulse-answer-handle" dir="ltr">@${a.author.username}</span>`;
        const content = String(a.content ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const isOwn = a.author_id && String(a.author_id) === String(currentUserId);
        const deleteBtn = isOwn ? `<button class="pulse-delete-btn memory-delete-btn" title="{{ __('messages.delete') }}"><i class="fas fa-trash-can"></i></button>` : '';
        return `<li class="pulse-answer memory-answer pulse-answer--new" data-answer-id="${a.id}">
            <div class="pulse-answer-avatar">${avatar}</div>
            <div class="pulse-answer-body">
                <div class="pulse-answer-head">
                    <span class="pulse-answer-name">${nameHtml}</span>
                    <div class="pulse-answer-head-right">
                        <span class="pulse-answer-time memory-answer-time" data-timestamp="${a.created_at}">${timeAgo(a.created_at)}</span>
                        ${deleteBtn}
                    </div>
                </div>
                <p class="pulse-answer-content memory-answer-content" dir="auto">${content}</p>
                <div class="pulse-answer-footer">
                    <button class="pulse-like-btn" data-answer-id="${a.id}">
                        <i class="far fa-heart"></i>
                        <span class="pulse-like-count">${a.likes_count ?? 0}</span>
                    </button>
                </div>
            </div>
        </li>`;
    }

    // ── Socket.io real-time ───────────────────────────────────────────────
    let socketReady = false;
    const waitForSocket = setInterval(() => {
        if (!window.NexusSocket?.socket?.connected) return;
        clearInterval(waitForSocket);
        socketReady = true;
        window.NexusSocket.socket.emit('memory:join');
        window.NexusSocket.socket.on('memory:answer', (a) => {
            if (a.is_update && knownIds.has(String(a.id))) {
                const existing = document.querySelector(`.memory-answer[data-answer-id="${a.id}"]`);
                if (existing) {
                    const existingLikes = existing.querySelector('.pulse-like-count')?.textContent ?? '0';
                    a.likes_count = parseInt(existingLikes) || (a.likes_count ?? 0);
                    const newHtml = renderAnswer(a);
                    const tmp = document.createElement('ul');
                    tmp.innerHTML = newHtml;
                    const newCard = tmp.firstElementChild;
                    newCard.classList.remove('pulse-answer--new');
                    newCard.querySelector('.memory-answer-content')?.insertAdjacentHTML('afterend',
                        `<span class="pulse-edited-badge"><i class="fas fa-pen" style="font-size:9px"></i> {{ __('messages.edited') }}</span>`);
                    existing.replaceWith(newCard);
                }
                return;
            }
            if (knownIds.has(String(a.id))) return;
            if (a.visibility && a.visibility !== 'public' && String(a.author_id) !== String(currentUserId)) return;
            knownIds.add(String(a.id));
            getOrCreateList().insertAdjacentHTML('afterbegin', renderAnswer(a));
            updateCounter(a.answers_count);
        });
        window.NexusSocket.socket.on('memory:answer:deleted', (a) => {
            knownIds.delete(String(a.id));
            document.querySelector(`.memory-answer[data-answer-id="${a.id}"]`)?.remove();
            updateCounter(a.answers_count);
        });
        window.NexusSocket.socket.on('memory:answer:liked', (data) => {
            const card = document.querySelector(`.memory-answer[data-answer-id="${data.answer_id}"]`);
            if (!card) return;
            const btn = card.querySelector('.pulse-like-btn');
            if (!btn) return;
            btn.querySelector('.pulse-like-count').textContent = data.likes_count;
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

    // ── Delete ────────────────────────────────────────────────────────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.memory-delete-btn');
        if (!btn) return;
        btn.disabled = true;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const res = await fetch('{{ route('pulse.memory.answer.delete') }}', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                btn.closest('.memory-answer')?.remove();
                document.querySelector('.memory-answered-badge')?.remove();
                window.NexusSoul?.feedback.delete();
                updateCounter(data.answers_count);
                // Reset compose section so user can write a new answer
                if (taEl) {
                    taEl.value = '';
                    savedAnswer = '';
                    savedAnonState = false;
                    savedVisibility = 'public';
                    if (charCount) charCount.textContent = '0 / 5000';
                }
                // Reset visibility pills to public
                if (visGroup) {
                    visGroup.querySelectorAll('.memory-pill').forEach(p => p.classList.remove('active'));
                    visGroup.querySelector('[data-vis="public"]')?.classList.add('active');
                    visGroup.setAttribute('data-memory-visibility', 'public');
                }
                // Reset anon toggle
                const anonToggle = document.getElementById('memory-anon-toggle');
                if (anonToggle) anonToggle.checked = false;
                // Reset share button label
                const shareBtn = document.getElementById('memory-share-btn');
                if (shareBtn) {
                    shareBtn.disabled = false;
                    shareBtn.innerHTML = '<span>{{ __('messages.memory_share') }}</span><i class="fas fa-bookmark" aria-hidden="true"></i>';
                }
                if (window.showToast) window.showToast('{{ __('messages.deleted') }}', 'success');
            } else { btn.disabled = false; }
        } catch { btn.disabled = false; }
    });

    // ── Submit ────────────────────────────────────────────────────────────
    const shareBtn = document.getElementById('memory-share-btn');
    if (!shareBtn) return;
    const anon = document.getElementById('memory-anon-toggle');
    let savedAnonState = @json(optional($userAnswer)->is_anonymous ?? false);
    let savedVisibility = @json(optional($userAnswer)->visibility ?? 'public');

    shareBtn.addEventListener('click', async () => {
        const content = (taEl?.value || '').trim();
        if (!content) { window.NexusSoul?.feedback.error(); taEl?.focus(); return; }
        const visibility = visGroup ? visGroup.getAttribute('data-memory-visibility') : 'public';
        const isAnon = !!anon?.checked;

        // If user already answered and nothing changed, show a message
        if (savedAnswer && content === savedAnswer.trim() && isAnon === savedAnonState && visibility === savedVisibility) {
            window.showToast?.('{{ __('messages.memory_no_changes') ?? 'No changes to save.' }}', 'info');
            return;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        shareBtn.disabled = true;
        const origLabel = shareBtn.innerHTML;
        shareBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const res = await fetch('{{ route('pulse.memory.answer') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ content, is_anonymous: isAnon, visibility }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.success) {
                window.NexusSoul?.feedback.post();
                window.showToast?.('{{ __('messages.memory_prompt_saved_toast') }}', 'success');
                const wasUpdate = !!savedAnswer;
                savedAnswer = content;
                savedAnonState = isAnon;
                savedVisibility = visibility;
                // Update own card in place only if this was an edit (not a fresh post after delete)
                if (wasUpdate && data.answer) {
                    const ownCard = document.querySelector(`.memory-answer[data-answer-id="${data.answer.id}"]`);
                    if (ownCard) {
                        const existingLikes = ownCard.querySelector('.pulse-like-count')?.textContent ?? '0';
                        const a = { ...data.answer, author_id: currentUserId, is_anonymous: isAnon, likes_count: parseInt(existingLikes) || (data.answer.likes_count ?? 0) };
                        const newHtml = renderAnswer(a);
                        const tmp = document.createElement('ul');
                        tmp.innerHTML = newHtml;
                        const newCard = tmp.firstElementChild;
                        newCard.classList.remove('pulse-answer--new');
                        newCard.querySelector('.memory-answer-content')?.insertAdjacentHTML('afterend',
                            `<span class="pulse-edited-badge"><i class="fas fa-pen" style="font-size:9px"></i> {{ __('messages.edited') }}</span>`);
                        ownCard.replaceWith(newCard);
                    }
                }
                shareBtn.disabled = false;
                shareBtn.innerHTML = '<i class="fas fa-check"></i> {{ __('messages.memory_share') }}';
                if (!document.querySelector('.memory-answered-badge')) {
                    document.querySelector('.pulse-hero-sub')?.insertAdjacentHTML('afterend',
                        `<div class="pulse-answered-badge memory-answered-badge"><i class="fas fa-circle-check"></i> {{ __('messages.memory_already_answered') }}</div>`
                    );
                }
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
