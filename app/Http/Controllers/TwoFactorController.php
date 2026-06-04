<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');

        // If already enabled, return current status
        if ($user->two_factor_secret) {
            return response()->json(['enabled' => true]);
        }

        $secret = $google2fa->generateSecretKey();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generate QR code as SVG data URI
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrSvg = base64_encode($writer->writeString($qrCodeUrl));

        // Store secret in session (not DB yet — user must confirm first)
        session(['2fa_pending_secret' => $secret]);

        return response()->json([
            'enabled'     => false,
            'qr_code_uri' => 'data:image/svg+xml;base64,' . $qrSvg,
            'secret'      => $secret,
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');
        $secret = session('2fa_pending_secret');

        if (!$secret) {
            return response()->json(['message' => __('auth.two_factor_session_expired')], 422);
        }

        $valid = $google2fa->verifyKey($secret, $request->code);
        if (!$valid) {
            return response()->json(['message' => __('auth.two_factor_invalid_code')], 422);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->two_factor_secret         = $secret;
        $user->two_factor_recovery_codes = array_map(fn($code) => Hash::make($code), $recoveryCodes);
        $user->save();

        session()->forget('2fa_pending_secret');
        session(['two_factor_confirmed' => true]);

        return response()->json([
            'message'        => __('auth.two_factor_enabled_success'),
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => __('auth.password_incorrect')], 422);
        }

        $user->two_factor_secret         = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        session()->forget('two_factor_confirmed');

        return response()->json(['message' => __('auth.two_factor_disabled_success')]);
    }

    public function showChallenge()
    {
        if (!auth()->check() || !auth()->user()->two_factor_secret) {
            return redirect('/');
        }

        if (session('two_factor_confirmed')) {
            return redirect('/');
        }

        return view('auth.two-factor-challenge');
    }

    public function challenge(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');
        $code = trim($request->code);

        // Try TOTP code
        if (strlen($code) === 6 && ctype_digit($code)) {
            if ($google2fa->verifyKey($user->two_factor_secret, $code)) {
                session(['two_factor_confirmed' => true]);
                return redirect()->intended('/');
            }
        }

        // Try recovery code
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        foreach ($recoveryCodes as $index => $hashed) {
            if (Hash::check($code, $hashed)) {
                // Invalidate used recovery code
                $recoveryCodes[$index] = null;
                $user->two_factor_recovery_codes = array_values(array_filter($recoveryCodes));
                $user->save();

                session(['two_factor_confirmed' => true]);
                return redirect()->intended('/');
            }
        }

        return back()->withErrors(['code' => __('auth.two_factor_invalid_code')]);
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }
        return $codes;
    }
}
