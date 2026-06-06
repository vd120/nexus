@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="admin-page">
    <div class="admin-page-header admin-flex-header">
        <div class="header-text">
            <h1 class="admin-page-title">{{ __('community_admin.community_topics') }}</h1>
            <p class="admin-page-subtitle">{{ __('community_admin.community_topics_subtitle') }}</p>
        </div>
        <button class="mod-btn approve-btn" onclick="toggleAddTopic()">
            <i class="fas fa-plus"></i> <span>{{ __('community_admin.create_new_topic') }}</span>
        </button>
    </div>

    {{-- Add Form --}}
    <div id="add-topic-form" class="panel add-form" style="display: none; margin-bottom: 32px;">
        <div class="panel-body">
            <form action="{{ route('communities.admin.topics.add', $group->slug) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>{{ __('community_admin.topic_name') }}</label>
                    <input type="text" name="name" required class="input" placeholder="{{ __('community_admin.topic_placeholder') }}">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn approve">{{ __('community_admin.create_topic') }}</button>
                    <button type="button" class="btn-text" onclick="toggleAddTopic()">{{ __('community_admin.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="topics-grid">
        @forelse($group->topics as $topic)
            <div class="panel topic-card ed-row" id="topic-{{ $topic->id }}">
                <div class="ed-view topic-card-inner">
                    <div class="topic-main-content">
                        <div class="topic-badge-icon">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="topic-details">
                            <h3 class="topic-title" data-view="name">{{ $topic->name }}</h3>
                        </div>
                    </div>
                    <div class="topic-actions" style="display:flex;gap:6px;">
                        <button type="button" class="btn-icon-danger-sm"
                                data-admin-edit data-target="#topic-{{ $topic->id }}"
                                title="{{ __('community_admin.edit') }}">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="btn-icon-danger-sm"
                                data-admin-delete="{{ route('communities.admin.topics.delete', [$group->slug, $topic->id]) }}"
                                data-target="#topic-{{ $topic->id }}"
                                data-confirm="{{ __('community_admin.delete_topic_confirm') }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="ed-form" style="display:none;padding:16px;">
                    <div class="form-group">
                        <label>{{ __('community_admin.topic_name') }}</label>
                        <input type="text" class="input" data-field="name" value="{{ $topic->name }}">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn approve"
                                data-admin-save
                                data-url="{{ route('communities.admin.topics.update', [$group->slug, $topic->id]) }}"
                                data-target="#topic-{{ $topic->id }}">
                            {{ __('community_admin.save') }}
                        </button>
                        <button type="button" class="btn-text"
                                data-admin-cancel data-target="#topic-{{ $topic->id }}">
                            {{ __('community_admin.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="admin-empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon-wrap">
                    <i class="fas fa-tags"></i>
                </div>
                <h3>{{ __('community_admin.no_topics_found') }}</h3>
                <p>{{ __('community_admin.no_topics_desc') }}</p>
                <button class="btn-link" onclick="toggleAddTopic()">{{ __('community_admin.create_first_topic') }}</button>
            </div>
        @endforelse
    </div>
</div>

<script>
    function toggleAddTopic() {
        const form = document.getElementById('add-topic-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>
        </div>
    </main>
</div>
@endsection
