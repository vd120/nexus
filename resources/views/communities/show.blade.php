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
