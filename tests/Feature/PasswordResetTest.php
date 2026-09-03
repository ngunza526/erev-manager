<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * NB4 — Reinitialisation de mot de passe.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Agent',
            'email' => 'agent@ereve.cd',
            'password' => Hash::make('AncienMotDePasse1'),
            'status' => 'actif',
            'level' => 'coordination',
        ]);
    }

    public function test_the_request_form_is_reachable(): void
    {
        $this->get('/mot-de-passe/oubli')->assertOk();
    }

    public function test_a_reset_link_is_sent_for_a_known_email(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/mot-de-passe/oubli', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_response_is_generic_for_an_unknown_email(): void
    {
        Notification::fake();

        $this->post('/mot-de-passe/oubli', ['email' => 'inconnu@ereve.cd'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_password_is_reset_with_a_valid_token(): void
    {
        $user = $this->user();
        $token = Password::broker()->createToken($user);

        $this->post('/mot-de-passe/reinitialiser', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NouveauMotDePasse2',
            'password_confirmation' => 'NouveauMotDePasse2',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('NouveauMotDePasse2', $user->fresh()->password));
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = $this->user();

        $this->post('/mot-de-passe/reinitialiser', [
            'token' => 'jeton-bidon',
            'email' => $user->email,
            'password' => 'NouveauMotDePasse2',
            'password_confirmation' => 'NouveauMotDePasse2',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('AncienMotDePasse1', $user->fresh()->password));
    }

    public function test_a_weak_new_password_is_rejected(): void
    {
        $user = $this->user();
        $token = Password::broker()->createToken($user);

        $this->post('/mot-de-passe/reinitialiser', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'court',
            'password_confirmation' => 'court',
        ])->assertSessionHasErrors('password');
    }
}
