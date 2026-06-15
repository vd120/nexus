<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail() && !session('auth.suspicious')) {
            if ($request->user()->password === null) {
                return redirect()->route('password.set-password')->with('message', __('messages.please_set_password'));
            }
            return back()->with('already_verified', __('messages.email_already_verified'));
        }

        $verificationCode = $request->user()->generateVerificationCode();

        $originalLocale = app()->getLocale();
        if ($request->user()->language) app()->setLocale($request->user()->language);
        $subject = __('emails.verification_code_subject', ['app' => config('app.name')]);

        \Log::info('Resending verification code to ' . $request->user()->email);
        try {
            \Illuminate\Support\Facades\Mail::to($request->user()->email, $request->user()->name)->send(new VerificationCodeMail($request->user(), $verificationCode));
            \Log::info('Resending: Email sent successfully to ' . $request->user()->email);
        } catch (\Exception $e) {
            \Log::error('Resending: Failed to send verification email to ' . $request->user()->email . '. Error: ' . $e->getMessage());
        }
        app()->setLocale($originalLocale);

        return back()->with('message', __('messages.verification_code_sent'));
    }

    /**
     * Verify the email using the provided code.
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6|regex:/^\d{6}$/',
        ]);

        $user = $request->user();

        if ($user && $user->verifyCode($request->code)) {
            session()->forget('auth.suspicious');
            $request->session()->regenerate();

            if ($user->password === null) {
                return redirect()->route('password.set-password')->with('message', __('messages.please_set_password'));
            }

            return redirect()->intended('/')->with('message', __('messages.email_verified_success'));
        }

        return back()->withErrors(['code' => __('auth.invalid_verification_code')]);
    }
}
