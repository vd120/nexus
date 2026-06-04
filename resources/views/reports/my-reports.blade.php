@extends('layouts.app')

@section('title', __('messages.my_reports'))

@push('styles')
@vite('resources/css/my-reports.css')
@endpush

@section('content')
<div class="my-reports-page">
    {{-- Header --}}
    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('home') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('messages.my_reports') }}</h1>
                <p>{{ __('messages.my_reports_subtitle') }}</p>
            </div>
        </div>
        <div class="header-actions">
            @if($stats['total'] > 0)
            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteAll()">
                <i class="fas fa-trash-alt"></i> {{ __('messages.clear_all_reports') }}
            </button>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $stats['total'] }}</span>
                <span class="stat-label">{{ __('messages.total_reports') }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $stats['pending'] }}</span>
                <span class="stat-label">{{ __('messages.pending_reports') }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon accepted">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $stats['accepted'] }}</span>
                <span class="stat-label">{{ __('messages.accepted_reports') }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $stats['rejected'] }}</span>
                <span class="stat-label">{{ __('messages.rejected_reports') }}</span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filters-section">
        <form method="GET" action="{{ route('reports.my-reports') }}" class="filter-form">
            <div class="filter-group">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">{{ __('messages.all_statuses') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>{{ __('messages.accepted') }}</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('messages.rejected') }}</option>
                </select>
                @if(request('status'))
                <a href="{{ route('reports.my-reports') }}" class="clear-btn">
                    <i class="fas fa-times"></i> {{ __('messages.clear_filter') }}
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Reports List --}}
    @if($reports->count() > 0)
    <div class="reports-list">
        @foreach($reports as $report)
        <div class="report-card {{ $report->status }}">
            <div class="report-header">
                <div class="report-meta">
                    <span class="status-badge {{ $report->status }}">
                        @if($report->status === 'pending')
                            <i class="fas fa-clock"></i> {{ __('messages.pending') }}
                        @elseif($report->status === 'accepted')
                            <i class="fas fa-check-circle"></i> {{ __('messages.accepted') }}
                        @else
                            <i class="fas fa-times-circle"></i> {{ __('messages.rejected') }}
                        @endif
                    </span>
                    <span class="reason-badge">{{ \App\Models\PostReport::REASONS[$report->reason] ?? $report->reason }}</span>
                    <span class="report-time">{{ $report->created_at->diffForHumans() }}</span>
                </div>
                <div class="report-actions">
                    <a href="{{ route('reports.show-user', $report->slug) }}" class="view-details-btn">
                        <i class="fas fa-eye"></i> {{ __('messages.view_details') }}
                    </a>
                    <button type="button" class="delete-report-btn" onclick="deleteReport('{{ $report->slug }}')" title="{{ __('messages.delete_report') }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="report-content">
                @if($report->post)
                <div class="post-preview">
                    <a href="{{ route('users.show', $report->post->user) }}" style="display:flex;flex-shrink:0;"><img src="{{ $report->post->user->avatar_url }}" alt="" class="avatar" style="pointer-events:none;"></a>
                    <div class="post-info">
                        <a href="{{ route('users.show', $report->post->user) }}" class="post-author" style="text-decoration:none;">{{ $report->post->user->username }}</a>
                        <span class="post-date">{{ $report->post->created_at->format('M d, Y') }}</span>
                    </div>
                    @if($report->post->media->count() > 0)
                    <div class="post-media-thumb">
                        @php $firstMedia = $report->post->media->first(); @endphp
                        @if($firstMedia->media_type === 'image')
                            <img src="{{ asset('storage/' . $firstMedia->media_path) }}" alt="">
                        @else
                            <i class="fas fa-video"></i>
                        @endif
                    </div>
                    @endif
                </div>
                
                @if($report->post->content)
                <div class="post-content-preview">
                    <div class="preview-header">
                        <i class="fas fa-newspaper"></i>
                        <span>{{ __('messages.reported_post_content') }}</span>
                    </div>
                    <p>{{ Str::limit($report->post->content, 200) }}</p>
                    <a href="{{ route('posts.show', $report->post->slug) }}" target="_blank" class="view-post-link">
                        <i class="fas fa-external-link-alt"></i> {{ __('messages.view_post') }}
                    </a>
                </div>
                @endif
                @else
                <div class="post-preview deleted">
                    <i class="fas fa-trash-alt"></i>
                    <span>{{ __('messages.post_deleted') }}</span>
                </div>
                @endif

                @if($report->content)
                <div class="report-details">
                    <div class="details-header">
                        <i class="fas fa-file-alt"></i>
                        <span>{{ __('messages.additional_details') }}</span>
                    </div>
                    <p>{{ $report->content }}</p>
                </div>
                @endif

                <div class="report-meta-info">
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ __('messages.reported_on') }}</span>
                        <strong>{{ $report->created_at->format('M d, Y • h:i a') }}</strong>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>{{ __('messages.updated_at') }}</span>
                        <strong>{{ $report->updated_at->diffForHumans() }}</strong>
                    </div>
                </div>

                @if($report->reviewed_by)
                <div class="review-info {{ $report->status }}">
                    @if($report->status === 'accepted')
                        <i class="fas fa-check-circle"></i>
                        <div class="review-content">
                            <div class="review-header">
                                <strong>{{ __('messages.report_accepted_title') }}</strong>
                                <span class="reviewer-badge">
                                    <i class="fas fa-user-shield"></i>
                                    {{ $report->reviewer->username ?? __('messages.admin') }}
                                </span>
                            </div>
                            @if($report->admin_action)
                            <div class="action-taken-badge">
                                <i class="fas fa-gavel"></i>
                                <span>{{ __('messages.action_' . $report->admin_action) }}</span>
                            </div>
                            @else
                            <p>{{ __('messages.report_action_taken') }}</p>
                            @endif
                            @if($report->admin_note)
                            <div class="admin-note">
                                <i class="fas fa-comment-alt"></i>
                                <span>{{ $report->admin_note }}</span>
                            </div>
                            @endif
                            <span class="review-time">{{ __('messages.reviewed_on') }} {{ $report->reviewed_at->format('M d, Y • h:i a') }}</span>
                        </div>
                    @else
                        <i class="fas fa-times-circle"></i>
                        <div class="review-content">
                            <div class="review-header">
                                <strong>{{ __('messages.report_rejected_title') }}</strong>
                                <span class="reviewer-badge">
                                    <i class="fas fa-user-shield"></i>
                                    {{ $report->reviewer->username ?? __('messages.admin') }}
                                </span>
                            </div>
                            @if($report->admin_note)
                                <div class="admin-note">
                                    <i class="fas fa-comment-alt"></i>
                                    <span>{{ $report->admin_note }}</span>
                                </div>
                            @else
                                <p>{{ __('messages.report_not_accepted') }}</p>
                            @endif
                            <span class="review-time">{{ __('messages.reviewed_on') }} {{ $report->reviewed_at->format('M d, Y • h:i a') }}</span>
                        </div>
                    @endif
                </div>
                @else
                <div class="pending-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>{{ __('messages.report_pending_review') }}</strong>
                        <p>{{ __('messages.report_pending_message') }}</p>
                    </div>
                </div>
                @endif
            </div>
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
        <h3>{{ __('messages.no_reports_found') }}</h3>
        <p>{{ __('messages.no_reports_message') }}</p>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="fas fa-home"></i> {{ __('messages.back_to_home') }}
        </a>
    </div>
    @endif
</div>

{{-- Delete All Confirmation Modal --}}
<div id="delete-all-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> {{ __('messages.confirm_delete_all') }}</h3>
            <button type="button" class="modal-close" onclick="closeDeleteAllModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>{{ __('messages.delete_all_reports_confirm') }}</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteAllModal()">
                {{ __('messages.cancel') }}
            </button>
            <form method="POST" action="{{ route('reports.delete-all') }}" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> {{ __('messages.delete_all') }}
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* Critical Responsive Overrides */
    @media (max-width: 768px) {
        .my-reports-page { padding: 1rem 0.5rem !important; }
        .stats-cards { grid-template-columns: repeat(2, 1fr) !important; gap: 0.5rem !important; }
        .stat-card { padding: 0.75rem !important; flex-direction: column !important; text-align: center !important; }
        .report-header { flex-direction: column !important; align-items: stretch !important; gap: 0.75rem !important; }
        .report-actions { display: flex !important; gap: 0.5rem !important; border-top: 1px solid var(--border) !important; padding-top: 0.75rem !important; }
        .view-details-btn { flex: 1 !important; justify-content: center !important; background: linear-gradient(135deg, var(--primary), #8b5cf6) !important; color: white !important; }
    }
    .report-card { 
        background: rgba(255, 255, 255, 0.03) !important; 
        backdrop-filter: blur(10px) !important; 
        border: 1px solid rgba(255, 255, 255, 0.05) !important; 
    }
</style>
<script>
function deleteReport(reportSlug) {
    if (!confirm('{{ __('messages.delete_report_confirm') }}')) {
        return;
    }

    fetch(`/reports/${reportSlug}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Find the report card and remove it
            const deleteBtn = document.querySelector(`button[onclick="deleteReport('${reportSlug}')"]`);
            const card = deleteBtn ? deleteBtn.closest('.report-card') : null;
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 300);
            }
            showToast(data.message || 'Report deleted', 'success');
        } else {
            alert(data.message || '{{ __('messages.error_deleting_report') }}');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('{{ __('messages.error_deleting_report') }}');
    });
}

function confirmDeleteAll() {
    document.getElementById('delete-all-modal').classList.add('show');
}

function closeDeleteAllModal() {
    document.getElementById('delete-all-modal').classList.remove('show');
}

// Close modal on outside click
document.getElementById('delete-all-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteAllModal();
    }
});
</script>
@endsection
