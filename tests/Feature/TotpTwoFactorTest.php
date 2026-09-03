<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailOtpCodeNotification;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 2FA par application d'authentification (TOTP, colonne users.otp_secret).
 */
class TotpTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Agent',
            'email' => 'agent@ereve.cd',
            'password' => Hash::make('MotDePasseFort1'),
            'status' => 'actif',
            'level' => 'coordination',
        ], $overrides));
    }

    private function withTotp(): array
    {
        $secret = Totp::generateSecret();
        $user = $this->user();
        $user->forceFill(['otp_secret' => $secret])->save();

        return [$user, $secret];
    }

    // --- Helper TOTP ----------------------------------------------------------

    public function test_totp_helper_matches_rfc6238_vector(): void
    {
        // Secret ASCII "12345678901234567890" en base32.
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', Totp::codeAt($secret, 59));
        $this->assertSame('081804', Totp::codeAt($secret, 1111111109));
        $this->assertTrue(Totp::verify($secret, Totp::codeAt($secret)));
        $this->assertFalse(Totp::verify($secret, '000000'));
    }

    // --- Activation / desactivation -----------------------------------------

    public function test_page_is_reachable_and_shows_disabled_by_default(): void
    {
        $this->actingAs($this->user())
            ->get('/securite/authentification')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Security/TwoFactor')->where('enabled', false));
    }

    public function test_enable_flow_persists_the_secret_only_after_a_valid_code(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/securite/authentification')->assertRedirect();
        $secret = session('auth.totp_setup_secret');
        $this->assertNotEmpty($secret);
        $this->assertNull($user->fresh()->otp_secret); // rien de persiste tant que non confirme

        // Mauvais code : refuse.
        $this->actingAs($user)->post('/securite/authentification/confirmer', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->otp_secret);

        // Bon code : active.
        $this->actingAs($user)->post('/securite/authentification/confirmer', ['code' => Totp::codeAt($secret)])
            ->assertRedirect();

        $this->assertSame($secret, $user->fresh()->otp_secret);
        $this->assertTrue($user->fresh()->hasTotpEnabled());
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.totp.enabled']);
    }

    public function test_disable_requires_the_current_password(): void
    {
        [$user] = $this->withTotp();

        $this->actingAs($user)->delete('/securite/authentification', ['password' => 'mauvais'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTotpEnabled());

        $this->actingAs($user)->delete('/securite/authentification', ['password' => 'MotDePasseFort1'])
            ->assertRedirect();
        $this->assertFalse($user->fresh()->hasTotpEnabled());
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.totp.disabled']);
    }

    // --- Connexion ---------------------------------------------------------

    public function test_login_uses_totp_and_skips_the_email_code(): void
    {
        Notification::fake();
        [$user, $secret] = $this->withTotp();

        $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasseFort1'])
            ->assertRedirect(route('otp.create'));

        $this->assertSame('totp', session('auth.otp_method'));
        Notification::assertNotSentTo($user, EmailOtpCodeNotification::class);

        $this->post('/otp', ['code' => Totp::codeAt($secret)])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login']);
    }

    public function test_login_without_totp_still_uses_the_email_code(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasseFort1'])
            ->assertRedirect(route('otp.create'));

        $this->assertSame('email', session('auth.otp_method'));
        Notification::assertSentTo($user, EmailOtpCodeNotification::class);
    }

    public function test_totp_login_rejects_a_wrong_code(): void
    {
        [$user] = $this->withTotp();

        $this->post('/login', ['email' => $user->email, 'password' => 'MotDePasseFort1']);
        $this->post('/otp', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
