@extends('layouts.app')

@section('content')
<div class="community-page-container">
    @include('communities.partials.header')

    <div class="community-body-wrap">

<div class="community-feed-grid">
    {{-- Main Feed Area --}}
    <div class="feed-content-main">
        @if($group->canPost(auth()->user()))
            @include('communities.partials.post-composer', ['group' => $group])
        @elseif(!$group->isMember(auth()->user()))
            <div class="card join-promo">
                <div class="promo-icon"><i class="fas fa-users"></i></div>
                <h3>{{ __('messages.join_to_participate') }}</h3>
                <p>{{ __('messages.join_promo_desc') }}</p>
                <button class="btn-action-primary" onclick="joinGroup('{{ $group->slug }}')">{{ __('messages.join_community_btn') }}</button>
            </div>
        @endif

        <div id="posts-feed" class="feed-posts-list" data-group-id="{{ $group->id }}">
            @forelse($posts as $post)
                @include('partials.post', ['post' => $post, 'group' => $group, 'hideGroupContext' => true])
            @empty
                <div class="empty-state">
                    <i class="fas fa-stream"></i>
                    <h3>{{ __('messages.no_posts_title') }}</h3>
                    <p>{{ __('messages.no_posts_desc') }}</p>
                </div>
            @endforelse
            
            <div class="pagination-wrap">
                {{ $posts->links() }}
            </div>
        </div>
    </div>

</div>

<style>
    .community-page-container {
        max-width: 1024px;
        margin: 0 auto;
        padding-bottom: 80px;
    }

    .community-feed-grid {
        display: flex;
        justify-content: center;
        gap: 24px;
        align-items: start;
    }

    .feed-content-main {
        max-width: 680px;
        width: 100%;
        margin: 0 auto;
    }

    .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
    }


    /* Feed List */
    .feed-posts-list { display: flex; flex-direction: column; gap: 20px; }

    /* Sidebar Modules */
    .module-title { font-size: 16px; font-weight: 800; margin-bottom: 12px; color: var(--text); }
    .module-text { font-size: 14px; line-height: 1.5; color: var(--text-muted); margin-bottom: 16px; }
    .module-meta-list { display: flex; flex-direction: column; gap: 10px; }
    .meta-item { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: var(--text); }
    .meta-item i { color: var(--text-muted); width: 16px; }

    .mini-rules-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
    .mini-rule { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text); }
    .mini-rule .num { width: 22px; height: 22px; background: var(--surface-hover); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--community-accent); }
    
    .module-link { display: block; font-size: 13px; font-weight: 700; color: var(--community-accent); text-decoration: none; }

    .join-promo { text-align: center; padding: 40px 20px; border: 2px dashed var(--border); }
    .promo-icon { font-size: 40px; color: var(--text-muted); margin-bottom: 16px; }
    .join-promo h3 { font-size: 18px; margin-bottom: 8px; }
    .join-promo p { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; }

    .empty-state { text-align: center; padding: 60px 20px; background: var(--surface); border-radius: 20px; border: 1px solid var(--border); }
    .empty-state i { font-size: 40px; color: var(--border); margin-bottom: 16px; }

    @media (max-width: 900px) {
        .community-page-container { padding-bottom: 100px; max-width: 100%; }
        .community-feed-grid { grid-template-columns: 1fr; gap: 16px; }
        .feed-info-sidebar { order: 2; padding: 0 16px; }
        .feed-content-main { order: 1; max-width: 100%; padding: 0; }
        
        .composer-trigger { 
            border-radius: 0; 
            padding: 16px; 
            margin-bottom: 12px;
            border-left: none;
            border-right: none;
            box-shadow: none;
        }
        
        .trigger-top { gap: 10px; margin-bottom: 8px; }
        .user-avatar-sm { width: 36px; height: 36px; }
        .fake-input { padding: 8px 16px; font-size: 13px; }
        
        .trigger-footer { gap: 4px; padding-top: 10px; }
        .trigger-footer .action { padding: 6px 10px; font-size: 12px; gap: 6px; }
        .trigger-footer .action i { font-size: 15px; }

        .feed-posts-list { gap: 12px; }
        
        .empty-state { padding: 40px 16px; border-radius: 16px; margin: 0 16px; }
        .empty-state i { font-size: 32px; }

        .card { padding: 16px; border-radius: 16px; }
    }

    /* Edge-to-edge mobile alignment */
    @media (max-width: 768px) {
        :is(.app-layout, .main-content) { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .community-body-wrap { padding: 0; }
        .feed-posts-list { gap: 8px; }
    }
</style>

<script>
    function joinGroup(slug) {
        fetch(`/communities/${slug}/join`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(res => res.json()).then(data => {
            if (data.status === 'approved') {
                window.location.reload();
            } else {
                showToast(data.message);
            }
        });
    }
</script>
    </div>
</div>
@endsection
