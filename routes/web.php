<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\SuspiciousLoginController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\GlobalChatController;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

// Define rate limiters for production
RateLimiter::for('auth', function ($request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('posts', function ($request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('comments', function ($request) {
    return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('verification', function ($request) {
    return Limit::perHour(10)->by($request->user()?->id ?: $request->ip())
        ->response(function (Request $request, array $headers) {
            $message = 'Too many verification attempts. Please try again in an hour.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429, $headers);
            }
            return back()->withErrors(['code' => $message])->withInput();
        });
});

// Explicit route binding for Story model to use slug
Route::bind('story', function ($value) {
    return \App\Models\Story::where('slug', $value)->firstOrFail();
});

Route::get('/dashboard', function () {
    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');

// Language switch route (placed early to avoid conflicts)
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch')->middleware('web');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('terms');
})->name('terms');

Route::get('/cookies', function () {
    return view('cookies');
})->name('cookies');

Route::middleware('guest')->group(function () {
    Route::get('login', function () {
        // Aggressive cleanup for unverified users
        \App\Models\User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();
            
        return view('auth.login');
    })->name('login.view');

    Route::post('login', [LoginController::class, 'store'])->name('login')->middleware('throttle:auth');
    
    // Suspicious Login Routes
    Route::get('login/suspicious/{uuid}', [SuspiciousLoginController::class, 'show'])->name('login.suspicious.view');
    Route::post('login/suspicious/{uuid}/verify', [SuspiciousLoginController::class, 'verify'])->name('login.suspicious.verify');
    Route::post('login/suspicious/{uuid}/resend', [SuspiciousLoginController::class, 'resend'])->name('login.suspicious.resend');
    
    Route::get('suspended', function () {
        return view('auth.suspended');
    })->name('auth.suspended');

    Route::get('register', function () {
        // Aggressive cleanup for unverified users
        \App\Models\User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();
            
        return view('auth.register');
    })->name('register.view');

    Route::post('register', [RegisterController::class, 'store'])->name('register')->middleware('throttle:auth');

    // Password Reset Routes
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

// Login Challenge Routes (Publicly accessible to allow session overrides)
Route::get('login/challenge/{uuid}', [LoginController::class, 'challengeView'])->name('login.challenge');
Route::get('login/challenge/{uuid}/status', [LoginController::class, 'challengeStatus'])->name('login.challenge.status');
Route::post('login/challenge/{uuid}/email', [LoginController::class, 'sendEmailChallenge'])->name('login.challenge.email');
Route::post('login/challenge/{uuid}/verify', [LoginController::class, 'verifyEmailChallenge'])->name('login.challenge.verify');

// Google OAuth Routes (Out of guest to allow session flushing)
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);



Route::middleware(['auth', 'suspended'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // Login Challenge Routes (Authenticated side - Approver)
    Route::post('login/challenge/{uuid}/approve', [LoginController::class, 'approveChallenge'])->name('login.challenge.approve');
    Route::post('login/challenge/{uuid}/deny', [LoginController::class, 'denyChallenge'])->name('login.challenge.deny');
});

// Email verification routes (6-digit code system)

Route::get('/email/verify', function () {
    // Allow access for both logged-in users and users with pending verification
    if (auth()->check()) {
        return view('auth.verify-email');
    }

    // Check if there's a pending verification user in session
    $pendingUserId = session('pending_verification_user_id');
    if ($pendingUserId) {
        $pendingUser = \App\Models\User::find($pendingUserId);
        if ($pendingUser && !$pendingUser->hasVerifiedEmail()) {
            // Temporarily log in the user for verification
            auth()->login($pendingUser);
            return view('auth.verify-email');
        }
    }

    return redirect('/')->with('error', 'Please register first.');
})->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    $user = $request->user();

    // If no authenticated user, check for pending verification user
    if (!$user) {
        $pendingUserId = session('pending_verification_user_id');
        if ($pendingUserId) {
            $user = \App\Models\User::find($pendingUserId);
        }
    }

    // Check if user is already verified
    if ($user && $user->hasVerifiedEmail() && !session('auth.suspicious')) {
        // User is already verified, check if they need to set password
        if ($user->password === null) {
            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('password.set-password')], 200);
            }
            return redirect()->route('password.set-password')->with('message', __('messages.please_set_password'));
        }
        
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Your account is already verified!'], 400);
        }
        return back()->with('error', 'Your account is already verified!');
    }

    if ($user && (!$user->hasVerifiedEmail() || session('auth.suspicious'))) {
        // Generate and send new verification code
        $verificationCode = $user->generateVerificationCode();

        // Send simple verification code via email
        \Illuminate\Support\Facades\Mail::raw(
            "Welcome to " . config('app.name') . "!\n\n" .
            "Your verification code is: {$verificationCode}\n\n" .
            "Please enter this code to verify your account.",
            function ($message) use ($user) {
                $message->to($user->email)
                        ->subject(config('app.name') . ' - Verification Code');
            }
        );

        // Return JSON response for AJAX
        if ($request->expectsJson()) {
            return response()->json(['message' => __('messages.verification_code_sent')]);
        }

        return back()->with('message', __('messages.verification_code_sent'));
    }

    // Return JSON response for AJAX
    if ($request->expectsJson()) {
        return response()->json(['error' => 'Unable to send verification email.'], 422);
    }

    return back()->with('error', 'Unable to send verification email.');
})->middleware('throttle:verification')->name('verification.send');

Route::post('/email/verify-code', function (Request $request) {
    $request->validate([
        'code' => 'required|string|size:6|regex:/^\d{6}$/',
    ]);

    $user = $request->user();

    // If no authenticated user, check for pending verification user
    if (!$user) {
        $pendingUserId = session('pending_verification_user_id');
        if ($pendingUserId) {
            $user = \App\Models\User::find($pendingUserId);
        }
    }

    if (!$user) {
        return redirect()->route('login')->withErrors(['code' => __('messages.user_not_found')]);
    }

    if ($user->verifyCode($request->code)) {
        // Clear pending verification session
        session()->forget('pending_verification_user_id');
        
        // Clear suspicious login flag if it exists
        session()->forget('auth.suspicious');

        // Ensure user is logged in
        if (!auth()->check()) {
            auth()->login($user);
        }

        // Regenerate session for security
        request()->session()->regenerate();

        // If user has no password (Google OAuth), redirect to set password page
        if ($user->password === null) {
            session()->save();
            return redirect()->route('password.set-password')->with('message', __('messages.please_set_password'));
        }

        session()->save();
        return redirect('/')->with('message', __('messages.email_verified_success'));
    }

    return back()->withErrors(['code' => __('messages.invalid_verification_code')]);
})->middleware('throttle:verification')->name('verification.verify-code');

Route::get('/', function () {
    $isAuthed = auth()->check();

    // If user is not authenticated, show the landing page
    if (!$isAuthed) {
        return view('home');
    }

    $user = auth()->user();

    // If authenticated and not verified (or suspicious login), show verification notice
    if (!$user->hasVerifiedEmail() || (session('auth.suspicious') && !$user->is_admin)) {
        return redirect()->route('verification.notice');
    }

    // If user has no password (Google OAuth), redirect to set password page
    if ($user->password === null) {
        return redirect()->route('password.set-password')->with('message', __('messages.please_set_password'));
    }

    // Show posts feed for authenticated and verified users
    return app(\App\Http\Controllers\PostController::class)->index(request());
})->name('home');

// Test route for debugging
Route::get('/user/test-route', function() {
    return response()->json(['status' => 'ok', 'message' => 'Route works']);
});

Route::middleware(['auth', 'suspended', 'verified', 'password.set'])->group(function () {
    Route::get('/posts/load-more', [PostController::class, 'loadMore'])->name('posts.load-more');
    Route::get('/feed-preview', [PostController::class, 'indexPreview'])->name('feed.preview');
    Route::resource('posts', PostController::class, [
        'parameters' => ['posts' => 'post:slug'],
        'only' => ['index', 'show', 'store', 'update', 'destroy', 'create', 'edit']
    ])->middleware('throttle:posts');
    Route::post('/posts/{post}/like', [PostController::class, 'like'])->name('posts.like')->where('post', '[a-zA-Z0-9]{24}')->middleware('throttle:posts');
    Route::post('/posts/{post}/react', [PostController::class, 'react'])->name('posts.react')->where('post', '[a-zA-Z0-9]{24}')->middleware('throttle:posts');
    Route::delete('/posts/{post}/react', [PostController::class, 'removeReaction'])->name('posts.remove-reaction')->where('post', '[a-zA-Z0-9]{24}')->middleware('throttle:posts');
    Route::get('/posts/{post}/reactions', [PostController::class, 'getReactions'])->name('posts.reactions')->where('post', '[a-zA-Z0-9]{24}');
    Route::post('/posts/{post}/save', [PostController::class, 'save'])->name('posts.save')->where('post', '[a-zA-Z0-9]{24}')->middleware('throttle:posts');
    Route::get('/posts/{post}/likers', [PostController::class, 'getLikers'])->name('posts.likers')->where('post', '[a-zA-Z0-9]{24}');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store')->middleware('throttle:comments');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->name('comments.like');
    Route::get('/stories', [App\Http\Controllers\StoryController::class, 'index'])->name('stories.index');
    Route::get('/stories/create', [App\Http\Controllers\StoryController::class, 'create'])->name('stories.create');
    Route::post('/stories', [App\Http\Controllers\StoryController::class, 'store'])->name('stories.store')->middleware('throttle:posts');
    Route::get('/stories/{user}/{story}', [App\Http\Controllers\StoryController::class, 'show'])->name('stories.show')->where('user', '[a-zA-Z0-9_-]+');
    Route::get('/stories/{user}/{story}/viewers', [App\Http\Controllers\StoryController::class, 'viewers'])->name('stories.viewers')->where('user', '[a-zA-Z0-9_-]+');
    Route::post('/stories/{user}/{story}/react', [App\Http\Controllers\StoryController::class, 'react'])->name('stories.react')->middleware('throttle:posts');
    Route::delete('/stories/{user}/{story}/react', [App\Http\Controllers\StoryController::class, 'removeReaction'])->name('stories.remove-reaction');
    Route::get('/stories/{user}/{story}/reactions', [App\Http\Controllers\StoryController::class, 'getReactions'])->name('stories.reactions');
    Route::get('/stories/{user}/{story}/check-reaction', [App\Http\Controllers\StoryController::class, 'checkReaction'])->name('stories.check-reaction');
    Route::delete('/stories/{user}/{story}', [App\Http\Controllers\StoryController::class, 'destroy'])->name('stories.destroy');

    Route::get('/profile', function () { return redirect()->route('users.show', auth()->user()); })->name('profile');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/memories', [UserController::class, 'memories'])->name('users.memories');
    Route::get('/users/{user}/followers', [UserController::class, 'followers'])->name('users.followers');
    Route::get('/users/{user}/following', [UserController::class, 'following'])->name('users.following');
    Route::get('/users/{user}/blocked', [UserController::class, 'blocked'])->name('users.blocked');
    Route::get('/saved-posts', [UserController::class, 'savedPosts'])->name('users.saved-posts');
    Route::post('/users/{user}/follow', [UserController::class, 'follow'])->name('users.follow');
    Route::post('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::get('/users/{user}/block', function () {
        abort(404);
    });
    Route::get('/explore', [UserController::class, 'explore'])->name('explore');
    Route::get('/search', [UserController::class, 'searchPage'])->name('search');
    Route::get('/users/{user}/edit', [UserController::class, 'editProfile'])->name('profile.edit')->where('user', '[a-zA-Z0-9_\- ]+');
    Route::post('/profile/{user}/update', [UserController::class, 'updateProfile'])->name('profile.update')->where('user', '[a-zA-Z0-9_\- ]+');
    Route::delete('/profile/delete-avatar', [UserController::class, 'deleteAvatar'])->name('profile.delete-avatar');
    Route::delete('/profile/delete-cover', [UserController::class, 'deleteCoverImage'])->name('profile.delete-cover');
    Route::delete('/profile/delete-account', [UserController::class, 'deleteAccount'])->name('profile.delete-account');
    
    // QR Code Profile Sharing routes
    Route::get('/users/{user}/qr-code', [UserController::class, 'generateQrCode'])->name('users.qr-code');
    Route::get('/users/{user}/qr-code/download', [UserController::class, 'downloadQrCode'])->name('users.qr-code.download');

    // Pinned Posts routes
    Route::post('/users/{user}/posts/{post}/pin', [UserController::class, 'pinPost'])->name('users.posts.pin')->where('post', '[0-9]+');
    Route::post('/users/{user}/posts/{post}/unpin', [UserController::class, 'unpinPost'])->name('users.posts.unpin')->where('post', '[0-9]+');
    Route::post('/users/{user}/pinned-posts/reorder', [UserController::class, 'reorderPinnedPosts'])->name('users.pinned-posts.reorder');
    Route::get('/api/users/following/suggestions', [UserController::class, 'followingSuggestions'])->name('api.users.following.suggestions');

    // Right-sidebar (feed) dynamic data endpoints
    Route::get('/api/sidebar/suggestions', [App\Http\Controllers\Api\SidebarController::class, 'suggestions'])->name('api.sidebar.suggestions');
    Route::get('/api/sidebar/top-communities', [App\Http\Controllers\Api\SidebarController::class, 'topCommunities'])->name('api.sidebar.top-communities');
    Route::get('/api/sidebar/trending-hashtags', [App\Http\Controllers\Api\SidebarController::class, 'trendingHashtags'])->name('api.sidebar.trending-hashtags');

    // Pulse — daily community prompt
    Route::get('/pulse', [App\Http\Controllers\PulseController::class, 'index'])->name('pulse.index');
    Route::post('/pulse/answer', [App\Http\Controllers\PulseController::class, 'answer'])->name('pulse.answer')->middleware('throttle:posts');
    Route::delete('/pulse/answer', [App\Http\Controllers\PulseController::class, 'deleteAnswer'])->name('pulse.answer.delete');
    Route::post('/pulse/answer/like', [App\Http\Controllers\PulseController::class, 'toggleLike'])->name('pulse.answer.like');
    Route::get('/api/pulse/today', [App\Http\Controllers\PulseController::class, 'today'])->name('api.pulse.today');
    Route::get('/api/pulse/answers', [App\Http\Controllers\PulseController::class, 'answers'])->name('api.pulse.answers');

    // Pulse — weekly memory prompt
    Route::get('/api/pulse/memory', [App\Http\Controllers\PulseController::class, 'memory'])->name('api.pulse.memory');
    Route::post('/pulse/memory/answer', [App\Http\Controllers\PulseController::class, 'answerMemory'])->name('pulse.memory.answer')->middleware('throttle:posts');
    Route::delete('/pulse/memory/answer', [App\Http\Controllers\PulseController::class, 'deleteMemoryAnswer'])->name('pulse.memory.answer.delete');
    Route::get('/memories', [App\Http\Controllers\PulseController::class, 'memoriesIndex'])->name('memories.index');
    Route::get('/pulse/memories', [App\Http\Controllers\PulseController::class, 'memoryAnswers'])->name('pulse.memories');

    // Life Chapters — user-owned life eras that auto-tag posts and pulse answers
    Route::get('/api/users/{username}/chapters', [App\Http\Controllers\LifeChapterController::class, 'index'])->name('api.chapters.index');
    Route::post('/api/chapters', [App\Http\Controllers\LifeChapterController::class, 'store'])->name('api.chapters.store');
    Route::put('/api/chapters/{chapter}', [App\Http\Controllers\LifeChapterController::class, 'update'])->name('api.chapters.update');
    Route::delete('/api/chapters/{chapter}', [App\Http\Controllers\LifeChapterController::class, 'destroy'])->name('api.chapters.destroy');
    Route::get('/api/chapters/{chapter}/posts', [App\Http\Controllers\LifeChapterController::class, 'posts'])->name('api.chapters.posts');

    // Life Chapter dedicated page on the profile
    Route::get('/users/{user}/chapters/{chapter}', [UserController::class, 'chapterPage'])
        ->name('users.chapter')
        ->where('chapter', '[0-9]+');
    
    // Set password for Google OAuth users
    Route::get('/set-password', [App\Http\Controllers\Auth\PasswordController::class, 'showSetPassword'])->name('password.set-password');
    Route::post('/set-password', [App\Http\Controllers\Auth\PasswordController::class, 'setPassword'])->name('password.set-password.store');
    
    Route::get('/password/change', function () { return view('auth.password-change'); })->name('password.change.view');
    Route::post('/password/change', [PasswordController::class, 'change'])->name('password.change');

    // AI Assistant routes
    Route::get('/ai', [AiController::class, 'index'])->name('ai.index');
    Route::post('/ai/chat', [AiController::class, 'chat'])->name('ai.chat');

    // Notifications routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-as-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']); // Alias
        Route::post('/mark-as-read/{id}', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read-api-compat'); // Compatibility
        Route::post('/mark-conversation-read/{conversationId}', [App\Http\Controllers\NotificationController::class, 'markConversationNotificationsAsRead'])->name('mark-conversation-read');
        Route::delete('/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [App\Http\Controllers\NotificationController::class, 'clearAll'])->name('clear-all');
    });

    // Chat routes
    Route::post('/chat/confirm-delivery', [App\Http\Controllers\ChatController::class, 'confirmDelivery'])->name('chat.message-delivered');
    Route::post('/chat/mark-delivered-all', [App\Http\Controllers\ChatController::class, 'markAllAsDelivered'])->name('chat.mark-delivered-all');
    // Global Chat
    Route::get('/global-chat', [GlobalChatController::class, 'index'])->name('global-chat.index');
    Route::post('/global-chat/send', [GlobalChatController::class, 'sendMessage'])->name('global-chat.send');
    Route::post('/global-chat/react/{message}', [GlobalChatController::class, 'react'])->name('global-chat.react');
    Route::get('/global-chat/message/{message}/reactions', [GlobalChatController::class, 'getReactions'])->name('global-chat.message.reactions');
    Route::delete('/global-chat/message/{message}', [GlobalChatController::class, 'destroy'])->name('global-chat.message.destroy');
    Route::post('/global-chat/admin-clear-all', [GlobalChatController::class, 'clear'])->name('global-chat.clear');

    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/api/conversations', [App\Http\Controllers\ChatController::class, 'getConversations'])->name('api.conversations');
    Route::get('/api/search-users', [App\Http\Controllers\UserController::class, 'apiSearch'])->name('api.search-users');
    Route::get('/api/user/{user}/username', [App\Http\Controllers\UserController::class, 'getUsername'])->name('api.user.username');
    Route::get('/user/{user}/online-status', [App\Http\Controllers\UserController::class, 'getOnlineStatus'])->name('user.get-online-status');
    Route::get('/users/online-status/bulk', [App\Http\Controllers\UserController::class, 'getBulkOnlineStatus'])->name('users.online-status.bulk');

    // 1. Specific Chat Actions (Must come before catch-all slug)
    Route::get('/chat/groups/create', [App\Http\Controllers\GroupController::class, 'create'])->name('groups.create');
    Route::post('/chat/groups/store', [App\Http\Controllers\GroupController::class, 'store'])->name('groups.store');
    Route::get('/chat/start/{userId}', [App\Http\Controllers\ChatController::class, 'startConversation'])->name('chat.start');
    Route::get('/chat/conversations', [App\Http\Controllers\ChatController::class, 'getConversations'])->name('chat.conversations');
    
    // 2. Specific Message/Info Routes
    Route::get('/chat/message/{message}/info', [App\Http\Controllers\ChatController::class, 'getMessageInfo'])->name('chat.message.info');
    Route::get('/chat/message/{message}/reactions', [App\Http\Controllers\ChatController::class, 'getMessageReactions'])->name('chat.message.reactions');
    Route::post('/chat/message/{message}/react', [App\Http\Controllers\ChatController::class, 'toggleReaction'])->name('chat.message.react');
    Route::delete('/chat/message/{message}', [App\Http\Controllers\ChatController::class, 'destroy'])->name('chat.destroy');

    // 3. Group Join (Safe Prefix)
    Route::get('/chat/join/{invite_link}', [App\Http\Controllers\GroupController::class, 'join'])->name('groups.join');

    // 4. Conversation-Specific Settings (Must come before catch-all slug)
    Route::get('/chat/{slug}/edit', [App\Http\Controllers\GroupController::class, 'show'])->name('groups.show');
    Route::patch('/chat/{group}/update', [App\Http\Controllers\GroupController::class, 'update'])->name('groups.update');
    Route::post('/chat/{group}/members/add', [App\Http\Controllers\GroupController::class, 'addMember'])->name('groups.members.add');
    Route::delete('/chat/{group}/members/{userId}/remove', [App\Http\Controllers\GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::patch('/chat/{group}/members/{userId}/role', [App\Http\Controllers\GroupController::class, 'changeRole'])->name('groups.members.role');
    Route::get('/chat/{conversation}/messages', [App\Http\Controllers\ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/{conversation}/read', [App\Http\Controllers\ChatController::class, 'markAsRead'])->name('chat.mark-read');
    Route::post('/chat/{conversation}/typing', [App\Http\Controllers\ChatController::class, 'sendTypingIndicator'])->name('chat.typing');
    Route::post('/chat/{conversation}/mute', [App\Http\Controllers\ChatController::class, 'toggleMute'])->name('chat.mute');
    Route::delete('/chat/{conversation}/clear', [App\Http\Controllers\ChatController::class, 'clearChat'])->name('chat.clear');
    Route::post('/chat/{group}/leave', [App\Http\Controllers\GroupController::class, 'leave'])->name('groups.leave');
    Route::delete('/chat/{group}/delete', [App\Http\Controllers\GroupController::class, 'destroy'])->name('groups.destroy');

    // 5. Generic Conversation Catch-all (Must be last)
    Route::get('/chat/{conversation}', [App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}', [App\Http\Controllers\ChatController::class, 'store'])->name('chat.store');
    Route::delete('/chat/{conversation}', [App\Http\Controllers\ChatController::class, 'deleteConversation'])->name('chat.delete-conversation');

    // Social Groups (Communities) - Modern Facebook Style
    Route::prefix('communities')->name('communities.')->group(function () {
        Route::get('/', [App\Http\Controllers\SocialGroupController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\SocialGroupController::class, 'store'])->name('store');
        
        Route::prefix('/{slug}')->group(function () {
            // Public Pages
            Route::get('/', [App\Http\Controllers\SocialGroupController::class, 'show'])->name('show');
            Route::get('/about', [App\Http\Controllers\SocialGroupController::class, 'about'])->name('about');
            Route::get('/members', [App\Http\Controllers\SocialGroupController::class, 'members'])->name('members');
            
            // Actions
            Route::post('/join', [App\Http\Controllers\SocialGroupController::class, 'join'])->name('join');
            Route::post('/leave', [App\Http\Controllers\SocialGroupController::class, 'leave'])->name('leave');
            Route::get('/settings', [App\Http\Controllers\SocialGroupController::class, 'settings'])->name('settings');
            Route::patch('/preferences', [App\Http\Controllers\SocialGroupController::class, 'updateUserPreferences'])->name('preferences');
            Route::post('/invite/{userId}', [App\Http\Controllers\SocialGroupInviteController::class, 'invite'])->name('invite');

            // Admin/Management - Dedicated Pages
            Route::prefix('/admin')->name('admin.')->group(function () {
                Route::get('/', [App\Http\Controllers\SocialGroupAdminController::class, 'index'])->name('index');
                Route::get('/settings', [App\Http\Controllers\SocialGroupAdminController::class, 'settings'])->name('settings');
                Route::patch('/settings', [App\Http\Controllers\SocialGroupAdminController::class, 'updateSettings'])->name('settings.update');
                Route::delete('/delete', [App\Http\Controllers\SocialGroupAdminController::class, 'destroy'])->name('delete');
                
                Route::get('/members', [App\Http\Controllers\SocialGroupAdminController::class, 'membersList'])->name('members');
                
                // Moderation Queues
                Route::get('/moderation/posts', [App\Http\Controllers\SocialGroupAdminController::class, 'pendingPosts'])->name('moderation.posts');
                Route::get('/moderation/members', [App\Http\Controllers\SocialGroupAdminController::class, 'pendingMembers'])->name('moderation.members');
                
                Route::post('/members/{userId}/approve', [App\Http\Controllers\SocialGroupAdminController::class, 'approveMember'])->name('members.approve');
                Route::post('/members/{userId}/reject', [App\Http\Controllers\SocialGroupAdminController::class, 'rejectMember'])->name('members.reject');
                Route::post('/members/{userId}/mute', [App\Http\Controllers\SocialGroupAdminController::class, 'muteMember'])->name('members.mute');
                Route::post('/members/{userId}/remove', [App\Http\Controllers\SocialGroupAdminController::class, 'removeMember'])->name('members.remove');
                Route::post('/members/{userId}/role', [App\Http\Controllers\SocialGroupAdminController::class, 'updateMemberRole'])->name('members.role.update');
                
                Route::post('/posts/{postId}/approve', [App\Http\Controllers\SocialGroupAdminController::class, 'approvePost'])->name('posts.approve');
                Route::post('/posts/{postId}/reject', [App\Http\Controllers\SocialGroupAdminController::class, 'rejectPost'])->name('posts.reject');
                
                // Meta Management
                Route::get('/rules', [App\Http\Controllers\SocialGroupAdminController::class, 'rules'])->name('rules');
                Route::post('/rules', [App\Http\Controllers\SocialGroupAdminController::class, 'addRule'])->name('rules.add');
                Route::put('/rules/{id}', [App\Http\Controllers\SocialGroupAdminController::class, 'apiUpdateRule'])->name('rules.update');
                Route::delete('/rules/{id}', [App\Http\Controllers\SocialGroupAdminController::class, 'deleteRule'])->name('rules.delete');

                Route::get('/topics', [App\Http\Controllers\SocialGroupAdminController::class, 'topics'])->name('topics');
                Route::post('/topics', [App\Http\Controllers\SocialGroupAdminController::class, 'addTopic'])->name('topics.add');
                Route::put('/topics/{id}', [App\Http\Controllers\SocialGroupAdminController::class, 'apiUpdateTopic'])->name('topics.update');
                Route::delete('/topics/{id}', [App\Http\Controllers\SocialGroupAdminController::class, 'deleteTopic'])->name('topics.delete');

                Route::get('/badges', [App\Http\Controllers\SocialGroupAdminController::class, 'badges'])->name('badges');
                Route::post('/badges', [App\Http\Controllers\SocialGroupAdminController::class, 'addBadge'])->name('badges.add');
                Route::put('/badges/{id}', [App\Http\Controllers\SocialGroupAdminController::class, 'apiUpdateBadge'])->name('badges.update');
                Route::delete('/badges/{id}', [App\Http\Controllers\SocialGroupAdminController::class, 'deleteBadge'])->name('badges.delete');
                Route::post('/members/{userId}/badges/toggle', [App\Http\Controllers\SocialGroupAdminController::class, 'toggleBadge'])->name('badges.toggle');

                // Reports management
                Route::get('/reports', [App\Http\Controllers\SocialGroupAdminController::class, 'reportsPage'])->name('reports');
                Route::get('/reports/data', [App\Http\Controllers\SocialGroupAdminController::class, 'reports'])->name('reports.data');
                Route::post('/reports/{id}/resolve', [App\Http\Controllers\SocialGroupAdminController::class, 'resolveReport'])->name('reports.resolve');
            });
        });
    });



    // Admin routes (protected by admin middleware)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [App\Http\Controllers\AdminController::class, 'showUser'])->name('users.show');
        Route::get('/users/{user}/edit', [App\Http\Controllers\AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\AdminController::class, 'deleteUser'])->name('users.delete');
        Route::get('/posts', [App\Http\Controllers\AdminController::class, 'posts'])->name('posts');
        Route::delete('/posts/{post}', [App\Http\Controllers\AdminController::class, 'deletePost'])->name('posts.delete')->where('post', '[a-zA-Z0-9]{24}');
        Route::get('/comments', [App\Http\Controllers\AdminController::class, 'comments'])->name('comments');
        Route::delete('/comments/{comment}', [App\Http\Controllers\AdminController::class, 'deleteComment'])->name('comments.delete');
        Route::get('/stories', [App\Http\Controllers\AdminController::class, 'stories'])->name('stories');
        Route::delete('/stories/{story}', [App\Http\Controllers\AdminController::class, 'deleteStory'])->name('stories.delete');
        Route::post('/create-admin', [App\Http\Controllers\AdminController::class, 'createAdminAccount'])->name('create-admin');
        
        // Report management routes
        Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports');
        Route::get('/reports/{report}', [App\Http\Controllers\ReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/accept', [App\Http\Controllers\ReportController::class, 'accept'])->name('reports.accept');
        Route::post('/reports/{report}/reject', [App\Http\Controllers\ReportController::class, 'reject'])->name('reports.reject');
        Route::post('/reports/bulk-accept', [App\Http\Controllers\ReportController::class, 'bulkAccept'])->name('reports.bulk-accept');
        Route::post('/reports/bulk-reject', [App\Http\Controllers\ReportController::class, 'bulkReject'])->name('reports.bulk-reject');
    });

    // User report routes (authenticated users can report posts)
    Route::get('/posts/{post}/report', [App\Http\Controllers\ReportController::class, 'create'])->name('posts.report.create')->where('post', '[a-zA-Z0-9]{24}');
    Route::post('/posts/{post}/report', [App\Http\Controllers\ReportController::class, 'store'])->name('posts.report.store')->where('post', '[a-zA-Z0-9]{24}');

    // Hashtag routes
    Route::get('/hashtags', [App\Http\Controllers\HashtagController::class, 'index'])->name('hashtags.index');
    Route::get('/api/hashtags/suggestions', [App\Http\Controllers\HashtagController::class, 'suggestions'])->name('api.hashtags.suggestions');
    Route::get('/hashtags/{slug}', [App\Http\Controllers\HashtagController::class, 'show'])->name('hashtags.show')->where('slug', '(?!api)[a-zA-Z0-9_-]+');

    // User reports management (authenticated users can view their reports)
    Route::middleware(['auth', 'verified', 'password.set'])->group(function () {
        Route::get('/my-reports', [App\Http\Controllers\ReportController::class, 'myReports'])->name('reports.my-reports');
        Route::get('/my-reports/{slug}', [App\Http\Controllers\ReportController::class, 'showReport'])->name('reports.show-user');
        Route::delete('/reports/{report}', [App\Http\Controllers\ReportController::class, 'deleteReport'])->name('reports.delete');
        Route::delete('/my-reports/delete-all', [App\Http\Controllers\ReportController::class, 'deleteAllReports'])->name('reports.delete-all');
        
        // Activity logs
        Route::get('/activity', [App\Http\Controllers\ActivityController::class, 'index'])->name('activity.index');
        Route::get('/activity/export', [App\Http\Controllers\ActivityController::class, 'export'])->name('activity.export');
        Route::delete('/activity/clear', [App\Http\Controllers\ActivityController::class, 'clearOld'])->name('activity.clear');
        Route::delete('/activity/log/{id}', [App\Http\Controllers\ActivityController::class, 'deleteLog'])->name('activity.log.delete');
        Route::delete('/activity/sessions/all', [App\Http\Controllers\ActivityController::class, 'terminateAllSessions'])->name('activity.terminate-all-sessions');
        Route::delete('/activity/sessions/{id}', [App\Http\Controllers\ActivityController::class, 'terminateSession'])->name('activity.terminate-session');


    });
});
require __DIR__.'/mini-chat.php';
