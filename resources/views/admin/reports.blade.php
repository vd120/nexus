@extends('layouts.app')

@section('title', __('admin.reports') . ' - Admin Panel')

@push('styles')
@vite('resources/css/admin-reports.css')
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
                <h1>{{ __('admin.reports') }}</h1>
                <p>{{ __('admin.manage_reports_subtitle') }}</p>
            </div>
        </div>
        <div class="header-stats">
            <span class="total-badge">{{ $stats['total'] }} {{ __('admin.total') }}</span>
            <span class="status-badge pending">{{ $stats['pending'] }} {{ __('admin.pending') }}</span>
            <span class="status-badge accepted">{{ $stats['accepted'] }} {{ __('admin.accepted') }}</span>
            <span class="status-badge rejected">{{ $stats['rejected'] }} {{ __('admin.rejected') }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filters-section">
        <form method="GET" action="{{ route('admin.reports') }}" class="filter-form">
            <div class="filter-group">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_reports') }}" autocomplete="off">
                </div>
                @if(request('search'))
                <a href="{{ route('admin.reports') }}" class="clear-btn">
                    <i class="fas fa-times"></i> {{ __('admin.clear') }}
                </a>
                @endif
            </div>

            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.pending') }}</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>{{ __('admin.accepted') }}</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('admin.rejected') }}</option>
                </select>

                <select name="reason" class="filter-select">
                    <option value="">{{ __('admin.all_reasons') }}</option>
                    @foreach(\App\Models\PostReport::REASONS as $value => $label)
                    <option value="{{ $value }}" {{ request('reason') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i> {{ __('admin.filter') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Bulk Actions --}}
    @if($reports->count() > 0 && request('status') !== 'accepted' && request('status') !== 'rejected')
    <div class="bulk-actions">
        <label class="checkbox-label">
            <input type="checkbox" id="select-all">
            <span>{{ __('admin.select_all') }}</span>
        </label>
        <form method="POST" action="{{ route('admin.reports.bulk-accept') }}" class="bulk-form" id="bulk-accept-form">
            @csrf
            <input type="hidden" name="action" value="delete">
            <div id="selected-ids"></div>
            <button type="submit" class="btn btn-danger btn-sm" disabled id="bulk-accept-btn">
                <i class="fas fa-check"></i> {{ __('admin.accept_selected') }}
            </button>
        </form>
        <form method="POST" action="{{ route('admin.reports.bulk-reject') }}" class="bulk-form" id="bulk-reject-form">
            @csrf
            <div id="selected-ids-reject"></div>
            <button type="submit" class="btn btn-secondary btn-sm" disabled id="bulk-reject-btn">
                <i class="fas fa-times"></i> {{ __('admin.reject_selected') }}
            </button>
        </form>
    </div>
    @endif

    {{-- Reports List --}}
    @if($reports->count() > 0)
    <div class="reports-section">
        @foreach($reports as $report)
        <div class="report-card {{ $report->status }}">
            <div class="report-header">
                <div class="report-meta">
                    @if(auth()->user()->is_admin)
                    <label class="checkbox-label">
                        <input type="checkbox" class="report-checkbox" value="{{ $report->id }}" {{ !$report->isPending() ? 'disabled' : '' }}>
                    </label>
                    @endif
                    <span class="status-badge {{ $report->status }}">
                        @if($report->status === 'pending')
                            <i class="fas fa-clock"></i> {{ __('admin.pending') }}
                        @elseif($report->status === 'accepted')
                            <i class="fas fa-check-circle"></i> {{ __('admin.accepted') }}
                        @else
                            <i class="fas fa-times-circle"></i> {{ __('admin.rejected') }}
                        @endif
                    </span>
                    <span class="reason-badge">{{ \App\Models\PostReport::REASONS[$report->reason] ?? $report->reason }}</span>
                    <span class="report-time">{{ $report->created_at->diffForHumans() }}</span>
                </div>
                <a href="{{ route('admin.reports.show', $report) }}" class="view-btn">
                    <i class="fas fa-eye"></i> {{ __('admin.view_details') }}
                </a>
            </div>

            <div class="report-content">
                <div class="reporter-info">
                    <img src="{{ $report->reporter->avatar_url }}" alt="" class="avatar">
                    <div>
                        <span class="reporter-name">{{ $report->reporter->username }}</span>
                        <span class="reporter-email">{{ $report->reporter->email }}</span>
                    </div>
                </div>

                @if($report->post)
                <div class="post-preview">
                    <div class="post-info">
                        <img src="{{ $report->post->user->avatar_url }}" alt="" class="avatar">
                        <div>
                            <span class="post-author">{{ $report->post->user->username }}</span>
                            <span class="post-date">{{ $report->post->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    @if($report->post->media->count() > 0)
                    <div class="post-media-preview">
                        @php $firstMedia = $report->post->media->first(); @endphp
                        @if($firstMedia->media_type === 'image')
                            <img src="{{ asset('storage/' . $firstMedia->media_path) }}" alt="">
                        @else
                            <i class="fas fa-video"></i>
                        @endif
                    </div>
                    @endif
                </div>
                @else
                <div class="post-preview">
                    <div class="post-info">
                        <i class="fas fa-trash-alt" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                        <div>
                            <span class="post-author" style="color: var(--danger-color);">{{ __('admin.post_deleted') }}</span>
                            <span class="post-date">{{ __('admin.post_no_longer_available') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                @if($report->content)
                <div class="report-details">
                    <p><strong>{{ __('admin.additional_details') }}:</strong></p>
                    <p>{{ Str::limit($report->content, 150) }}</p>
                </div>
                @endif

                @if($report->reviewed_by)
                <div class="review-info">
                    <span><strong>{{ __('admin.reviewed_by') }}:</strong> {{ $report->reviewer->username ?? 'Unknown' }}</span>
                    @if($report->admin_note)
                    <span><strong>{{ __('admin.admin_note') }}:</strong> {{ Str::limit($report->admin_note, 100) }}</span>
                    @endif
                </div>
                @endif
            </div>

            @if($report->isPending())
            <div class="report-actions">
                <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-gavel"></i> {{ __('admin.take_action') }}
                </a>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pagination-section">
        {{ $reports->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <i class="fas fa-clipboard-check"></i>
        <h3>{{ __('admin.no_reports_found') }}</h3>
        <p>{{ __('admin.no_reports_message') }}</p>
    </div>
    @endif
</div>

<script>
window.runOnPageLoad( function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.report-checkbox');
    const bulkAcceptForm = document.getElementById('bulk-accept-form');
    const bulkRejectForm = document.getElementById('bulk-reject-form');
    const bulkAcceptBtn = document.getElementById('bulk-accept-btn');
    const bulkRejectBtn = document.getElementById('bulk-reject-btn');
    const selectedIdsInput = document.getElementById('selected-ids');
    const selectedIdsRejectInput = document.getElementById('selected-ids-reject');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = this.checked;
                }
            });
            updateBulkButtons();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkButtons);
    });

    function updateBulkButtons() {
        const selected = Array.from(checkboxes)
            .filter(cb => cb.checked && !cb.disabled)
            .map(cb => cb.value);

        if (selected.length > 0) {
            // Update accept form
            selectedIdsInput.innerHTML = selected.map(id =>
                `<input type="hidden" name="report_ids[]" value="${id}">`
            ).join('');
            if (bulkAcceptBtn) bulkAcceptBtn.disabled = false;

            // Update reject form
            selectedIdsRejectInput.innerHTML = selected.map(id =>
                `<input type="hidden" name="report_ids[]" value="${id}">`
            ).join('');
            if (bulkRejectBtn) bulkRejectBtn.disabled = false;
        } else {
            if (bulkAcceptBtn) bulkAcceptBtn.disabled = true;
            if (bulkRejectBtn) bulkRejectBtn.disabled = true;
        }
    }
});
</script>

@endsection
