<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ActivityController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display user's activity logs
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get filter parameters
        $action = $request->get('action', 'all');
        $days = (int) $request->get('days', 30);

        // Build query
        $query = ActivityLog::forUser($user->id)
            ->recent($days)
            ->orderBy('logged_at', 'desc');

        // Filter by action if specified
        if ($action !== 'all') {
            $query->action($action);
        }

        $activities = $query->paginate(20);

        // Get statistics
        $totalLogins = ActivityLog::forUser($user->id)->action('login')->count();
        $failedLogins = ActivityLog::where('action', 'failed_login')
            ->where('ip_address', request()->ip())
            ->recent($days)
            ->count();
        
        // Get active sessions - improved to handle NULL user_id in sessions table
        $activeSessionsList = $this->activityService->getActiveSessionsWithDetails($user->id);
        $activeSessions = $activeSessionsList->count();

        // Get available actions for filter
        $actions = [
            'all' => __('activity.all_activities'),
            'login' => __('activity.login'),
            'failed_login' => __('activity.failed_login'),
            'logout' => __('activity.logout'),
            'password_change' => __('activity.password_change'),
            'profile_update' => __('activity.profile_update'),
        ];

        return view('activity.index', compact('activities', 'actions', 'action', 'days', 'totalLogins', 'failedLogins', 'activeSessions', 'activeSessionsList'));
    }

    /**
     * Export activity logs to CSV
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $days = (int) $request->get('days', 30);
        $action = $request->get('action', 'all');

        // Build query
        $query = ActivityLog::forUser($user->id)
            ->recent($days)
            ->orderBy('logged_at', 'desc');

        // Filter by action if specified
        if ($action !== 'all') {
            $query->action($action);
        }

        $activities = $query->get();

        // CSV headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity-logs-' . now()->format('Y-m-d') . '.csv"',
        ];

        // Create callback for streaming CSV
        $callback = function() use ($activities) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 support in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($file, [
                'ID',
                'Date/Time',
                'Action',
                'IP Address',
                'Device Type',
                'Browser',
                'OS',
                'Country',
                'City',
                'ISP',
                'Coordinates',
            ]);

            // Data rows
            foreach ($activities as $activity) {
                fputcsv($file, [
                    $activity->id,
                    $activity->logged_at->format('Y-m-d H:i:s'),
                    $activity->action_name,
                    $activity->ip_address,
                    $activity->device_type,
                    $activity->browser,
                    $activity->os,
                    $activity->country ?? 'N/A',
                    $activity->city ?? 'N/A',
                    $activity->isp ?? 'N/A',
                    $activity->latitude && $activity->longitude ? "{$activity->latitude},{$activity->longitude}" : 'N/A',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Terminate all other sessions (logout from all devices)
     */
    public function terminateAllSessions(Request $request)
    {
        $user = auth()->user();
        $currentSessionId = $request->session()->getId();

        // Delete all sessions for this user except current session
        $deletedCount = \DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        // Log the action
        $this->activityService->logActivity('all_sessions_terminated', $user->id);

        return redirect()->back()->with('success', __('activity.all_sessions_terminated', ['count' => $deletedCount]));
    }

    /**
     * Terminate a specific session (logout from specific device)
     */
    public function terminateSession($sessionId)
    {
        $user = auth()->user();
        $currentSessionId = request()->session()->getId();

        // SAFETY: Don't allow terminating current session
        if ($sessionId === $currentSessionId) {
            return redirect()->back()->with('error', __('activity.cannot_terminate_current_session'));
        }

        // Check if session exists and belongs to this user
        $session = \DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return redirect()->back()->with('error', __('activity.session_not_found'));
        }

        // TERMINATE BY SESSION ID directly from sessions table
        $deleted = \DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted > 0) {
            // Log the termination attempt
            $this->activityService->logActivity('session_terminated', $user->id);
            return redirect()->back()->with('success', __('activity.session_terminated'));
        }

        // Delete failed
        return redirect()->back()->with('error', __('activity.session_not_found'));
    }

    /**
     * Clear all activity logs
     */
    public function clearOld(Request $request)
    {
        $user = auth()->user();
        $ipAddress = request()->ip();
        $sessionId = $request->session()->getId();

        // Delete user's activity logs
        $deleted = ActivityLog::where('user_id', $user->id)->delete();

        // Also delete ALL failed login attempts (not just current IP)
        // Failed logins don't have user_id, so we delete all of them
        $deletedFailed = ActivityLog::where('action', 'failed_login')->delete();

        $totalDeleted = $deleted + $deletedFailed;

        return redirect()->back()->with('success', __('activity.logs_cleared', ['count' => $totalDeleted]));
    }

    /**
     * Delete a specific activity log
     */
    public function deleteLog($id)
    {
        $user = auth()->user();
        $ipAddress = request()->ip();
        $sessionId = request()->session()->getId();

        // Try to find activity log by user_id
        $activity = ActivityLog::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        // If not found, check if it's a failed login (any failed login can be deleted)
        if (!$activity) {
            $activity = ActivityLog::where('id', $id)
                ->where('action', 'failed_login')
                ->first();
        }

        if (!$activity) {
            return redirect()->back()->with('error', __('activity.log_not_found'));
        }

        $activity->delete();

        return redirect()->back()->with('success', __('activity.log_deleted'));
    }
}
