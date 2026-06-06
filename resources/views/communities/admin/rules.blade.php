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
            <div class="panel rule-card ed-row" id="rule-{{ $rule->id }}">
                <div class="ed-view rule-card-inner">
                    <div class="rule-header-row">
                        <div class="rule-badge-index">{{ $loop->iteration }}</div>
                        <div class="rule-main-info">
                            <h3 class="rule-card-title" data-view="title">{{ $rule->title }}</h3>
                        </div>
                        <div class="rule-card-actions" style="display:flex;gap:6px;">
                            <button type="button" class="btn-icon-danger-sm"
                                    data-admin-edit data-target="#rule-{{ $rule->id }}"
                                    title="{{ __('community_admin.edit') }}">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="btn-icon-danger-sm"
                                    data-admin-delete="{{ route('communities.admin.rules.delete', [$group->slug, $rule->id]) }}"
                                    data-target="#rule-{{ $rule->id }}"
                                    data-confirm="{{ __('community_admin.delete_rule_confirm') }}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    @if($rule->description)
                        <div class="rule-card-body">
                            <p data-view="description">{{ $rule->description }}</p>
                        </div>
                    @else
                        <div class="rule-card-body" style="display:none">
                            <p data-view="description"></p>
                        </div>
                    @endif
                </div>
                <div class="ed-form" style="display:none;padding:16px;">
                    <div class="form-group">
                        <label>{{ __('community_admin.rule_title') }}</label>
                        <input type="text" class="input" data-field="title" value="{{ $rule->title }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('community_admin.description') }}</label>
                        <textarea class="input" rows="3" data-field="description">{{ $rule->description }}</textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn approve"
                                data-admin-save
                                data-url="{{ route('communities.admin.rules.update', [$group->slug, $rule->id]) }}"
                                data-target="#rule-{{ $rule->id }}">
                            {{ __('community_admin.save_rule') }}
                        </button>
                        <button type="button" class="btn-text"
                                data-admin-cancel data-target="#rule-{{ $rule->id }}">
                            {{ __('community_admin.cancel') }}
                        </button>
                    </div>
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
