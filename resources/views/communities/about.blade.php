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
    </div>
</div>
@endsection
