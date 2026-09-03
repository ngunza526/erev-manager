<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailOtpCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * B2 — Livraison du code de connexion (OTP) par email.
 */
class OtpEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Agent',
            'email' => 'agent@ereve.cd',
            'password' => Hash::make('password'),
            'status' => 'actif',
            'level' => 'coordination',
        ]);
    }

    public function test_login_sends_the_otp_code_by_email(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('otp.create'));

        Notification::assertSentTo($user, EmailOtpCodeNotification::class, function ($notification, $channels, $notifiable) {
            $mail = $notification->toMail($notifiable);

            // Le code envoye est bien celui attendu a l'etape de verification.
            return in_array('**'.session('auth.otp_code').'**', $mail->introLines, true);
        });
    }

    public function test_login_is_blocked_when_email_delivery_fails_and_demo_is_off(): void
    {
        config([
            'auth.otp_demo' => false,
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 2, // connexion refusee immediatement
            'mail.mailers.smtp.timeout' => 1,
        ]);
        $user = $this->user();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertNull(session('auth.pending_user_id'));
        $this->assertGuest();
    }

    public function test_demo_mode_tolerates_a_delivery_failure(): void
    {
        config([
            'auth.otp_demo' => true,
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 2,
            'mail.mailers.smtp.timeout' => 1,
        ]);
        $user = $this->user();

        // Le code reste affiche a l'ecran, la connexion continue malgre l'echec SMTP.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('otp.create'))
            ->assertSessionHas('success', fn ($m) => str_contains((string) $m, 'demonstration'));

        $this->assertNotNull(session('auth.otp_code'));
    }
}
