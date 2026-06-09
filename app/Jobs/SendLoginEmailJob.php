<?php

namespace App\Jobs;

use App\Mail\LoginSecurityAlert;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLoginEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    /**
     * The number of times the queued job may be attempted before failing.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->userId = $user->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                return;
            }

            // Don't send if email is not verified
            if (!$user->hasVerifiedEmail()) {
                return;
            }

            // Get the most recent login activity for this user
            $activity = ActivityLog::where('user_id', $this->userId)
                ->where('action', 'login')
                ->latest()
                ->first();

            if (!$activity) {
                return;
            }

            // Check if this is a suspicious login
            $isSuspicious = $activity->is_suspicious;

            // Only send email if it's suspicious
            if (!$isSuspicious) {
                return;
            }

            // Send email in the user's language (locale() on mailable covers subject + view)
            $mailable = new LoginSecurityAlert($activity);
            if ($user->language) {
                $mailable->locale($user->language);
            }
            Mail::to($user->email)->send($mailable);

        } catch (\Exception $e) {
            \Log::error('Failed to send login email to user ' . $this->userId . ': ' . $e->getMessage());
            throw $e; // Will trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('SendLoginEmailJob failed after ' . $this->tries . ' attempts for user ' . $this->userId);
        // Don't rethrow - just log the failure
    }
}
