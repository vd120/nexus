<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_returns_qr_code_and_secret_for_user_without_2fa(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('2fa.setup'));

        $response->assertOk()
            ->assertJsonStructure(['enabled', 'qr_code_uri', 'secret'])
            ->assertJson(['enabled' => false]);

        $this->assertNotNull(session('2fa_pending_secret'));
    }

    public function test_setup_returns_enabled_true_when_2fa_already_active(): void
    {
        $user = User::factory()->create(['two_factor_secret' => 'EXISTINGSECRET']);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_confirmed' => true])
            ->get(route('2fa.setup'));

        $response->assertOk()->assertJson(['enabled' => true]);
    }

    public function test_confirm_rejects_invalid_totp_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('2fa.setup'));

        $response = $this->actingAs($user)
            ->postJson(route('2fa.confirm'), ['code' => '000000']);

        $response->assertStatus(422);
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_confirm_rejects_when_no_pending_secret_in_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('2fa.confirm'), ['code' => '123456']);

        $response->assertStatus(422);
    }

    public function test_disable_requires_correct_password(): void
    {
        $user = User::factory()->create(['two_factor_secret' => 'SOMESECRET']);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_confirmed' => true])
            ->postJson(route('2fa.disable'), ['password' => 'wrongpassword']);

        $response->assertStatus(422);
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_disable_clears_secret_with_correct_password(): void
    {
        $user = User::factory()->create([
            'two_factor_secret'         => 'SOMESECRET',
            'two_factor_recovery_codes' => [Hash::make('ABCDE12345')],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_confirmed' => true])
            ->postJson(route('2fa.disable'), ['password' => 'password']);

        $response->assertOk();
        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
    }

    public function test_challenge_redirects_with_valid_recovery_code(): void
    {
        $rawCode = 'ABCDE12345';
        $user = User::factory()->create([
            'two_factor_secret'         => 'FAKESECRET',
            'two_factor_recovery_codes' => [Hash::make($rawCode)],
        ]);

        $this->actingAs($user)->withSession(['two_factor_confirmed' => false]);

        $response = $this->actingAs($user)
            ->post(route('2fa.challenge.post'), ['code' => $rawCode]);

        $response->assertRedirect('/');
        // Recovery code should be consumed
        $this->assertEmpty($user->fresh()->two_factor_recovery_codes);
    }

    public function test_challenge_returns_error_for_invalid_code(): void
    {
        $user = User::factory()->create([
            'two_factor_secret'         => 'FAKESECRET',
            'two_factor_recovery_codes' => [Hash::make('VALIDCODE1')],
        ]);

        $response = $this->actingAs($user)
            ->post(route('2fa.challenge.post'), ['code' => 'WRONGCODE1']);

        $response->assertSessionHasErrors('code');
    }

    public function test_protected_route_redirects_to_2fa_challenge_when_2fa_enabled_but_not_confirmed(): void
    {
        $user = User::factory()->create(['two_factor_secret' => 'FAKESECRET']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('2fa.challenge'));
    }
}
