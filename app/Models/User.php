<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'language',
        'password',
        'is_admin',
        'is_suspended',
        'verification_code',
        'verification_code_expires_at',
        'last_active',
        'inactive_reminder_sent_at',
        'is_online',
        'username_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'username_changed_at' => 'datetime',
            'last_active' => 'datetime',
            'inactive_reminder_sent_at' => 'datetime',
            'is_online' => 'boolean',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Validate username format before saving
        static::saving(function ($user) {
            if (empty($user->username)) {
                // Auto-generate username if empty
                $user->username = self::generateUniqueUsername($user->name);
            }

            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $user->username)) {
                throw new \InvalidArgumentException('Username can only contain letters, numbers, underscores, and hyphens.');
            }
        });

        // Auto-generate username when creating a new user
        static::creating(function ($user) {
            if (empty($user->username)) {
                $user->username = self::generateUniqueUsername($user->name);
            }
        });

        // Auto-create profile for new users
        static::created(function ($user) {
            // Use firstOrCreate to prevent duplicate profile creation
            $user->profile()->firstOrCreate([]);
        });
    }

    /**
     * Generate a unique username from name
     */
    protected static function generateUniqueUsername($name)
    {
        // Convert name to slug format
        $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $baseUsername = substr($baseUsername, 0, 20); // Limit base length
        
        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }
        
        $username = $baseUsername;
        $counter = 1;
        
        // Check if username exists and add numbers if needed
        while (self::where('username', $username)->exists()) {
            $username = substr($baseUsername, 0, 20 - strlen($counter)) . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Get the route key name for model binding.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
    }

    /**
     * Get the avatar URL - returns custom avatar or a generated letter avatar
     * matching the design system (gradient circle + first letter).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile && $this->profile->avatar) {
            return asset('storage/' . $this->profile->avatar);
        }

        return $this->buildLetterAvatarDataUri();
    }

    /**
     * Build an inline SVG data URI for a letter-based default avatar.
     * Picks one of 8 gradient palettes deterministically from the username.
     */
    protected function buildLetterAvatarDataUri(): string
    {
        $source = $this->username ?? $this->name ?? '?';
        $letter = mb_strtoupper(mb_substr($source, 0, 1, 'UTF-8'), 'UTF-8');
        if ($letter === '' || $letter === false) {
            $letter = '?';
        }

        $palettes = [
            ['#8b5cf6', '#6366f1'],
            ['#ec4899', '#f43f5e'],
            ['#f59e0b', '#ef4444'],
            ['#10b981', '#06b6d4'],
            ['#3b82f6', '#8b5cf6'],
            ['#f43f5e', '#f97316'],
            ['#06b6d4', '#3b82f6'],
            ['#84cc16', '#22c55e'],
        ];
        $idx = abs(crc32((string) $source)) % count($palettes);
        [$from, $to] = $palettes[$idx];

        $safeLetter = htmlspecialchars($letter, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
            . '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">'
            . '<stop offset="0%" stop-color="' . $from . '"/>'
            . '<stop offset="100%" stop-color="' . $to . '"/>'
            . '</linearGradient></defs>'
            . '<circle cx="50" cy="50" r="50" fill="url(#g)"/>'
            . '<text x="50" y="68" text-anchor="middle" '
            . 'font-family="Inter, -apple-system, system-ui, sans-serif" '
            . 'font-size="56" font-weight="700" fill="white">' . $safeLetter . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function follows()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_id');
    }

    /**
     * Get the users that this user is following
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id')
            ->withTimestamps();
    }

    /**
     * Get the users that follow this user
     */
    public function followersList()
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id')
            ->withTimestamps();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function isFollowing(User $user)
    {
        return $this->follows()->where('followed_id', $user->id)->exists();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function blockedUsers()
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function blockedBy()
    {
        return $this->hasMany(Block::class, 'blocked_id');
    }

    public function isBlocking(User $user)
    {
        return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
    }

    public function isBlockedBy(User $user)
    {
        return $this->blockedBy()->where('blocker_id', $user->id)->exists();
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function storyViews()
    {
        return $this->hasMany(StoryView::class);
    }

    public function storyReactions()
    {
        return $this->hasMany(StoryReaction::class);
    }

    /**
     * Get all conversations for this user (both direct and group)
     * Note: This is a custom query since conversations don't use a pivot table
     */
    public function conversations()
    {
        return Conversation::where('user1_id', $this->id)
            ->orWhere('user2_id', $this->id)
            ->orWhereIn('id', function($query) {
                $query->select('conversation_id')
                    ->from('conversations')
                    ->where('is_group', true)
                    ->whereIn('group_id', function($q) {
                        $q->select('group_id')
                            ->from('group_members')
                            ->where('user_id', $this->id);
                    });
            });
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function groupMemberships()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function activeStories()
    {
        return $this->stories()->active();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function commentLikes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function savedPosts()
    {
        return $this->hasMany(SavedPost::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function mutedConversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_mutes', 'user_id', 'conversation_id')->withTimestamps();
    }

    public function reportedPosts()
    {
        return $this->hasMany(PostReport::class, 'user_id');
    }

    public function reviewedReports()
    {
        return $this->hasMany(PostReport::class, 'reviewed_by');
    }

    /**
     * Get push subscriptions for this user.
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Generate a 6-digit verification code
     */
    public function generateVerificationCode()
    {
        $this->verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->verification_code_expires_at = now()->addMinutes(10); // Code expires in 10 minutes
        $this->save();

        return $this->verification_code;
    }

    /**
     * Verify the provided code
     */
    public function verifyCode($code)
    {
        if ($this->verification_code !== $code) {
            return false;
        }

        if (now()->isAfter($this->verification_code_expires_at)) {
            return false; // Code expired
        }

        // Mark email as verified
        $this->email_verified_at = now();
        $this->verification_code = null;
        $this->verification_code_expires_at = null;
        $this->save();

        return true;
    }

    /**
     * Check if verification code is expired
     */
    public function isVerificationCodeExpired()
    {
        return $this->verification_code_expires_at && now()->isAfter($this->verification_code_expires_at);
    }

    /**
     * Check if user is actually online (flag is set AND activity within last 5 minutes)
     */
    public function isActuallyOnline(): bool
    {
        if (!$this->is_online) {
            return false;
        }

        // If no last_active, but is_online is true, it might be a new or legacy user
        if (!$this->last_active) {
            return true;
        }

        // Consider stale after 5 minutes of inactivity
        return $this->last_active->diffInMinutes(now()) < 5;
    }

    /**
     * Check if user has other active sessions on different devices
     */
    public function hasOtherActiveSessions(): bool
    {
        // If the user isn't even marked as online via our socket heartbeat/presence,
        // they likely won't be able to approve a real-time challenge anyway.
        if (!$this->isActuallyOnline()) {
            return false;
        }

        $currentSessionId = request()->session()->getId();

        return \DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('id', '!=', $currentSessionId)
            ->where('last_activity', '>', now()->subMinutes(5)->timestamp)
            ->exists();
    }

    /**
     * Mark user as offline (called when they logout or close browser)
     */
    public function markAsOffline(): void
    {
        $this->update([
            'is_online' => false,
        ]);
    }

    /**
     * Username change cooldown period in seconds (3 days)
     */
    const USERNAME_COOLDOWN_SECONDS = 259200; // 3 * 24 * 60 * 60

    /**
     * Check if user can change their username
     * Admins can change anytime, regular users must wait 3 days
     */
    public function canChangeUsername(): bool
    {
        // Admins can change username anytime
        if ($this->is_admin) {
            return true;
        }

        // If never changed, can change
        if (is_null($this->username_changed_at)) {
            return true;
        }

        // Check if cooldown period has passed
        return now()->diffInSeconds($this->username_changed_at) >= self::USERNAME_COOLDOWN_SECONDS;
    }

    /**
     * Get the remaining time until username can be changed
     * Returns array with days, hours, minutes, seconds
     */
    public function getUsernameChangeCooldownRemaining(): array
    {
        if ($this->canChangeUsername()) {
            return [
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'total_seconds' => 0,
            ];
        }

        $elapsed = (int) now()->diffInSeconds($this->username_changed_at);
        $remaining = self::USERNAME_COOLDOWN_SECONDS - $elapsed;

        return [
            'days' => intdiv($remaining, 86400),
            'hours' => intdiv($remaining % 86400, 3600),
            'minutes' => intdiv($remaining % 3600, 60),
            'seconds' => $remaining % 60,
            'total_seconds' => $remaining,
        ];
    }

    /**
     * Get a human-readable cooldown message
     */
    public function getUsernameChangeCooldownMessage(): string
    {
        if ($this->canChangeUsername()) {
            return '';
        }

        $remaining = $this->getUsernameChangeCooldownRemaining();

        if ($remaining['days'] > 0) {
            return "You can change your username in {$remaining['days']} day" . ($remaining['days'] > 1 ? 's' : '');
        }

        if ($remaining['hours'] > 0) {
            return "You can change your username in {$remaining['hours']} hour" . ($remaining['hours'] > 1 ? 's' : '');
        }

        if ($remaining['minutes'] > 0) {
            return "You can change your username in {$remaining['minutes']} minute" . ($remaining['minutes'] > 1 ? 's' : '');
        }

        return "You can change your username in {$remaining['seconds']} second" . ($remaining['seconds'] > 1 ? 's' : '');
    }

    /**
     * Update username and record the change time
     */
    public function updateUsername(string $newUsername): void
    {
        $this->update([
            'username' => $newUsername,
            'username_changed_at' => now(),
        ]);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        $email = $this->email;
        \Illuminate\Support\Facades\Mail::send('emails.password-reset', ['resetUrl' => $resetUrl, 'url' => $resetUrl], function ($message) use ($email) {
            $message->to($email)
                    ->subject(config('app.name') . ' - Password Reset Request');
        });
    }

    /**
     * Generate a signed token for Socket.IO authentication
     */
    public function createSocketToken(): string
    {
        $secret = config('services.socket.secret');

        // Remove "base64:" prefix if present (Laravel style)
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $payload = [
            'user_id' => $this->id,
            'username' => $this->username,
            'is_admin' => (bool)$this->is_admin,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        return base64_encode($jsonPayload) . '.' . $signature;
    }
    /**
     * Update the last active timestamp
     */
    public function updateLastActive(): void
    {
        $this->update(['last_active' => now()]);
    }

    /**
     * Check if user is inactive for given days
     */
    public function isInactiveFor($days): bool
    {
        if (!$this->last_active) {
            return false; // Never active, don't consider inactive
        }

        return $this->last_active->diffInDays() >= $days;
    }

    public function socialGroupMemberships()
    {
        return $this->hasMany(SocialGroupMember::class);
    }

    /**
     * Life Chapters owned by this user, ordered for the profile timeline.
     */
    public function chapters()
    {
        return $this->hasMany(LifeChapter::class)
            ->orderBy('sort_order')
            ->orderByDesc('starts_on');
    }

    /**
     * Get the social groups the user is a member of.
     */
    public function socialGroups()
    {
        return $this->belongsToMany(SocialGroup::class, 'social_group_members')
                    ->withPivot(['role', 'status', 'notification_preference', 'is_anonymous_default', 'anonymous_username', 'muted_until'])
                    ->withTimestamps();
    }
}
