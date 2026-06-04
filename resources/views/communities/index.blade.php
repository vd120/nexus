@extends('layouts.app')

@push('styles')
    @vite('resources/css/communities.css')
@endpush

@section('content_class', 'wide-content')
@section('main_class', 'full-width')
@section('title', __('messages.discover_communities_title'))

@section('content')
<div class="discover-page">

    {{-- Sticky top bar --}}
    <div class="comm-topbar">
        <div class="comm-topbar-left">
            <a href="javascript:history.back()" class="comm-back-btn" aria-label="Back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="comm-page-title">{{ __('messages.communities_header') }}</h1>
        </div>
        <div class="comm-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="community-search" placeholder="{{ __('messages.search_communities') }}" autocomplete="off">
        </div>
        <button class="comm-create-btn" onclick="openCreateGroupModal()">
            <i class="fas fa-plus"></i>
            <span>{{ __('messages.create_new') }}</span>
        </button>
    </div>

    {{-- Your Communities --}}
    @if($myGroups->count() > 0)
    <section class="comm-section">
        <p class="comm-section-label">{{ __('messages.your_communities') }}</p>
        <div class="comm-mine-scroll">
            @foreach($myGroups as $group)
                <a href="{{ route('communities.show', $group->slug) }}" class="comm-mine-pill">
                    <img src="{{ $group->avatar_url }}" alt="" class="comm-mine-avatar">
                    <span>{{ $group->name }}</span>
                    <span class="comm-mine-count" data-mini-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Discover list --}}
    <section class="comm-section">
        <p class="comm-section-label">{{ __('messages.discover') }}</p>
        <div class="comm-list" id="groups-grid">
            @forelse($groups as $group)
                <a href="{{ route('communities.show', $group->slug) }}" class="comm-row">
                    <img src="{{ $group->avatar_url }}" alt="" class="comm-row-avatar">
                    <div class="comm-row-body">
                        <span class="comm-row-name">{{ $group->name }}</span>
                        <span class="comm-row-desc">{{ Str::limit($group->description, 70) }}</span>
                    </div>
                    <div class="comm-row-meta">
                        <span class="comm-row-count" data-discovery-members-count="{{ $group->slug }}">
                            <i class="fas fa-users"></i> {{ number_format($group->members_count) }}
                        </span>
                        <span class="comm-row-privacy">
                            @if($group->privacy_level === 'private')
                                <i class="fas fa-lock"></i>
                            @else
                                <i class="fas fa-globe"></i>
                            @endif
                        </span>
                    </div>
                    <div class="comm-row-action">
                        @if(in_array($group->id, $joinedIds))
                            <span class="comm-badge-joined"><i class="fas fa-check"></i> {{ __('messages.joined') }}</span>
                        @else
                            <span class="comm-btn-join">{{ __('messages.enter') }} <i class="fas fa-arrow-right"></i></span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="comm-empty">
                    <i class="fas fa-search"></i>
                    <h3>{{ __('messages.no_communities_found') }}</h3>
                    <p>{{ __('messages.no_communities_desc') }}</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="pagination" style="padding: 0 24px 40px; max-width: 1100px; margin: 0 auto;">
        {{ $groups->links() }}
    </div>

</div>

{{-- Create Modal --}}
<div id="createGroupModal" class="nexus-modal-overlay" style="display: none;" onclick="closeCreateGroupModal(event)">
    <div class="nexus-modal-card" onclick="event.stopPropagation()">
        <div class="nexus-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3>{{ __('messages.create_community_title') }}</h3>
                    <p>{{ __('messages.create_community_subtitle') }}</p>
                </div>
            </div>
            <button class="close-btn" onclick="closeCreateGroupModal()"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('communities.store') }}" method="POST">
            @csrf
            <div class="nexus-modal-body">
                <div class="nexus-form-group">
                    <label>{{ __('messages.community_name') }}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-signature"></i>
                        <input type="text" name="name" required placeholder="{{ __('messages.community_name_placeholder') }}">
                    </div>
                </div>
                <div class="nexus-form-group">
                    <label>{{ __('messages.group_description') }}</label>
                    <div class="input-wrapper textarea-wrapper">
                        <i class="fas fa-align-left"></i>
                        <textarea name="description" required rows="3" placeholder="{{ __('messages.description_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="grid-2-nexus">
                    <div class="nexus-form-group">
                        <label>{{ __('messages.privacy_level_label') }}</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <select name="privacy_level">
                                <option value="public">{{ __('messages.public_desc') }}</option>
                                <option value="private">{{ __('messages.private_desc') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="nexus-form-group">
                        <label>{{ __('messages.posting_rights') }}</label>
                        <div class="input-wrapper">
                            <i class="fas fa-pen-nib"></i>
                            <select name="posting_permission">
                                <option value="anyone">{{ __('messages.anyone_can_post') }}</option>
                                <option value="admins_only">{{ __('messages.admins_only_post') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nexus-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCreateGroupModal()">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn-submit">
                    <span>{{ __('messages.create_community_title') }}</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateGroupModal() {
        document.getElementById('createGroupModal').style.display = 'flex';
    }

    function closeCreateGroupModal(e) {
        if (!e || e.target === document.getElementById('createGroupModal')) {
            document.getElementById('createGroupModal').style.display = 'none';
        }
    }

    document.getElementById('community-search')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.comm-row').forEach(row => {
            const name = row.querySelector('.comm-row-name')?.textContent.toLowerCase() || '';
            const desc = row.querySelector('.comm-row-desc')?.textContent.toLowerCase() || '';
            row.style.display = (name.includes(query) || desc.includes(query)) ? 'flex' : 'none';
        });
    });
</script>
@endsection
