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

<style>
    .community-members-view { max-width: 1000px; margin: 0 auto; }
    
    .members-search-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .members-count-title { margin: 0; }
    .members-count-title span { color: var(--community-accent); }

    .members-search-wrap { position: relative; width: 300px; }
    .members-search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; }
    .members-search-wrap input { width: 100%; background: var(--surface-hover); border: 1px solid var(--border); padding: 10px 14px 10px 40px; border-radius: 12px; color: var(--text); font-size: 14px; font-weight: 600; outline: none; transition: 0.2s; }
    .members-search-wrap input:focus { border-color: var(--community-accent); background: var(--surface); box-shadow: 0 0 0 4px var(--community-accent-soft); }

    .members-grid { display: grid; grid-template-columns: repeat(2, 1fr); border-top: 1px solid var(--border); }
    
    .member-entry { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); transition: 0.2s; }
    .member-entry:nth-child(even) { border-right: none; }
    .member-entry:hover { background: var(--surface-hover); }

    .user-cell { display: flex; align-items: center; gap: 14px; }
    .entry-avatar { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; background: var(--border); }
    
    .entry-meta { display: flex; flex-direction: column; }
    .entry-name { font-size: 15px; font-weight: 800; color: var(--text); text-decoration: none; transition: 0.2s; }
    .entry-name:hover { color: var(--community-accent); }
    
    .entry-role { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    .entry-role.admin { color: #f59e0b; }
    .entry-role.moderator { color: #3b82f6; }
    .entry-role.member { color: var(--text-muted); }

    .members-pagination { padding: 20px 24px; border-top: 1px solid var(--border); }

    @media (max-width: 768px) {
        .members-search-header { flex-direction: column; align-items: flex-start; padding: 16px; gap: 12px; }
        .members-search-wrap { width: 100%; }
        .members-grid { grid-template-columns: 1fr; }
        .member-entry { border-right: none; padding: 14px 16px; }
        .entry-avatar { width: 42px; height: 42px; border-radius: 12px; }
        .entry-name { font-size: 14px; }
        .members-main-card { border-radius: 16px; overflow: hidden; }
    }
</style>

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
