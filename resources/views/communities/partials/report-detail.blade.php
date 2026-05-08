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

<style>
    .report-meta-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 24px;
    }

    .reporter-info {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .reporter-info img {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid var(--border);
    }

    .meta-label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .meta-value {
        font-weight: 700;
        font-size: 15px;
    }

    .reason-text {
        color: #f43f5e;
    }

    .content-investigation {
        margin-bottom: 32px;
    }

    .investigation-label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 12px;
        letter-spacing: 0.5px;
    }

    .content-preview-box {
        background: var(--surface-hover);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 24px;
        max-height: 400px;
        overflow-y: auto;
    }

    .admin-action-footer {
        display: flex;
        gap: 12px;
        padding-top: 24px;
        border-top: 1px solid var(--border);
    }

    .btn-warn {
        background: rgba(255, 184, 0, 0.1);
        border: 1px solid rgba(255, 184, 0, 0.2);
        color: #ffb800;
        padding: 14px;
        border-radius: var(--radius-lg);
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-warn:hover { background: #ffb800; color: white; }

    .btn-delete-full {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #ef4444;
        padding: 14px;
        border-radius: var(--radius-lg);
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-delete-full:hover { background: #ef4444; color: white; }
</style>

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
