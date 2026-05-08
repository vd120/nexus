@extends('layouts.app')

@section('content')
<div class="community-page-container">
    @include('communities.partials.header')

    <div class="community-body-wrap">

<div class="community-about-grid">
    {{-- Main Column --}}
    <div class="about-main-col">
        <div class="panel about-card">
            <div class="panel-header">
                <h3>{{ __('messages.about_this_community') }}</h3>
            </div>
            <div class="panel-body">
                <div class="description-content">
                    {{ $group->description ?? __('messages.no_description') }}
                </div>
            </div>
        </div>

        <div class="panel about-card">
            <div class="panel-header">
                <h3>{{ __('messages.community_rules') }}</h3>
            </div>
            <div class="panel-body">
                <div class="rules-stack">
                    @forelse($group->rules as $rule)
                        <div class="rule-box">
                            <div class="rule-head">
                                <span class="rule-index">{{ $loop->iteration }}</span>
                                <h4 class="rule-title">{{ $rule->title }}</h4>
                            </div>
                            <div class="rule-text">
                                {{ $rule->description }}
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-mini">
                            <i class="fas fa-gavel"></i>
                            <p>{{ __('messages.no_rules_set') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar Column --}}
    <div class="about-side-col">
        <div class="panel info-card">
            <div class="panel-header">
                <h3>{{ __('messages.details') }}</h3>
            </div>
            <div class="panel-body">
                <div class="details-list">
                    <div class="detail-row">
                        <div class="icon-circle"><i class="fas fa-history"></i></div>
                        <div class="info">
                            <label>{{ __('messages.created_at_label') }}</label>
                            <strong>{{ $group->created_at->format('M d, Y') }}</strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="icon-circle"><i class="fas fa-shield-alt"></i></div>
                        <div class="info">
                            <label>{{ __('messages.privacy_label') }}</label>
                            <strong>{{ __('messages.' . $group->privacy_level) }}</strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="icon-circle"><i class="fas fa-eye"></i></div>
                        <div class="info">
                            <label>{{ __('messages.visibility_label') }}</label>
                            <strong>{{ $group->is_discoverable ? __('messages.publicly_visible') : __('messages.hidden') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($group->topics->count() > 0)
        <div class="panel info-card">
            <div class="panel-header">
                <h3>{{ __('messages.popular_topics') }}</h3>
            </div>
            <div class="panel-body">
                <div class="topics-cloud">
                    @foreach($group->topics as $topic)
                        <span class="topic-pill">#{{ $topic->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    .community-about-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
    
    .description-content { font-size: 16px; line-height: 1.8; color: var(--text); white-space: pre-wrap; font-weight: 500; }

    .rules-stack { display: flex; flex-direction: column; gap: 20px; }
    .rule-box { padding-bottom: 20px; border-bottom: 1px solid var(--border); }
    .rule-box:last-child { border-bottom: none; padding-bottom: 0; }
    
    .rule-head { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
    .rule-index { width: 28px; height: 28px; background: var(--community-accent-soft); color: var(--community-accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; }
    .rule-title { font-size: 16px; font-weight: 800; color: var(--text); margin: 0; }
    .rule-text { color: var(--text-muted); font-size: 14px; line-height: 1.6; padding-left: 40px; font-weight: 500; }

    .details-list { display: flex; flex-direction: column; gap: 20px; }
    .detail-row { display: flex; align-items: center; gap: 16px; }
    .icon-circle { width: 44px; height: 44px; background: var(--surface-hover); color: var(--community-accent); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid var(--border); }
    
    .detail-row .info { display: flex; flex-direction: column; }
    .detail-row label { font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .detail-row strong { font-size: 14px; font-weight: 700; color: var(--text); }

    .topics-cloud { display: flex; flex-wrap: wrap; gap: 10px; }
    .topic-pill { background: var(--surface-hover); color: var(--community-accent); padding: 8px 16px; border-radius: 100px; font-weight: 800; font-size: 13px; border: 1px solid var(--border); transition: 0.2s; cursor: default; }
    .topic-pill:hover { background: var(--community-accent); color: white; transform: translateY(-2px); }

    .empty-state-mini { text-align: center; padding: 32px 20px; color: var(--text-muted); }
    .empty-state-mini i { font-size: 24px; margin-bottom: 12px; opacity: 0.5; }
    .empty-state-mini p { font-size: 14px; font-weight: 600; margin: 0; }

    @media (max-width: 900px) {
        .community-about-grid { grid-template-columns: 1fr; gap: 16px; }
        .about-side-col { order: 2; }
        .about-main-col { order: 1; }

        .about-card { border-radius: 16px; }
        .panel-header { padding: 16px 20px; }
        .panel-body { padding: 20px; }

        .description-content { font-size: 14px; line-height: 1.6; }
        
        .rule-box { padding-bottom: 16px; }
        .rule-text { padding-left: 0; margin-top: 10px; font-size: 13px; }
        
        .info-card { border-radius: 16px; }
    }
</style>
    </div>
</div>
@endsection
