@extends('layouts.app')

@section('title', 'Your Chat Groups - Nexus')

@section('content')
<div class="chat-groups-container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800;">Chat Groups</h1>
            <p style="color: var(--text-muted);">Private messaging groups for you and your friends.</p>
        </div>
        <a href="{{ route('groups.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Group
        </a>
    </header>

    <div class="groups-list" style="display: flex; flex-direction: column; gap: 16px;">
        @forelse($groups as $group)
            <div class="group-chat-item" style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px; display: flex; align-items: center; gap: 16px; transition: all 0.2s; cursor: pointer;" onclick="window.location.href='{{ route('chat.show', $group->conversation->slug ?? $group->slug) }}'">
                <img src="{{ $group->avatar ? asset('storage/' . $group->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($group->name) }}" alt="" style="width: 60px; height: 60px; border-radius: 12px; object-fit: cover;">
                <div style="flex: 1;">
                    <h3 style="margin: 0; font-size: 18px;">{{ $group->name }}</h3>
                    <p style="margin: 4px 0 0; color: var(--text-muted); font-size: 14px;">{{ $group->members_count }} members</p>
                </div>
                <div class="group-actions">
                    <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 60px; background: var(--surface); border-radius: 24px; border: 1px dashed var(--border);">
                <i class="fas fa-comments" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                <h3>No chat groups yet</h3>
                <p>Create a group to start messaging with multiple people.</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    .group-chat-item:hover {
        transform: translateX(4px);
        border-color: var(--primary);
        background: var(--surface-hover);
    }

    @media (max-width: 600px) {
        .chat-groups-container {
            margin: 20px auto !important;
        }
        header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 16px;
        }
        header .btn {
            width: 100%;
            justify-content: center;
        }
        .group-chat-item {
            padding: 12px !important;
        }
        .group-chat-item img {
            width: 50px !important;
            height: 50px !important;
        }
    }
</style>
@endsection
