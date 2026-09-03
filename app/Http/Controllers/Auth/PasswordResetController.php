<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reinitialisation de mot de passe via le broker Laravel (table
 * password_reset_tokens, lien envoye par email — depend de la config MAIL_*).
 */
class PasswordResetController extends Controller
{
    public function requestForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        // Reponse volontairement generique : pas d'enumeration de comptes.
        Password::sendResetLink($data);

        return back()->with('success', 'Si un compte correspond a cette adresse, un lien de reinitialisation vient d\'etre envoye.');
    }

    public function resetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset($data, function ($user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => 'Ce lien de reinitialisation est invalide ou a expire.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Mot de passe mis a jour, vous pouvez vous connecter.');
    }
}
