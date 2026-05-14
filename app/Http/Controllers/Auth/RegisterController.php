<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        // Define reserved usernames that cannot be used
        $reservedUsernames = [
            'admin', 'administrator', 'root', 'system', 'sysadmin',
            'moderator', 'mod', 'staff', 'support', 'help',
            'bot', 'robot', 'api', 'service',
            'laravel', 'social', 'twitter', 'x', 'meta', 'facebook',
            'instagram', 'linkedin', 'youtube', 'tiktok',
            'app', 'application', 'platform', 'site', 'website',
            'company', 'official', 'team', 'dev', 'developer',
            'superuser', 'superadmin', 'master', 'owner',
            'ceo', 'founder', 'manager', 'director'
        ];

        // Define disposable email domains to block
        $disposableEmailDomains = [
            '10minutemail.com', 'guerrillamail.com', 'mailinator.com', 'temp-mail.org',
            'throwaway.email', 'yopmail.com', 'maildrop.cc', 'tempail.com',
            'fakeinbox.com', 'mailcatch.com', 'tempinbox.com', 'dispostable.com'
        ];

        // Pre-validation cleanup: Remove unverified users with the same email/username 
        // to prevent blocking new registration attempts.
        if ($request->has('username') || $request->has('email')) {
            \App\Models\User::whereNull('email_verified_at')
                ->where(function($q) use ($request) {
                    $q->where('username', $request->username)
                      ->orWhere('email', $request->email);
                })
                ->delete();
        }

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_-]+$/',
                function ($attribute, $value, $fail) use ($reservedUsernames) {
                    if (in_array(strtolower($value), $reservedUsernames)) {
                        $fail('This username is reserved and cannot be used.');
                    }
                },
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                function ($attribute, $value, $fail) use ($disposableEmailDomains) {
                    $domain = strtolower(substr(strrchr($value, "@"), 1));
                    if (in_array($domain, $disposableEmailDomains)) {
                        $fail('Disposable email addresses are not allowed. Please use a valid email address.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name ?? $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
        ]);

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false))
            ->with('status', 'registration-successful');
    }
}
