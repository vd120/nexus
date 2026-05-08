@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="admin-page">
    <div class="admin-page-header admin-flex-header">
        <div class="header-text">
            <h1 class="admin-page-title">{{ __('community_admin.community_rules') }}</h1>
            <p class="admin-page-subtitle">{{ __('community_admin.community_rules_subtitle') }}</p>
        </div>
        <button class="mod-btn approve-btn" onclick="toggleAddRule()">
            <i class="fas fa-plus"></i> <span>{{ __('community_admin.add_new_rule') }}</span>
        </button>
    </div>

    {{-- Add Form --}}
    <div id="add-rule-form" class="panel add-form" style="display: none; margin-bottom: 32px;">
        <div class="panel-body">
            <form action="{{ route('communities.admin.rules.add', $group->slug) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>{{ __('community_admin.rule_title') }}</label>
                    <input type="text" name="title" required class="input" placeholder="{{ __('community_admin.rule_title_placeholder') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('community_admin.description') }}</label>
                    <textarea name="description" rows="3" class="input" placeholder="{{ __('community_admin.description_placeholder') }}"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn approve">{{ __('community_admin.save_rule') }}</button>
                    <button type="button" class="btn-text" onclick="toggleAddRule()">{{ __('community_admin.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="rules-grid">
        @forelse($group->rules as $rule)
            <div class="panel rule-card" id="rule-{{ $rule->id }}">
                <div class="rule-card-inner">
                    <div class="rule-header-row">
                        <div class="rule-badge-index">{{ $loop->iteration }}</div>
                        <div class="rule-main-info">
                            <h3 class="rule-card-title">{{ $rule->title }}</h3>
                        </div>
                        <div class="rule-card-actions">
                            <form action="{{ route('communities.admin.rules.delete', [$group->slug, $rule->id]) }}" method="POST" onsubmit="return confirm('{{ __('community_admin.delete_rule_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon-danger-sm">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @if($rule->description)
                        <div class="rule-card-body">
                            <p>{{ $rule->description }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="admin-empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon-wrap">
                    <i class="fas fa-gavel"></i>
                </div>
                <h3>{{ __('community_admin.no_rules_established') }}</h3>
                <p>{{ __('community_admin.no_rules_desc') }}</p>
                <button class="btn-link" onclick="toggleAddRule()">{{ __('community_admin.create_first_rule') }}</button>
            </div>
        @endforelse
    </div>
</div>

<style>
    .admin-page { max-width: 1100px; margin: 0 auto; }
    
    .admin-flex-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 40px; }
    
    .rules-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    
    .rule-card { border-radius: 24px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--border); background: var(--surface); }
    .rule-card:hover { border-color: var(--admin-accent); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    
    .rule-card-inner { padding: 24px; }
    .rule-header-row { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; }
    .rule-header-row:has(+ .rule-card-body) { margin-bottom: 16px; }
    
    .rule-badge-index { width: 36px; height: 36px; background: var(--admin-accent-glow); color: var(--admin-accent); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0; }
    
    .rule-main-info { flex: 1; }
    .rule-card-title { font-size: 16px; font-weight: 800; color: var(--text); margin: 0; line-height: 1.4; }
    
    .rule-card-body p { font-size: 14px; color: var(--text-muted); margin: 0; line-height: 1.6; font-weight: 500; }
    
    .btn-icon-danger-sm { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface-hover); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-icon-danger-sm:hover { border-color: #ef4444; color: #ef4444; background: rgba(239, 68, 68, 0.05); }

    /* Modern Form */
    .add-form { border-radius: 24px; border: 1px solid var(--admin-accent-glow); background: var(--surface); animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; padding-left: 4px; }
    .input { width: 100%; background: var(--surface-hover); border: 1px solid var(--border); padding: 12px 16px; border-radius: 14px; color: var(--text); font-size: 15px; font-weight: 500; transition: 0.2s; outline: none; }
    .input:focus { border-color: var(--admin-accent); background: var(--surface); }
    
    .form-actions { display: flex; gap: 12px; padding-top: 8px; }
    
    .mod-btn { height: 48px; padding: 0 24px; border-radius: 14px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.3s; border: 1px solid transparent; }
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
        .rules-grid { grid-template-columns: 1fr; }
        .admin-flex-header { flex-direction: column; align-items: flex-start; gap: 16px; }
    }
</style>

<script>
    function toggleAddRule() {
        const form = document.getElementById('add-rule-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>
        </div>
    </main>
</div>
@endsection
