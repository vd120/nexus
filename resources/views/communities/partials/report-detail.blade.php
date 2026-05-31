<div id="report-detail-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content report-modal-box">
        <div class="modal-header">
            <h3 class="flex items-center gap-3">
                <i class="fas fa-flag text-danger"></i> Report Investigation
            </h3>
            <button class="close-modal" onclick="closeReportDetail()">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Reporter Info -->
            <div class="report-meta-box">
                <div class="reporter-info">
                    <img id="reporter-avatar" src="">
                    <div class="text">
                        <span class="meta-label">Reported By</span>
                        <span id="reporter-name" class="meta-value"></span>
                    </div>
                </div>
                <div class="reason-info">
                    <span class="meta-label">Reason</span>
                    <span id="report-reason" class="meta-value reason-text"></span>
                </div>
            </div>

            <!-- Offending Content -->
            <div class="content-investigation">
                <label class="investigation-label">Offending Content</label>
                <div id="report-post-preview" class="content-preview-box">
                    <!-- Post content injected here -->
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="admin-action-footer">
                <button onclick="warnAuthor()" class="btn btn-ghost flex-grow" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.2);">
                    <i class="fas fa-exclamation-triangle"></i> Warn Author
                </button>
                <button onclick="deleteReportedPost()" class="btn btn-primary flex-grow" style="background: #f43f5e; border-color: #f43f5e;">
                    <i class="fas fa-trash"></i> Delete Post
                </button>
                <button onclick="dismissReport()" class="btn btn-ghost">
                    Dismiss
                </button>
            </div>
        </div>
    </div>
</div>


<script>
function openReportDetail(reportId) {
    document.getElementById('report-detail-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeReportDetail() {
    document.getElementById('report-detail-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}
</script>
