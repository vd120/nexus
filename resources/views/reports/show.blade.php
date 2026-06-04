@extends('layouts.app')

@section('title', __('messages.report_details'))

@push('styles')
@vite('resources/css/report-detail-user.css')
@endpush

@section('content')
<div class="report-detail-page">
    {{-- Header --}}
    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('reports.my-reports') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('messages.report_details') }}</h1>
                <p>{{ __('messages.report_details_subtitle') }}</p>
            </div>
        </div>
        <div class="header-actions">
            <span class="status-badge {{ $report->status }}">
                @if($report->status === 'pending')
                    <i class="fas fa-clock"></i> {{ __('messages.pending') }}
                @elseif($report->status === 'accepted')
                    <i class="fas fa-check-circle"></i> {{ __('messages.accepted') }}
                @else
                    <i class="fas fa-times-circle"></i> {{ __('messages.rejected') }}
                @endif
            </span>
        </div>
    </div>

    <div class="detail-content">
        {{-- Report Info Card --}}
        <div class="detail-card">
            <h2><i class="fas fa-flag"></i> {{ __('messages.report_information') }}</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>{{ __('messages.report_slug') }}:</label>
                    <span class="slug-text">{{ $report->slug }}</span>
                </div>
                <div class="info-item">
                    <label>{{ __('messages.reason') }}:</label>
                    <span class="reason-badge">{{ \App\Models\PostReport::REASONS[$report->reason] ?? $report->reason }}</span>
                </div>
                <div class="info-item">
                    <label>{{ __('messages.submitted_on') }}:</label>
                    <span>{{ $report->created_at->format('M d, Y • h:i a') }}</span>
                </div>
                <div class="info-item">
                    <label>{{ __('messages.last_updated') }}:</label>
                    <span>{{ $report->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            @if($report->content)
            <div class="info-section">
                <label><i class="fas fa-file-alt"></i> {{ __('messages.additional_details') }}:</label>
                <p class="content-text">{{ $report->content }}</p>
            </div>
            @endif
        </div>

        {{-- Reported Post Card --}}
        <div class="detail-card">
            <h2><i class="fas fa-newspaper"></i> {{ __('messages.reported_post') }}</h2>
            @if($report->post && !$report->post->trashed())
            <div class="post-detail">
                <div class="post-header">
                    <a href="{{ route('users.show', $report->post->author) }}" style="display:flex;flex-shrink:0;"><img src="{{ $report->post->author->avatar_url }}" alt="" class="avatar" style="pointer-events:none;"></a>
                    <div class="post-meta">
                        <a href="{{ route('users.show', $report->post->author) }}" class="author" style="text-decoration:none;">{{ $report->post->author->username }}</a>
                        <span class="date">{{ $report->post->created_at->diffForHumans() }}</span>
                        @if($report->post->is_private)
                        <span class="private-badge"><i class="fas fa-lock"></i> {{ __('messages.private') }}</span>
                        @endif
                    </div>
                </div>

                @if($report->post->content)
                <div class="post-content">
                    <p>{{ $report->post->content }}</p>
                </div>
                @endif

                @if($report->post->media->count() > 0)
                <div class="post-media">
                    @foreach($report->post->media as $media)
                        @if($media->media_type === 'image')
                            <img src="{{ asset('storage/' . $media->media_path) }}" alt="">
                        @elseif($media->media_type === 'video')
                            <video controls>
                                <source src="{{ asset('storage/' . $media->media_path) }}" type="video/mp4">
                            </video>
                        @endif
                    @endforeach
                </div>
                @endif

                <div class="post-stats">
                    <span><i class="fas fa-heart"></i> {{ $report->post->likes->count() }} {{ __('messages.likes') }}</span>
                    <span><i class="fas fa-comment"></i> {{ $report->post->comments->count() }} {{ __('messages.comments') }}</span>
                </div>

                <a href="{{ route('posts.show', $report->post->slug) }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> {{ __('messages.view_post') }}
                </a>
            </div>
            @else
            <div class="post-deleted-notice">
                <i class="fas fa-trash-alt"></i>
                <h3>{{ __('messages.post_deleted') }}</h3>
                <p>{{ __('messages.post_no_longer_available') }}</p>
            </div>
            @endif
        </div>

        {{-- Review Status Card --}}
        <div class="detail-card">
            <h2><i class="fas fa-gavel"></i> {{ __('messages.review_status') }}</h2>
            @if($report->reviewed_by)
            <div class="review-status {{ $report->status }}">
                @if($report->status === 'accepted')
                <div class="status-header accepted">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>{{ __('messages.report_accepted_title') }}</strong>
                        <span class="reviewer-info">
                            <i class="fas fa-user-shield"></i>
                            {{ $report->reviewer->username ?? __('messages.admin') }}
                        </span>
                    </div>
                </div>
                @if($report->admin_action)
                <div class="action-taken">
                    <i class="fas fa-gavel"></i>
                    <span>{{ __('messages.action_' . $report->admin_action) }}</span>
                </div>
                @endif
                @if($report->admin_note)
                <div class="admin-note">
                    <i class="fas fa-comment-alt"></i>
                    <div>
                        <strong>{{ __('messages.admin_note') }}:</strong>
                        <p>{{ $report->admin_note }}</p>
                    </div>
                </div>
                @endif
                <div class="review-timestamp">
                    <i class="fas fa-clock"></i>
                    <span>{{ __('messages.reviewed_on') }} {{ $report->reviewed_at->format('M d, Y • h:i a') }}</span>
                </div>
                @else
                <div class="status-header rejected">
                    <i class="fas fa-times-circle"></i>
                    <div>
                        <strong>{{ __('messages.report_rejected_title') }}</strong>
                        <span class="reviewer-info">
                            <i class="fas fa-user-shield"></i>
                            {{ $report->reviewer->username ?? __('messages.admin') }}
                        </span>
                    </div>
                </div>
                @if($report->admin_note)
                <div class="admin-note">
                    <i class="fas fa-comment-alt"></i>
                    <div>
                        <strong>{{ __('messages.admin_note') }}:</strong>
                        <p>{{ $report->admin_note }}</p>
                    </div>
                </div>
                @endif
                <div class="review-timestamp">
                    <i class="fas fa-clock"></i>
                    <span>{{ __('messages.reviewed_on') }} {{ $report->reviewed_at->format('M d, Y • h:i a') }}</span>
                </div>
                @endif
            </div>
            @else
            <div class="pending-status">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>{{ __('messages.report_pending_review') }}</strong>
                    <p>{{ __('messages.report_pending_detailed_message') }}</p>
                </div>
                <div class="pending-info">
                    <i class="fas fa-clock"></i>
                    <span>{{ __('messages.waiting_since') }} {{ $report->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="detail-actions">
            <button type="button" class="btn btn-danger" onclick="deleteReport('{{ $report->slug }}')">
                <i class="fas fa-trash"></i> {{ __('messages.delete_report') }}
            </button>
            <a href="{{ route('reports.my-reports') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.back_to_reports') }}
            </a>
        </div>
    </div>
</div>

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
            window.location.href = '{{ route("reports.my-reports") }}';
        } else {
            alert(data.message || '{{ __('messages.error_deleting_report') }}');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('{{ __('messages.error_deleting_report') }}');
    });
}
</script>
@endsection
