@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="admin-page">
    <div class="admin-page-header admin-flex-header">
        <div class="header-text">
            <h1 class="admin-page-title">{{ __('community_admin.community_badges') }}</h1>
            <p class="admin-page-subtitle">{{ __('community_admin.community_badges_subtitle') }}</p>
        </div>
        <button class="mod-btn approve-btn" onclick="toggleAddBadge()">
            <i class="fas fa-plus"></i> <span>{{ __('community_admin.create_new_badge') }}</span>
        </button>
    </div>

    {{-- Add Form --}}
    <div id="add-badge-form" class="panel add-form" style="display: none; margin-bottom: 40px;">
        <div class="panel-body" style="padding: 32px;">
            <div class="form-section-header">
                <i class="fas fa-magic"></i>
                <div>
                    <h3>{{ __('community_admin.design_new_badge') }}</h3>
                    <p>{{ __('community_admin.design_new_badge_desc') }}</p>
                </div>
            </div>

            <form action="{{ route('communities.admin.badges.add', $group->slug) }}" method="POST">
                @csrf
                <div class="grid-2">
                    <div class="form-group">
                        <label>{{ __('community_admin.badge_name') }}</label>
                        <input type="text" name="name" required class="input" placeholder="{{ __('community_admin.badge_name_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('community_admin.badge_color') }}</label>
                        <div class="color-picker-wrapper">
                            <input type="color" name="color" id="badge-color-picker" value="#6366f1" class="color-input" oninput="updateColorText(this.value)">
                            <span class="color-value-text" id="color-preview-text">#6366F1</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('community_admin.select_icon') }}</label>
                    <input type="hidden" name="icon" id="selected-icon-input" value="fas fa-award">
                    <div class="icon-selector-grid">
                        <div class="icon-opt active" onclick="selectBadgeIcon('fas fa-award', this)"><i class="fas fa-award"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-star', this)"><i class="fas fa-star"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-crown', this)"><i class="fas fa-crown"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-shield-alt', this)"><i class="fas fa-shield-alt"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-medal', this)"><i class="fas fa-medal"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-gem', this)"><i class="fas fa-gem"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-fire', this)"><i class="fas fa-fire"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-heart', this)"><i class="fas fa-heart"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-bolt', this)"><i class="fas fa-bolt"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-trophy', this)"><i class="fas fa-trophy"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-rocket', this)"><i class="fas fa-rocket"></i></div>
                        <div class="icon-opt" onclick="selectBadgeIcon('fas fa-ghost', this)"><i class="fas fa-ghost"></i></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="mod-btn approve-btn">
                        <i class="fas fa-check"></i>
                        <span>{{ __('community_admin.create_reward_badge') }}</span>
                    </button>
                    <button type="button" class="btn-text" onclick="toggleAddBadge()">{{ __('community_admin.discard_changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="badges-grid">
        @forelse($group->badges as $badge)
            <div class="panel badge-card ed-row" id="badge-{{ $badge->id }}">
                <div class="ed-view badge-card-inner">
                    <div class="badge-visual-section">
                        <div class="badge-icon-wrap" style="background: {{ $badge->color }}15; color: {{ $badge->color }}; border-color: {{ $badge->color }}30;">
                            <i class="{{ $badge->icon ?? 'fas fa-award' }}"></i>
                        </div>
                    </div>
                    <div class="badge-content-section">
                        <h3 class="badge-name-title" data-view="name">{{ $badge->name }}</h3>
                        <div class="badge-pill-preview" style="background: {{ $badge->color }};">
                            <i class="{{ $badge->icon ?? 'fas fa-award' }}"></i>
                            <span>{{ $badge->name }}</span>
                        </div>
                    </div>
                    <div class="badge-actions-section" style="display:flex;gap:6px;">
                        <button type="button" class="btn-icon-danger-sm"
                                data-admin-edit data-target="#badge-{{ $badge->id }}"
                                title="{{ __('community_admin.edit') }}">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="btn-icon-danger-sm"
                                data-admin-delete="{{ route('communities.admin.badges.delete', [$group->slug, $badge->id]) }}"
                                data-target="#badge-{{ $badge->id }}"
                                data-confirm="{{ __('community_admin.delete_badge_confirm') }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="ed-form" style="display:none;padding:16px;">
                    <div class="form-group">
                        <label>{{ __('community_admin.badge_name') }}</label>
                        <input type="text" class="input" data-field="name" value="{{ $badge->name }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('community_admin.badge_color') }}</label>
                        <input type="text" class="input" data-field="color" value="{{ $badge->color }}" placeholder="#6366f1">
                    </div>
                    <div class="form-group">
                        <label>{{ __('community_admin.select_icon') }}</label>
                        <input type="text" class="input" data-field="icon" value="{{ $badge->icon }}" placeholder="fas fa-award">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn approve"
                                data-admin-save
                                data-url="{{ route('communities.admin.badges.update', [$group->slug, $badge->id]) }}"
                                data-target="#badge-{{ $badge->id }}">
                            {{ __('community_admin.save') }}
                        </button>
                        <button type="button" class="btn-text"
                                data-admin-cancel data-target="#badge-{{ $badge->id }}">
                            {{ __('community_admin.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="admin-empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon-wrap">
                    <i class="fas fa-award"></i>
                </div>
                <h3>{{ __('community_admin.no_badges_created') }}</h3>
                <p>{{ __('community_admin.no_badges_desc') }}</p>
                <button class="btn-link" onclick="toggleAddBadge()">{{ __('community_admin.design_first_badge') }}</button>
            </div>
        @endforelse
    </div>
</div>

<script>
    function toggleAddBadge() {
        const form = document.getElementById('add-badge-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function updateColorText(value) {
        document.getElementById('color-preview-text').innerText = value.toUpperCase();
    }

    function selectBadgeIcon(iconClass, element) {
        document.getElementById('selected-icon-input').value = iconClass;
        document.querySelectorAll('.icon-opt').forEach(opt => opt.classList.remove('active'));
        element.classList.add('active');
    }
</script>
        </div>
    </main>
</div>
@endsection
