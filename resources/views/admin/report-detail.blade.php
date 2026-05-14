@extends('layouts.app')

@section('title', __('admin.report_details') . ' - Admin Panel')

@section('content')
<div class="admin-page">
    {{-- Header --}}
    <div class="admin-header">
        <div class="header-left">
            <a href="{{ route('admin.reports') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('admin.report_details') }}</h1>
                <p>{{ __('admin.report_details_subtitle') }}</p>
            </div>
        </div>
        <div class="header-actions">
            <span class="status-badge {{ $report->status }}">
                @if($report->status === 'pending')
                    <i class="fas fa-clock"></i> {{ __('admin.pending') }}
                @elseif($report->status === 'accepted')
                    <i class="fas fa-check-circle"></i> {{ __('admin.accepted') }}
                @else
                    <i class="fas fa-times-circle"></i> {{ __('admin.rejected') }}
                @endif
            </span>
        </div>
    </div>

    {{-- Report Content --}}
    <div class="report-detail-section">
        {{-- Report Information --}}
        <div class="detail-card">
            <h2 style="color: #ffffff !important;"><i class="fas fa-flag"></i> {{ __('admin.report_information') }}</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <b style="color: #86868b !important; font-size: 0.85rem;">{{ __('admin.report_slug') }}:</b>
                    <span class="slug-text" style="color: #5e60ce !important; font-family: monospace; font-weight: bold;">{{ $report->slug }}</span>
                </div>
                <div class="detail-item">
                    <b style="color: #86868b !important; font-size: 0.85rem;">{{ __('admin.reason') }}:</b>
                    <span class="reason-badge" style="background: rgba(94, 96, 206, 0.1); color: #ffffff !important; padding: 4px 12px; border-radius: 20px;">{{ \App\Models\PostReport::REASONS[$report->reason] ?? $report->reason }}</span>
                </div>
                <div class="detail-item">
                    <b style="color: #86868b !important; font-size: 0.85rem;">{{ __('admin.reported_by') }}:</b>
                    <span style="color: #ffffff !important;">{{ $report->created_at->format('M d, Y h:i a') }}</span>
                </div>
                <div class="detail-item">
                    <b style="color: #86868b !important; font-size: 0.85rem;">{{ __('admin.status') }}:</b>
                    <span class="status-badge {{ $report->status }}" style="font-weight: bold;">
                        @if($report->status === 'pending')
                            {{ __('admin.pending') }}
                        @elseif($report->status === 'accepted')
                            {{ __('admin.accepted') }}
                        @else
                            {{ __('admin.rejected') }}
                        @endif
                    </span>
                </div>
            </div>

            @if($report->content)
            <div class="detail-section">
                <b style="color: #86868b !important; display: block; margin-bottom: 8px;">{{ __('admin.additional_details') }}:</b>
                <p class="content-text" style="color: #ffffff !important; background: rgba(255, 255, 255, 0.05); padding: 12px; border-radius: 8px;">{{ $report->content }}</p>
            </div>
            @endif
        </div>

        {{-- Reporter Information --}}
        <div class="detail-card">
            <h2 style="color: #ffffff !important;"><i class="fas fa-user"></i> {{ __('admin.reporter_information') }}</h2>
            <div class="user-profile" style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                <img src="{{ $report->reporter->avatar_url }}" alt="" class="profile-avatar" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--border);">
                <div class="profile-info">
                    <h3 style="color: #ffffff !important; margin: 0;">{{ $report->reporter->username }}</h3>
                    <p style="color: #86868b !important; margin: 2px 0;">{{ $report->reporter->name }}</p>
                </div>
            </div>
            <a href="{{ route('admin.users.show', $report->reporter) }}" class="btn btn-secondary btn-sm" style="color: #ffffff !important; border-color: #444;">
                <i class="fas fa-user-circle"></i> {{ __('admin.view_profile') }}
            </a>
        </div>

        {{-- Reported Post --}}
        <div class="detail-card">
            <h2 style="color: #ffffff !important;"><i class="fas fa-newspaper"></i> {{ __('admin.reported_post') }}</h2>
            @if($report->post)
            <div class="post-detail">
                <div class="post-header" style="display: -webkit-inline-box; -webkit-box-align: center; vertical-align: middle; gap: 12px; margin-bottom: 15px;">
                    <img src="{{ $report->post->user->avatar_url }}" alt="" class="avatar" style="width: 40px; height: 40px; border-radius: 50%; display: block;">
                    <div class="post-meta" style="display: -webkit-inline-box; -webkit-box-orient: vertical; -webkit-box-pack: center; margin-left: 10px;">
                        <b class="author" style="color: #ffffff !important; display: block; line-height: 1.2;">{{ $report->post->user->username }}</b>
                        <span class="date" style="color: #86868b !important; font-size: 0.8rem; line-height: 1.2;">
                            {{ $report->post->created_at->diffForHumans() }}
                            @if($report->post->trashed())
                                <span style="color: #ef4444; font-weight: bold; margin-left: 5px;">[{{ __('admin.suspended') }}]</span>
                            @endif
                        </span>
                    </div>
                </div>

                @if($report->post->content)
                <div class="post-content" style="background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                    <p style="color: #ffffff !important; margin: 0;">{{ $report->post->content }}</p>
                </div>
                @endif

                @if($report->post->media->count() > 0)
                <div class="post-media" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 15px;">
                    @foreach($report->post->media as $media)
                        @if($media->media_type === 'image')
                            <img src="{{ asset('storage/' . $media->media_path) }}" alt="" style="width: 100%; height: 300px; object-fit: cover; border-radius: 12px; border: 1px solid #333;">
                        @elseif($media->media_type === 'video')
                            <video controls style="width: 100%; height: 300px; object-fit: cover; border-radius: 12px; border: 1px solid #333;">
                                <source src="{{ asset('storage/' . $media->media_path) }}" type="video/mp4">
                            </video>
                        @endif
                    @endforeach
                </div>
                @endif

                <div class="post-stats" style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <span style="color: #86868b !important;"><i class="fas fa-heart" style="color: #5e60ce;"></i> {{ $report->post->likes->count() }} {{ __('admin.likes') }}</span>
                    <span style="color: #86868b !important;"><i class="fas fa-comment" style="color: #5e60ce;"></i> {{ $report->post->comments->count() }} {{ __('admin.comments') }}</span>
                </div>

                <a href="{{ route('posts.show', $report->post->slug) }}" target="_blank" class="btn btn-secondary btn-sm" style="color: #ffffff !important; border-color: #444;">
                    <i class="fas fa-external-link-alt"></i> {{ __('admin.view_post') }}
                </a>
            </div>
            @endif
        </div>

        {{-- Action Section (only for pending reports) --}}
        @if($report->isPending() && $report->post)
        <div class="detail-card action-card" style="border: 2px solid #5e60ce !important;">
            <h2 style="color: #ffffff !important;"><i class="fas fa-gavel"></i> {{ __('admin.take_action_on_report') }}</h2>

            <form method="POST" action="{{ route('admin.reports.accept', $report) }}" class="action-form">
                @csrf
                <div class="action-options">
                    <h3 style="color: #ffffff !important; font-size: 1rem; margin-bottom: 15px;">{{ __('admin.select_action') }}:</h3>
                    <div class="option-group" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <label class="option-card">
                            <input type="radio" name="action" value="delete" checked>
                            <div class="option-content">
                                <i class="fas fa-trash" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <b style="color: #ffffff !important; display: block;">{{ __('admin.delete_post') }}</b>
                                <small style="color: #86868b !important; display: block; font-size: 0.75rem;">{{ __('admin.delete_post_description') }}</small>
                            </div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="action" value="hide">
                            <div class="option-content">
                                <i class="fas fa-eye-slash" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <b style="color: #ffffff !important; display: block;">{{ __('admin.hide_post') }}</b>
                                <small style="color: #86868b !important; display: block; font-size: 0.75rem;">{{ __('admin.hide_post_description') }}</small>
                            </div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="action" value="warning">
                            <div class="option-content">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <b style="color: #ffffff !important; display: block;">{{ __('admin.issue_warning') }}</b>
                                <small style="color: #86868b !important; display: block; font-size: 0.75rem;">{{ __('admin.issue_warning_description') }}</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <b style="color: #ffffff !important; display: block; margin-bottom: 8px;">{{ __('admin.admin_note') }}:</b>
                    <textarea name="admin_note" rows="3" style="background: rgba(255, 255, 255, 0.05); color: #ffffff !important; border: 1px solid #444; width: 100%; border-radius: 8px; padding: 10px;" placeholder="{{ __('admin.admin_note_placeholder') }}"></textarea>
                </div>

                <div class="action-buttons" style="margin-top: 20px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-success" style="background: #22c55e !important; color: #fff !important; font-weight: bold; padding: 10px 20px;">
                        <i class="fas fa-check"></i> {{ __('admin.accept_report_and_take_action') }}
                    </button>
                    <button type="button" class="btn btn-danger" style="background: #ef4444 !important; color: #fff !important; font-weight: bold; padding: 10px 20px;" onclick="document.getElementById('reject-form').classList.toggle('hidden')">
                        <i class="fas fa-times"></i> {{ __('admin.reject_report') }}
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.reports.reject', $report) }}" class="action-form hidden" id="reject-form">
                @csrf
                <div class="form-group">
                    <label for="admin_note_reject">{{ __('admin.admin_note') }} ({{ __('admin.optional') }}):</label>
                    <textarea name="admin_note" id="admin_note_reject" rows="3" placeholder="{{ __('admin.reject_note_placeholder') }}"></textarea>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-times"></i> {{ __('admin.reject_report') }}
                    </button>
                    <button type="button" class="btn btn-link" onclick="document.getElementById('reject-form').classList.add('hidden')">
                        {{ __('admin.cancel') }}
                    </button>
                </div>
            </form>
        </div>
        @elseif($report->isPending() && !$report->post)
        <div class="detail-card">
            <h2><i class="fas fa-info-circle"></i> {{ __('admin.report_status') }}</h2>
            <div class="status-message">
                <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                <h3>{{ __('admin.post_already_deleted') }}</h3>
                <p>{{ __('admin.post_already_deleted_message') }}</p>
                <div class="action-buttons" style="justify-content: center; margin-top: 1rem;">
                    <form method="POST" action="{{ route('admin.reports.reject', $report) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-check"></i> {{ __('admin.mark_as_reviewed') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @else
        {{-- Already processed --}}
        <div class="detail-card">
            <h2><i class="fas fa-info-circle"></i> {{ __('admin.report_status') }}</h2>
            <div class="status-message {{ $report->status }}">
                @if($report->status === 'accepted')
                    <i class="fas fa-check-circle"></i>
                    <h3>{{ __('admin.report_accepted_title') }}</h3>
                    <p>{{ __('admin.report_accepted_message') }}</p>
                @else
                    <i class="fas fa-times-circle"></i>
                    <h3>{{ __('admin.report_rejected_title') }}</h3>
                    <p>{{ __('admin.report_rejected_message') }}</p>
                @endif
                @if($report->admin_note)
                <div class="admin-note">
                    <strong>{{ __('admin.admin_note') }}:</strong>
                    <p>{{ $report->admin_note }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin-reports.css') }}">
<script>
// Add click handlers for option cards to improve selection visibility
window.runOnPageLoad( function() {
    const optionCards = document.querySelectorAll('.option-card');
    
    optionCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking directly on the radio
            if (e.target.tagName !== 'INPUT') {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    
                    // Update visual feedback
                    optionCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                }
            }
        });
    });
    
    // Initialize first option as selected
    const firstRadio = document.querySelector('.option-card input[type="radio"]:checked');
    if (firstRadio) {
        firstRadio.closest('.option-card').classList.add('selected');
    }
});
</script>
@endsection
