@extends('layouts.app')

@section('content')
<div class="community-page-container">
    @include('communities.partials.header')

    <div class="community-body-wrap">

<div class="community-members-view">
    <div class="panel members-main-card">
        <div class="panel-header members-search-header">
            <h3 class="members-count-title">{{ __('messages.group_members') }} · <span>{{ number_format($group->members_count) }}</span></h3>
            <div class="members-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="{{ __('messages.search_members_placeholder') }}" id="member-search-input">
            </div>
        </div>

        <div class="panel-body no-padding">
            <div class="members-grid" id="members-list">
                @foreach($members as $member)
                    <div class="member-entry">
                        <div class="user-cell">
                            <img src="{{ $member->user->avatar_url }}" alt="" class="entry-avatar">
                            <div class="entry-meta">
                                <a href="{{ route('users.show', $member->user) }}" class="entry-name">{{ $member->user->name }}</a>
                                <span class="entry-role {{ $member->role }}">{{ __('messages.role_' . $member->role) }}</span>
                            </div>
                        </div>
                        <div class="entry-actions">
                            @if(auth()->id() !== $member->user_id)
                                <a href="{{ route('chat.start', $member->user->id) }}" class="btn-icon" title="{{ __('messages.message') }}">
                                    <i class="fas fa-comment"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($members->hasPages())
        <div class="panel-footer members-pagination">
            {{ $members->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    document.getElementById('member-search-input')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const entries = document.querySelectorAll('.member-entry');
        
        entries.forEach(entry => {
            const name = entry.querySelector('.entry-name').textContent.toLowerCase();
            entry.style.display = name.includes(query) ? 'flex' : 'none';
        });
    });
</script>
    </div>
</div>
@endsection
