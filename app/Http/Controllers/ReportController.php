<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display a form to report a post
     */
    public function create(Post $post)
    {
        return view('posts.report', compact('post'));
    }

    /**
     * Store a new post report
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'reason' => 'required|in:spam,inappropriate,harassment,hate_speech,violence,misinformation,copyright,other',
            'content' => 'nullable|string|max:1000',
        ]);

        // Prevent self-reporting
        if ($post->user_id === Auth::id()) {
            $errorMsg = __('messages.cannot_report_self') ?? 'You cannot report your own post.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $errorMsg], 422);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        // Check if user already reported this post
        $existingReport = PostReport::where('post_id', $post->id)
            ->where('user_id', Auth::id())
            ->where('status', PostReport::STATUS_PENDING)
            ->exists();

        if ($existingReport) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => __('messages.already_reported')
                ], 422);
            }
            return redirect()->back()->with('error', __('messages.already_reported'));
        }

        $report = PostReport::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'reason' => $request->reason,
            'content' => $request->content,
            'status' => PostReport::STATUS_PENDING,
        ]);

        // Real-time Notification for Admins
        $this->notifyAdminsOfNewReport($report);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.report_submitted')
            ]);
        }

        return redirect()->back()->with('success', __('messages.report_submitted'));
    }

    /**
     * Display user's submitted reports
     */
    public function myReports(Request $request)
    {
        $query = PostReport::where('user_id', Auth::id())
            ->with(['post.user', 'post.media', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(20);

        $stats = [
            'total' => PostReport::where('user_id', Auth::id())->count(),
            'pending' => PostReport::where('user_id', Auth::id())->where('status', PostReport::STATUS_PENDING)->count(),
            'accepted' => PostReport::where('user_id', Auth::id())->where('status', PostReport::STATUS_ACCEPTED)->count(),
            'rejected' => PostReport::where('user_id', Auth::id())->where('status', PostReport::STATUS_REJECTED)->count(),
        ];

        return view('reports.my-reports', compact('reports', 'stats'));
    }

    /**
     * Display user's report details
     */
    public function showReport($slug)
    {
        $report = PostReport::where('slug', $slug)->firstOrFail();
        
        // Only allow users to view their own reports
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $report->load(['post.user', 'post.media', 'reviewer']);

        return view('reports.show', compact('report'));
    }

    /**
     * Delete a specific report
     */
    public function deleteReport(PostReport $report)
    {
        // Only allow users to delete their own reports
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $report->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.report_deleted')
            ]);
        }

        return redirect()->back()->with('success', __('messages.report_deleted'));
    }

    /**
     * Delete all user's reports
     */
    public function deleteAllReports(Request $request)
    {
        PostReport::where('user_id', Auth::id())->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.all_reports_deleted')
            ]);
        }

        return redirect()->back()->with('success', __('messages.all_reports_deleted'));
    }

    /**
     * Display the admin reports dashboard
     */
    public function index(Request $request)
    {
        $query = PostReport::with(['post.user', 'reporter', 'post.media'])
            ->orderBy('status')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by reason
        if ($request->has('reason') && !empty($request->reason)) {
            $query->where('reason', $request->reason);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('reporter', function ($q) use ($search) {
                $q->where('username', 'LIKE', '%' . $search . '%')
                    ->orWhere('name', 'LIKE', '%' . $search . '%');
            });
        }

        $reports = $query->paginate(20);

        $stats = [
            'total' => PostReport::count(),
            'pending' => PostReport::where('status', PostReport::STATUS_PENDING)->count(),
            'accepted' => PostReport::where('status', PostReport::STATUS_ACCEPTED)->count(),
            'rejected' => PostReport::where('status', PostReport::STATUS_REJECTED)->count(),
        ];

        return view('admin.reports', compact('reports', 'stats'));
    }

    /**
     * Show a specific report with details
     */
    public function show(PostReport $report)
    {
        $report->load(['post.user', 'post.media', 'reporter.profile', 'reviewer']);

        return view('admin.report-detail', compact('report'));
    }

    /**
     * Accept a report and take action on the post
     */
    public function accept(Request $request, PostReport $report)
    {
        if ($report->status !== PostReport::STATUS_PENDING) {
            return redirect()->back()->with('error', __('messages.report_already_processed'));
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
            'action' => 'required|in:delete,hide,warning',
        ]);

        DB::beginTransaction();

        try {
            // Update report status
            $report->update([
                'status' => PostReport::STATUS_ACCEPTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_note' => $request->admin_note,
                'admin_action' => $request->action,
            ]);

            // Check if post was already trashed before we take action
            $post = $report->post;
            $wasAlreadyTrashed = $post && $post->trashed();
            $action = $request->action;

            if ($action === 'delete' && $post) {
                // Delete associated media files
                foreach ($post->media as $media) {
                    if ($media->media_path && \Storage::disk('public')->exists($media->media_path)) {
                        \Storage::disk('public')->delete($media->media_path);
                    }
                }
                $post->delete();
            } elseif ($action === 'hide') {
                $post->update(['is_private' => true]);
            }

            // Send notification to reporter
            $this->notifyReporter($report->reporter, $report, true, $action);

            // Notify post owner if post still exists or was just soft-deleted
            if ($post) {
                if (!$wasAlreadyTrashed || $action === 'warning') {
                    $this->notifyPostOwner($post->user, $report, $action);
                }
            }

            DB::commit();

            return redirect()->route('admin.reports')->with('success', __('messages.report_accepted_and_action_taken'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.error_processing_report'));
        }
    }

    /**
     * Reject a report
     */
    public function reject(Request $request, PostReport $report)
    {
        if ($report->status !== PostReport::STATUS_PENDING) {
            return redirect()->back()->with('error', __('messages.report_already_processed'));
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $report->update([
            'status' => PostReport::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        // Send notification to reporter
        $this->notifyReporter($report->reporter, $report, false);

        return redirect()->route('admin.reports')->with('success', __('messages.report_rejected'));
    }

    /**
     * Bulk accept reports
     */
    public function bulkAccept(Request $request)
    {
        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:post_reports,id',
            'action' => 'required|in:delete,hide,warning',
        ]);

        $reports = PostReport::whereIn('id', $request->report_ids)
            ->where('status', PostReport::STATUS_PENDING)
            ->get();

        foreach ($reports as $report) {
            $this->processReportAction($report, PostReport::STATUS_ACCEPTED, $request->action, $request->admin_note ?? null);
        }

        return redirect()->route('admin.reports')->with('success', __('messages.reports_processed'));
    }

    /**
     * Bulk reject reports
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:post_reports,id',
        ]);

        $reports = PostReport::whereIn('id', $request->report_ids)
            ->where('status', PostReport::STATUS_PENDING)
            ->get();

        foreach ($reports as $report) {
            $this->processReportAction($report, PostReport::STATUS_REJECTED, null, $request->admin_note ?? null);
        }

        return redirect()->route('admin.reports')->with('success', __('messages.reports_processed'));
    }

    /**
     * Process a single report action
     */
    private function processReportAction(PostReport $report, string $status, ?string $action = null, ?string $adminNote = null)
    {
        DB::beginTransaction();

        try {
            $report->update([
                'status' => $status,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
            ]);

            if ($status === PostReport::STATUS_ACCEPTED && $action && $report->post) {
                $post = $report->post;
                $wasAlreadyTrashed = $post->trashed();

                if ($action === 'delete') {
                    foreach ($post->media as $media) {
                        if ($media->media_path && \Storage::disk('public')->exists($media->media_path)) {
                            \Storage::disk('public')->delete($media->media_path);
                        }
                    }
                    $post->delete();
                } elseif ($action === 'hide') {
                    $post->update(['is_private' => true]);
                }

                $this->notifyReporter($report->reporter, $report, true, $action);

                // Notify post owner if post still exists or was just soft-deleted
                if ($post) {
                    if (!$wasAlreadyTrashed || $action === 'warning') {
                        $this->notifyPostOwner($post->user, $report, $action);
                    }
                }
            } elseif ($status === PostReport::STATUS_REJECTED) {
                $this->notifyReporter($report->reporter, $report, false);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing report: ' . $e->getMessage());
        }
    }

    /**
     * Notify all admins about a new report
     */
    private function notifyAdminsOfNewReport(PostReport $report)
    {
        $reporter = $report->reporter;
        $post = $report->post;

        // Only emit real-time socket event for online admins
        // Persistent notifications are removed as per user request
        app(\App\Services\SocketEmitService::class)->emit('admin', 'admin:report:new', [
            'report_id' => $report->id,
            'report_slug' => $report->slug,
            'reason' => $report->reason,
            'reporter_username' => $reporter->username,
            'post_id' => $post->id,
            'created_at' => $report->created_at->toISOString(),
            'pending_count' => PostReport::where('status', PostReport::STATUS_PENDING)->count()
        ]);
    }

    /**
     * Send notification to the reporter
     */
    private function notifyReporter(User $reporter, PostReport $report, bool $accepted, ?string $action = null)
    {
        $post = $report->post;

        if ($accepted) {
            $actionText = match ($action) {
                'delete' => __('messages.post_was_deleted'),
                'hide' => __('messages.post_was_hidden'),
                'warning' => __('messages.post_owner_was_warned'),
                default => __('messages.action_was_taken')
            };

            NotificationController::createNotification(
                $reporter->id,
                'report_accepted',
                [
                    'title' => __('messages.report_accepted'),
                    'message' => __('messages.your_report_was_accepted_and') . ' ' . $actionText,
                    'report_id' => $report->id,
                    'post_id' => $post?->id,
                    'reason' => $report->reason,
                ],
                $report
            );
        } else {
            NotificationController::createNotification(
                $reporter->id,
                'report_rejected',
                [
                    'title' => __('messages.report_rejected'),
                    'message' => __('messages.your_report_was_reviewed_but_not_accepted'),
                    'report_id' => $report->id,
                    'post_id' => $post?->id,
                    'reason' => $report->reason,
                    'admin_note' => $report->admin_note,
                ],
                $report
            );
        }
    }
    /**
     * Send notification to the post owner about moderation action
     */
    private function notifyPostOwner(User $owner, PostReport $report, string $action)
    {
        $messageKey = match ($action) {
            'delete' => 'messages.post_deleted_by_admin',
            'hide' => 'messages.post_hidden_by_admin',
            'warning' => 'messages.warning_issued_by_admin',
            default => 'messages.action_taken_on_post'
        };

        NotificationController::createNotification(
            $owner->id,
            'report_action_owner',
            [
                'title' => __('messages.moderation_action_taken'),
                'message' => __($messageKey, ['reason' => (__('admin.reasons.' . $report->reason) ?? $report->reason)]),
                'report_id' => $report->id,
                'action' => $action,
                'reason' => $report->reason,
                'admin_note' => $report->admin_note,
            ],
            $report
        );
    }
}
