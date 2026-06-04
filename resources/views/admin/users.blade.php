@extends('layouts.app')

@section('title', __('admin.manage_users') . ' - Admin Panel')

@push('styles')
@vite('resources/css/admin-users.css')
@endpush

@section('content')
<div class="admin-page">
    {{-- Header --}}
    <div class="admin-header">
        <div class="header-left">
            <a href="{{ route('admin.dashboard') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('admin.users') }}</h1>
                <p>{{ __('admin.manage_users_subtitle') }}</p>
            </div>
        </div>
        <div class="header-stats">
            <span class="total-badge">{{ $users->total() }} {{ __('admin.total') }}</span>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="filters-section">
        <div class="search-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" value="{{ request('search') }}" placeholder="{{ __('admin.search_users') }}" autocomplete="off">
            </div>
            <select id="filter-select" class="filter-select">
                <option value="">{{ __('admin.all_users') }}</option>
                <option value="admin" {{ request('admin_filter') === 'admin' ? 'selected' : '' }}>{{ __('admin.admins_only') }}</option>
                <option value="user" {{ request('admin_filter') === 'user' ? 'selected' : '' }}>{{ __('admin.regular_users') }}</option>
            </select>
            @if(request('search') || request('admin_filter'))
            <a href="{{ route('admin.users') }}" class="clear-btn">
                <i class="fas fa-times"></i> {{ __('admin.clear') }}
            </a>
            @endif
        </div>
    </div>

    {{-- Users Table --}}
    @if($users->count() > 0)
    <div class="users-table">
        <div class="table-header-row">
            <div class="col-user">{{ __('admin.user') }}</div>
            <div class="col-email">{{ __('admin.email') }}</div>
            <div class="col-role">{{ __('admin.role') }}</div>
            <div class="col-status">{{ __('admin.status') }}</div>
            <div class="col-joined">{{ __('admin.joined') }}</div>
            <div class="col-actions">{{ __('admin.actions') }}</div>
        </div>

        @foreach($users as $user)
        <div class="table-row">
            <div class="col-user">
                <div class="user-cell">
                    <div class="user-avatar">
                        <img src="{{ $user->avatar_url }}" alt="">
                    </div>
                    <div class="user-details">
                        <span class="user-name" style="display:inline-flex;align-items:center;gap:.2em;">{{ $user->username }}<x-verified-badge :user="$user" size=".8em" /></span>
                        @if($user->profile && $user->profile->bio)
                        <span class="user-bio">{{ Str::limit($user->profile->bio, 25) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-email">{{ $user->email }}</div>
            <div class="col-role">
                @if($user->is_admin)
                <span class="badge admin">{{ __('admin.admin_badge') }}</span>
                @else
                <span class="badge user">{{ __('admin.user_badge') }}</span>
                @endif
            </div>
            <div class="col-status">
                @if($user->is_suspended)
                <span class="badge suspended">{{ __('admin.suspended') }}</span>
                @elseif($user->profile && $user->profile->is_private)
                <span class="badge private">{{ __('admin.private_badge') }}</span>
                @else
                <span class="badge active">{{ __('admin.active') }}</span>
                @endif
            </div>
            <div class="col-joined">{{ $user->created_at->format('M j, Y') }}</div>
            <div class="col-actions">
                <div class="action-buttons">
                    <a href="{{ route('admin.users.show', $user) }}" class="action-btn view" title="{{ __('admin.view') }}">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}" class="action-btn edit" title="{{ __('admin.edit') }}">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($user->id !== auth()->id() && !$user->is_admin)
                    <form method="POST" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('{{ __('admin.delete_user_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-btn delete" title="{{ __('admin.delete') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $users->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-users"></i>
        </div>
        <h3>{{ __('admin.no_users_found') }}</h3>
        <p>{{ __('admin.no_users_match') }}</p>
    </div>
    @endif
</div>

<script>
let searchTimeout;

window.runOnPageLoad( function() {
    const searchInput = document.getElementById('search-input');
    const filterSelect = document.getElementById('filter-select');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                if (query.length === 0) {
                    applyFilter();
                }
                return;
            }

            searchTimeout = setTimeout(function() {
                applyFilter();
            }, 500);
        });
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            applyFilter();
        });
    }
});

function applyFilter() {
    const search = document.getElementById('search-input').value.trim();
    const filter = document.getElementById('filter-select').value;
    
    let url = '{{ route("admin.users") }}?';
    const params = [];
    
    if (search.length >= 2) {
        params.push('search=' + encodeURIComponent(search));
    }
    if (filter) {
        params.push('admin_filter=' + encodeURIComponent(filter));
    }
    
    window.location.href = url + params.join('&');
}
</script>
@endsection
