<?php

namespace App\Jobs;

use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use ZipArchive;

class GenerateDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public DataExportRequest $exportRequest
    ) {}

    public function handle(): void
    {
        $request = $this->exportRequest;
        $user = $request->user;

        // Set locale from user's language preference for translated email
        if ($user->language) {
            app()->setLocale($user->language);
        }

        $request->update(['status' => 'processing']);

        $tmpDir = storage_path('app/tmp/export_' . $request->id . '_' . time());
        mkdir($tmpDir, 0700, true);

        try {
            // Profile
            file_put_contents($tmpDir . '/profile.json', json_encode([
                'name'       => $user->name,
                'username'   => $user->username,
                'email'      => $user->email,
                'bio'        => $user->profile?->bio,
                'website'    => $user->profile?->website,
                'location'   => $user->profile?->location,
                'created_at' => $user->created_at->toIso8601String(),
            ], JSON_PRETTY_PRINT));

            // Posts
            $posts = $user->posts()->with('media')->get()->map(fn($p) => [
                'content'    => $p->content,
                'created_at' => $p->created_at->toIso8601String(),
                'media'      => $p->media->pluck('media_path'),
            ]);
            file_put_contents($tmpDir . '/posts.json', json_encode($posts, JSON_PRETTY_PRINT));

            // Comments
            $comments = $user->comments()->get()->map(fn($c) => [
                'content'    => $c->content,
                'post_id'    => $c->post_id,
                'created_at' => $c->created_at->toIso8601String(),
            ]);
            file_put_contents($tmpDir . '/comments.json', json_encode($comments, JSON_PRETTY_PRINT));

            // Followers / following
            $followers = $user->followers()->pluck('follower_id');
            $following = $user->follows()->pluck('followed_id');
            file_put_contents($tmpDir . '/social.json', json_encode([
                'followers_count' => $followers->count(),
                'following_count' => $following->count(),
            ], JSON_PRETTY_PRINT));

            // Messages (sent)
            $messages = $user->messages()->select(['content', 'created_at', 'conversation_id'])->get()->map(fn($m) => [
                'conversation_id' => $m->conversation_id,
                'content'         => $m->content,
                'sent_at'         => $m->created_at->toIso8601String(),
            ]);
            file_put_contents($tmpDir . '/messages_sent.json', json_encode($messages, JSON_PRETTY_PRINT));

            // Create ZIP
            $token   = bin2hex(random_bytes(32));
            $zipPath = storage_path('app/exports/' . $user->id . '_' . $token . '.zip');
            if (!is_dir(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0700, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('Cannot create ZIP');
            }
            foreach (glob($tmpDir . '/*.json') as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            $expiresAt = now()->addHours(48);

            $request->update([
                'status'         => 'ready',
                'download_token' => $token,
                'file_path'      => 'exports/' . $user->id . '_' . $token . '.zip',
                'expires_at'     => $expiresAt,
            ]);

            // Email the signed download link
            $downloadUrl = URL::temporarySignedRoute(
                'export.download',
                $expiresAt,
                ['token' => $token]
            );

            Mail::send('emails.data-export-ready', [
                'user'        => $user,
                'downloadUrl' => $downloadUrl,
                'expiresAt'   => $expiresAt->format('F j, Y g:i A'),
            ], function ($mail) use ($user) {
                $mail->to($user->email)->subject(
                    __('emails.data_export_subject', ['app' => config('app.name')])
                );
            });

        } finally {
            // Clean up temp directory
            $files = glob($tmpDir . '/*');
            if ($files) array_map('unlink', $files);
            @rmdir($tmpDir);
        }
    }
}
