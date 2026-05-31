@extends('layouts.app')

@push('styles')
    @vite('resources/css/communities.css')
@endpush

@section('title', __('messages.discover_communities_title'))

@section('content')
<div class="discover-page">
    {{-- Header --}}
    <div class="discover-header">
        <div class="text" style="display: flex; align-items: flex-start; gap: 12px;">
            <a href="javascript:history.back()" class="btn back-btn" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 0; min-width: 44px; margin-top: 5px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title">{{ __('messages.communities_header') }}</h1>
                <p class="page-subtitle">{{ __('messages.communities_subtitle') }}</p>
            </div>
        </div>
        <button class="btn approve create-btn" onclick="openCreateGroupModal()">
            <i class="fas fa-plus"></i> {{ __('messages.create_new') }}
        </button>
    </div>

    {{-- My Communities (Horizontal Scroll) --}}
    @if($myGroups->count() > 0)
    <section class="section">
        <h2 class="section-title">{{ __('messages.your_communities') }}</h2>
        <div class="horizontal-scroll-container">
            <div class="horizontal-scroll">
                @foreach($myGroups as $group)
                    <a href="{{ route('communities.show', $group->slug) }}" class="mini-card">
                        <div class="mini-content-alt">
                            <img src="{{ $group->avatar_url }}" alt="" class="mini-avatar-alt">
                            <div class="mini-info">
                                <strong>{{ $group->name }}</strong>
                                <span data-mini-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }} {{ __('messages.members_label') }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Discovery Grid --}}
    <section class="section">
        <div class="discovery-header">
            <h2 class="section-title">{{ __('messages.discover') }}</h2>
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="community-search" placeholder="{{ __('messages.search_communities') }}">
            </div>
        </div>
        
        <div class="discovery-grid" id="groups-grid">
            @forelse($groups as $group)
                <a href="{{ route('communities.show', $group->slug) }}" class="discovery-card">
                    <div class="card-body-alt">
                        <img src="{{ $group->avatar_url }}" alt="" class="card-avatar-alt">
                        <div class="card-text">
                            <h3>{{ $group->name }}</h3>
                            <p>{{ Str::limit($group->description, 80) }}</p>
                        </div>
                        <div class="card-footer">
                            <span class="member-tag" data-discovery-members-count="{{ $group->slug }}"><i class="fas fa-users"></i> {{ number_format($group->members_count) }}</span>
                            @if(in_array($group->id, $joinedIds))
                                <span class="joined-badge"><i class="fas fa-check-circle"></i> {{ __('messages.joined') }}</span>
                            @else
                                <span class="join-hint">{{ __('messages.enter') }} <i class="fas fa-arrow-right"></i></span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="empty-state-card">
                    <div class="empty-icon"><i class="fas fa-search"></i></div>
                    <h3>{{ __('messages.no_communities_found') }}</h3>
                    <p>{{ __('messages.no_communities_desc') }}</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="pagination">
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

    function closeCreateGroupModal() {
        document.getElementById('createGroupModal').style.display = 'none';
    }

    document.getElementById('community-search')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.discovery-card');
        
        cards.forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const desc = card.querySelector('p').textContent.toLowerCase();
            if (name.includes(query) || desc.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
@endsection
