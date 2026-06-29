<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class SuspiciousLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to setup user with past logins so a new one can be flagged as suspicious.
     */
    protected function createUserWithPastLogins(array $userAttributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ], $userAttributes));

        // Create 5 recent normal logins
        for ($i = 0; $i < 5; $i++) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                'device_type' => 'desktop',
                'browser' => 'Chrome',
                'country' => 'United States',
                'logged_at' => now()->subDays(5 - $i),
            ]);
        }

        return $user;
    }

    public function test_suspicious_login_prompts_for_email_code_when_2fa_disabled(): void
    {
        $user = $this->createUserWithPastLogins();

        // Attempt login from a different browser to trigger suspicious check
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Firefox/120.0',
        ]);

        // Should redirect to the suspicious challenge view
        $response->assertRedirect();
        
        // Extract challenge UUID from redirection URL
        $location = $response->headers->get('Location');
        preg_match('/\/login\/suspicious\/(.+)/', $location, $matches);
        $uuid = $matches[1] ?? null;
        $this->assertNotNull($uuid);

        // Assert challenge in cache is 'manual' (email)
        $challenge = Cache::get('suspicious_challenge_' . $uuid);
        $this->assertNotNull($challenge);
        $this->assertEquals('manual', $challenge['type']);
        $this->assertNotNull($challenge['email_code']);

        // Assert view shows email verification code field
        $viewResponse = $this->get(route('login.suspicious.view', $uuid));
        $viewResponse->assertOk();
        $viewResponse->assertSee(__('auth.verification_code'));
    }

    public function test_suspicious_login_prompts_for_2fa_code_when_2fa_enabled(): void
    {
        $user = $this->createUserWithPastLogins([
            'two_factor_secret' => 'MOCKSECRETKEY',
        ]);

        // Attempt login from a different browser
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Firefox/120.0',
        ]);

        $response->assertRedirect();
        
        $location = $response->headers->get('Location');
        preg_match('/\/login\/suspicious\/(.+)/', $location, $matches);
        $uuid = $matches[1] ?? null;
        $this->assertNotNull($uuid);

        // Assert challenge in cache is '2fa' and doesn't contain email_code
        $challenge = Cache::get('suspicious_challenge_' . $uuid);
        $this->assertNotNull($challenge);
        $this->assertEquals('2fa', $challenge['type']);
        $this->assertNull($challenge['email_code']);

        // Assert view shows 2FA code field
        $viewResponse = $this->get(route('login.suspicious.view', $uuid));
        $viewResponse->assertOk();
        $viewResponse->assertSee(__('auth.two_factor_authentication'));
        $viewResponse->assertDontSee(__('auth.resend_code'));
    }

    public function test_suspicious_login_2fa_verification_success_with_valid_totp(): void
    {
        $user = $this->createUserWithPastLogins([
            'two_factor_secret' => 'MOCKSECRETKEY',
        ]);

        // Create a fake challenge cache entry directly for testing verify route
        $uuid = 'test-uuid-2fa-totp';
        Cache::put('suspicious_challenge_' . $uuid, [
            'user_id' => $user->id,
            'type' => '2fa',
            'ip' => '127.0.0.1',
            'user_agent' => 'Firefox',
            'remember' => true,
        ], 600);

        // Mock Google2FA verification
        $google2faMock = Mockery::mock(app('pragmarx.google2fa'));
        $google2faMock->shouldReceive('verifyKey')
            ->once()
            ->with('MOCKSECRETKEY', '123456')
            ->andReturn(true);
        $this->app->instance('pragmarx.google2fa', $google2faMock);

        $response = $this->post(route('login.suspicious.verify', $uuid), [
            'code' => '123456',
        ]);

        $response->assertRedirect('/');
        $this->assertTrue(session('two_factor_confirmed'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_suspicious_login_2fa_verification_success_with_valid_recovery_code(): void
    {
        $user = $this->createUserWithPastLogins([
            'two_factor_secret' => 'MOCKSECRETKEY',
            'two_factor_recovery_codes' => [Hash::make('RECOVERYCODE123')],
        ]);

        $uuid = 'test-uuid-2fa-recovery';
        Cache::put('suspicious_challenge_' . $uuid, [
            'user_id' => $user->id,
            'type' => '2fa',
            'ip' => '127.0.0.1',
            'user_agent' => 'Firefox',
            'remember' => true,
        ], 600);

        // Mock Google2FA verification (fails for TOTP check)
        $google2faMock = Mockery::mock(app('pragmarx.google2fa'));
        $google2faMock->shouldReceive('verifyKey')
            ->andReturn(false);
        $this->app->instance('pragmarx.google2fa', $google2faMock);

        $response = $this->post(route('login.suspicious.verify', $uuid), [
            'code' => 'RECOVERYCODE123',
        ]);

        $response->assertRedirect('/');
        $this->assertTrue(session('two_factor_confirmed'));
        $this->assertAuthenticatedAs($user);

        // Recovery code should be consumed
        $this->assertEmpty($user->fresh()->two_factor_recovery_codes);
    }

    public function test_suspicious_login_2fa_verification_fails_with_invalid_code(): void
    {
        $user = $this->createUserWithPastLogins([
            'two_factor_secret' => 'MOCKSECRETKEY',
            'two_factor_recovery_codes' => [Hash::make('RECOVERYCODE123')],
        ]);

        $uuid = 'test-uuid-2fa-fail';
        Cache::put('suspicious_challenge_' . $uuid, [
            'user_id' => $user->id,
            'type' => '2fa',
            'ip' => '127.0.0.1',
            'user_agent' => 'Firefox',
            'remember' => true,
        ], 600);

        // Mock Google2FA verification
        $google2faMock = Mockery::mock(app('pragmarx.google2fa'));
        $google2faMock->shouldReceive('verifyKey')
            ->andReturn(false);
        $this->app->instance('pragmarx.google2fa', $google2faMock);

        $response = $this->post(route('login.suspicious.verify', $uuid), [
            'code' => 'INVALIDCODE',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
