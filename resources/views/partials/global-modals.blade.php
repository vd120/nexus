<!-- Global Modals -->

<!-- Report Modal -->
<div id="report-modal" class="modal report-modal glass-modal">
    <div class="modal-content glass-content">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-flag"></i> {{ __('messages.report_post') }}</h3>
            <button type="button" class="modal-close" onclick="closeReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="report-form" method="POST">
            @csrf
            <div class="modal-body">
                <div class="report-info-banner">
                    <i class="fas fa-info-circle"></i>
                    <p class="report-description">{{ __('messages.report_description') }}</p>
                </div>
                
                <div class="form-group premium-group">
                    <label for="report-reason" class="premium-label">{{ __('messages.select_reason') }}</label>
                    <div class="select-wrapper">
                        <select name="reason" id="report-reason" required onchange="toggleOtherReason()" class="premium-select">
                            <option value="">{{ __('messages.choose_reason') }}</option>
                            <option value="spam">{{ __('messages.reason_spam') }}</option>
                            <option value="inappropriate">{{ __('messages.reason_inappropriate') }}</option>
                            <option value="harassment">{{ __('messages.reason_harassment') }}</option>
                            <option value="hate_speech">{{ __('messages.reason_hate_speech') }}</option>
                            <option value="violence">{{ __('messages.reason_violence') }}</option>
                            <option value="misinformation">{{ __('messages.reason_misinformation') }}</option>
                            <option value="copyright">{{ __('messages.reason_copyright') }}</option>
                            <option value="other">{{ __('messages.reason_other') }}</option>
                        </select>
                        <i class="fas fa-chevron-down select-icon"></i>
                    </div>
                </div>

                <div class="form-group premium-group" id="other-reason-group" style="display: none;">
                    <label for="report-content" class="premium-label">{{ __('messages.additional_details') }} ({{ __('messages.optional') }})</label>
                    <div class="textarea-wrapper">
                        <textarea name="content" id="report-content" rows="4" maxlength="1000" placeholder="{{ __('messages.report_details_placeholder') }}" class="premium-textarea"></textarea>
                        <div class="char-counter-badge">
                            <span id="char-count">0</span>/1000
                        </div>
                    </div>
                </div>

                <div class="report-warning premium-warning">
                    <div class="warning-icon-wrapper">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p>{{ __('messages.report_warning') }}</p>
                </div>
            </div>

            <div class="modal-footer glass-footer">
                <button type="button" class="btn btn-ghost" onclick="closeReportModal()">
                    {{ __('messages.cancel') }}
                </button>
                <button type="submit" class="btn btn-danger btn-premium" id="submit-report-btn" disabled>
                    <i class="fas fa-flag"></i> <span>{{ __('messages.submit_report') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Media Modal -->
<div id="media-modal" class="media-modal" onclick="closeMediaModal(event)">
    <div class="media-modal-content" onclick="event.stopPropagation()">
        <button class="media-modal-close" onclick="closeMediaModal()" title="{{ __('messages.close') }}">
            <i class="fas fa-times"></i>
        </button>
        <button class="media-modal-nav media-modal-prev" onclick="navigateMedia(-1)" title="{{ __('messages.previous') }}">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div id="media-modal-image"></div>
        <button class="media-modal-nav media-modal-next" onclick="navigateMedia(1)" title="{{ __('messages.next') }}">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="media-modal-counter" id="media-modal-counter"></div>
    </div>
</div>
