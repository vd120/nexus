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
            <div class="panel badge-card" id="badge-{{ $badge->id }}">
                <div class="badge-card-inner">
                    <div class="badge-visual-section">
                        <div class="badge-icon-wrap" style="background: {{ $badge->color }}15; color: {{ $badge->color }}; border-color: {{ $badge->color }}30;">
                            <i class="{{ $badge->icon ?? 'fas fa-award' }}"></i>
                        </div>
                    </div>
                    <div class="badge-content-section">
                        <h3 class="badge-name-title">{{ $badge->name }}</h3>
                        <div class="badge-pill-preview" style="background: {{ $badge->color }};">
                            <i class="{{ $badge->icon ?? 'fas fa-award' }}"></i>
                            <span>{{ $badge->name }}</span>
                        </div>
                    </div>
                    <div class="badge-actions-section">
                        <form action="{{ route('communities.admin.badges.delete', [$group->slug, $badge->id]) }}" method="POST" onsubmit="return confirm('{{ __('community_admin.delete_badge_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon-danger-sm">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
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

<style>
    .admin-page { max-width: 1100px; margin: 0 auto; }
    
    .admin-flex-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 40px; }
    
    .badges-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    
    .badge-card { border-radius: 24px; border: 1px solid var(--border); background: var(--surface); transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .badge-card:hover { border-color: var(--admin-accent); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    
    .badge-card-inner { padding: 24px; display: flex; align-items: center; gap: 20px; }
    
    .badge-icon-wrap { width: 56px; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; border: 1px solid transparent; flex-shrink: 0; }
    
    .badge-content-section { flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .badge-name-title { font-size: 17px; font-weight: 800; color: var(--text); margin: 0; }
    
    .badge-pill-preview { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 100px; color: white; font-size: 12px; font-weight: 800; width: fit-content; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .badge-pill-preview i { font-size: 14px; }

    .btn-icon-danger-sm { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-hover); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-icon-danger-sm:hover { border-color: #ef4444; color: #ef4444; background: rgba(239, 68, 68, 0.05); }

    /* Modern Form */
    .add-form { border-radius: 28px; border: 1px solid var(--admin-accent-glow); background: var(--surface); animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    
    .form-section-header { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
    .form-section-header i { font-size: 24px; color: var(--admin-accent); }
    .form-section-header h3 { font-size: 18px; font-weight: 800; color: var(--text); margin: 0; }
    .form-section-header p { font-size: 13px; color: var(--text-muted); margin: 0; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    
    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; padding-left: 4px; }
    
    .icon-selector-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(48px, 1fr)); gap: 10px; padding: 12px; background: var(--surface-hover); border-radius: 16px; border: 1px solid var(--border); }
    .icon-opt { aspect-ratio: 1; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--text-muted); cursor: pointer; transition: 0.2s; border: 1px solid transparent; }
    .icon-opt:hover { background: var(--border); color: var(--text); }
    .icon-opt.active { background: var(--admin-accent-glow); color: var(--admin-accent); border-color: var(--admin-accent); }

    .input { width: 100%; background: var(--surface-hover); border: 1px solid var(--border); padding: 12px 16px; border-radius: 14px; color: var(--text); font-size: 15px; font-weight: 500; transition: 0.2s; outline: none; }
    .input:focus { border-color: var(--admin-accent); background: var(--surface); }
    
    .color-picker-wrapper { display: flex; align-items: center; gap: 12px; background: var(--surface-hover); padding: 5px 16px; border-radius: 14px; border: 1px solid var(--border); }
    .color-input { width: 40px; height: 40px; border: none; border-radius: 8px; cursor: pointer; background: none; }
    .color-value-text { font-size: 14px; font-weight: 700; color: var(--text); font-family: monospace; }

    .form-actions { display: flex; gap: 12px; padding-top: 10px; }
    
    .mod-btn { height: 52px; padding: 0 28px; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; border: 1px solid transparent; }
    .approve-btn { background: var(--admin-accent); color: white; }
    .approve-btn:hover { background: #4f46e5; transform: scale(1.02); }
    
    .btn-text { background: none; border: none; color: var(--text-muted); font-weight: 700; cursor: pointer; padding: 0 16px; }
    .btn-text:hover { color: var(--text); }

    /* Empty State */
    .admin-empty-state { padding: 80px 40px; text-align: center; background: var(--surface); border-radius: 32px; border: 1px solid var(--border); }
    .empty-icon-wrap { width: 80px; height: 80px; background: var(--admin-accent-glow); color: var(--admin-accent); border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; }
    .admin-empty-state h3 { font-size: 24px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .admin-empty-state p { color: var(--text-muted); margin-bottom: 24px; }
    .btn-link { background: none; border: none; color: var(--admin-accent); text-decoration: none; font-weight: 700; font-size: 14px; cursor: pointer; }

    @media (max-width: 900px) {
        .badges-grid { grid-template-columns: 1fr; }
        .grid-2 { grid-template-columns: 1fr; }
        .admin-flex-header { flex-direction: column; align-items: flex-start; gap: 16px; }
    }
</style>

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
