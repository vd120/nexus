{{-- Life Chapters carousel section. Included in users.show. --}}
@php
    $chaptersUser = $chaptersUser ?? $user;
    $chaptersIsOwner = $chaptersIsOwner ?? (auth()->check() && auth()->id() === $chaptersUser->id);
    $chapters = \App\Models\LifeChapter::where('user_id', $chaptersUser->id)
        ->orderByDesc('sort_order')
        ->orderByDesc('starts_on')
        ->get();
    $hasChapters = $chapters->count() > 0;
@endphp

<section class="lc-section" data-username="{{ $chaptersUser->username }}" data-owner="{{ $chaptersIsOwner ? '1' : '0' }}">
    <header class="lc-section-header">
        <h3 class="lc-section-title">
            <span class="lc-section-emoji" aria-hidden="true">📖</span>
            <span>{{ __('messages.my_chapters') }}</span>
        </h3>
        @if($chaptersIsOwner && $hasChapters)
            <button type="button" class="lc-add-btn-inline" data-lc-open-create>
                <i class="fas fa-plus"></i>
                <span>{{ __('messages.add_chapter') }}</span>
            </button>
        @endif
    </header>

    @if(!$hasChapters && $chaptersIsOwner)
        <div class="lc-empty">
            <div class="lc-empty-emoji" aria-hidden="true">📖</div>
            <h4 class="lc-empty-title">{{ __('messages.chapters_empty_title') }}</h4>
            <p class="lc-empty-desc">{{ __('messages.chapters_empty_desc') }}</p>
            <button type="button" class="lc-empty-cta" data-lc-open-create>
                <i class="fas fa-plus"></i>
                <span>{{ __('messages.create_first_chapter') }}</span>
            </button>
        </div>
    @elseif($hasChapters)
        <div class="lc-shelf" id="lcShelf">
            @if($chaptersIsOwner)
                <button type="button" class="lc-card lc-card-add" data-lc-open-create>
                    <div class="lc-card-add-icon"><i class="fas fa-plus"></i></div>
                    <div class="lc-card-add-label">{{ __('messages.add_chapter') }}</div>
                </button>
            @endif

            @foreach($chapters as $chapter)
                @php
                    $startYear = $chapter->starts_on?->format('Y');
                    $endYear = $chapter->ends_on?->format('Y');
                    $rangeText = $startYear
                        ? ($endYear ? $startYear.' – '.$endYear : $startYear.' – '.__('messages.ongoing'))
                        : ($endYear ? '… – '.$endYear : __('messages.ongoing'));
                    $excerpt = $chapter->description
                        ? \Illuminate\Support\Str::limit(strip_tags($chapter->description), 90)
                        : null;
                @endphp
                <article class="lc-card" id="lcCard-{{ $chapter->id }}" data-lc-id="{{ $chapter->id }}"
                    data-lc-title="{{ $chapter->title }}"
                    data-lc-emoji="{{ $chapter->emoji }}"
                    data-lc-starts="{{ $chapter->starts_on?->toDateString() }}"
                    data-lc-ends="{{ $chapter->ends_on?->toDateString() }}"
                    data-lc-desc="{{ $chapter->description }}">
                    <a href="{{ url('/users/'.$chaptersUser->username.'/chapters/'.$chapter->id) }}" class="lc-card-link" aria-label="{{ $chapter->title }}">
                        <div class="lc-card-emoji">{{ $chapter->emoji ?: '📖' }}</div>
                        <div class="lc-card-body">
                            <h4 class="lc-card-title">{{ $chapter->title }}</h4>
                            <div class="lc-card-range">{{ $rangeText }}</div>
                            @if($excerpt)
                                <p class="lc-card-excerpt">{{ $excerpt }}</p>
                            @endif
                        </div>
                    </a>
                    @if($chaptersIsOwner)
                        <div class="lc-card-actions">
                            <button type="button" class="lc-card-action" data-lc-open-edit="{{ $chapter->id }}" aria-label="{{ __('messages.edit_chapter') }}">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <button type="button" class="lc-card-action lc-card-action-danger"
                                data-admin-delete="{{ url('/api/chapters/'.$chapter->id) }}"
                                data-target="#lcCard-{{ $chapter->id }}"
                                data-confirm="{{ __('messages.delete_chapter_confirm') }}"
                                aria-label="{{ __('messages.delete_chapter') ?? 'Delete' }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>

@if($chaptersIsOwner)
    <div id="lcModal" class="lc-modal" hidden>
        <div class="lc-modal-backdrop" data-lc-close></div>
        <div class="lc-modal-panel" role="dialog" aria-modal="true" aria-labelledby="lcModalTitle">
            <header class="lc-modal-header">
                <h3 id="lcModalTitle" class="lc-modal-title"
                    data-add-label="{{ __('messages.add_chapter') }}"
                    data-edit-label="{{ __('messages.edit_chapter') }}">{{ __('messages.add_chapter') }}</h3>
                <button type="button" class="lc-modal-close" data-lc-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <form id="lcForm" class="lc-modal-form" novalidate>
                <input type="hidden" name="id" id="lcFieldId" value="">
                <div class="lc-field-row">
                    <div class="lc-field lc-field-emoji">
                        <label for="lcFieldEmoji">{{ __('messages.chapter_emoji') }}</label>
                        <input type="text" id="lcFieldEmoji" name="emoji" maxlength="8" placeholder="📖" autocomplete="off">
                    </div>
                    <div class="lc-field lc-field-grow">
                        <label for="lcFieldTitle">{{ __('messages.chapter_title') }} <span class="lc-required">*</span></label>
                        <input type="text" id="lcFieldTitle" name="title" maxlength="120" required>
                    </div>
                </div>
                <div class="lc-field-row">
                    <div class="lc-field">
                        <label for="lcFieldStarts">{{ __('messages.chapter_starts_on') }}</label>
                        <input type="date" id="lcFieldStarts" name="starts_on">
                    </div>
                    <div class="lc-field">
                        <label for="lcFieldEnds">{{ __('messages.chapter_ends_on') }}</label>
                        <input type="date" id="lcFieldEnds" name="ends_on">
                    </div>
                </div>
                <div class="lc-field">
                    <label for="lcFieldDesc">{{ __('messages.chapter_description') }}</label>
                    <textarea id="lcFieldDesc" name="description" rows="3" maxlength="2000"></textarea>
                </div>
                <div class="lc-modal-error" id="lcFormError" hidden></div>
                <footer class="lc-modal-footer">
                    <button type="button" class="lc-btn lc-btn-ghost" data-lc-close>{{ __('messages.cancel') ?? 'Cancel' }}</button>
                    <button type="button" class="lc-btn lc-btn-primary" id="lcSaveBtn" onclick="lcSaveChapter()">
                        <i class="fas fa-bookmark"></i>
                        <span>{{ __('messages.chapter_save') }}</span>
                    </button>
                </footer>
            </form>
        </div>
    </div>
@endif
