<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailOtpCodeNotification;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || $user->status !== 'actif') {
            Audit::record('auth.login_failed', $user, ['email' => $credentials['email']]);

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides ou compte inactif.',
            ]);
        }

        // Application d'authentification (TOTP) : le code est time-based, aucun
        // envoi ; on laisse 10 min pour terminer la connexion.
        if ($user->hasTotpEnabled()) {
            $request->session()->put('auth.pending_user_id', $user->id);
            $request->session()->put('auth.otp_attempts', 0);
            $request->session()->put('auth.otp_method', 'totp');
            $request->session()->put('auth.otp_expires_at', now()->addMinutes(10)->getTimestamp());

            return redirect()->route('otp.create')
                ->with('success', "Saisissez le code affiche par votre application d'authentification.");
        }

        // Sinon : code a usage unique envoye par email (B2). L'envoi d'abord :
        // un echec (hors demo) interrompt la connexion avant tout ecrit en session.
        $ttlSeconds = (int) config('auth.otp_ttl', 300);
        $otp = (string) random_int(100000, 999999);

        $this->deliverOtp($user, $otp, (int) ceil($ttlSeconds / 60));

        $request->session()->put('auth.pending_user_id', $user->id);
        $request->session()->put('auth.otp_attempts', 0);
        $request->session()->put('auth.otp_method', 'email');
        $request->session()->put('auth.otp_code', $otp);
        $request->session()->put('auth.otp_expires_at', now()->addSeconds($ttlSeconds)->getTimestamp());

        // Le code n'est plus jamais affiche a l'ecran : la livraison par email
        // est fiable, il n'y a donc plus de mode "demonstration" cote UI.
        return redirect()->route('otp.create')
            ->with('success', 'Un code de connexion vient de vous etre envoye par email.');
    }

    private function deliverOtp(User $user, string $otp, int $ttlMinutes): void
    {
        $demo = (bool) config('auth.otp_demo');

        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            if ($demo) {
                return;
            }

            throw ValidationException::withMessages([
                'email' => 'Aucune adresse email valide n\'est associee a ce compte pour recevoir le code.',
            ]);
        }

        try {
            $user->notify(new EmailOtpCodeNotification($otp, $ttlMinutes));
        } catch (\Throwable $exception) {
            Log::error('Echec de l\'envoi du code de connexion par email.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            if (! $demo) {
                throw ValidationException::withMessages([
                    'email' => 'Impossible d\'envoyer le code de connexion par email. Reessayez plus tard.',
                ]);
            }
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Audit::record('auth.logout', $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
