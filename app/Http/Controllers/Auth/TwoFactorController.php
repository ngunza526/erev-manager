<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion par l'utilisateur de sa 2FA par application d'authentification (TOTP,
 * colonne users.otp_secret). Tant que le secret en attente n'est pas confirme
 * par un code valide, rien n'est ecrit — pas de risque de verrouillage.
 * Quand la 2FA TOTP est active, elle remplace l'OTP par email a la connexion.
 */
class TwoFactorController extends Controller
{
    private const SETUP_KEY = 'auth.totp_setup_secret';

    public function show(Request $request): Response
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get(self::SETUP_KEY);

        return Inertia::render('Security/TwoFactor', [
            'enabled' => $user->hasTotpEnabled(),
            'pending' => $pendingSecret ? [
                'secret' => $pendingSecret,
                'uri' => Totp::provisioningUri($pendingSecret, $user->email, config('app.name')),
            ] : null,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasTotpEnabled(), 422, 'La 2FA est deja active.');

        $request->session()->put(self::SETUP_KEY, Totp::generateSecret());

        return back()->with('success', "Scannez le QR code puis saisissez un code pour confirmer l'activation.");
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);

        $secret = (string) $request->session()->get(self::SETUP_KEY);

        if ($secret === '' || ! Totp::verify($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Code invalide. Verifiez l\'heure de votre telephone et reessayez.']);
        }

        $request->user()->forceFill([
            'otp_secret' => $secret,
            'otp_verified_at' => null,
        ])->save();
        $request->session()->forget(self::SETUP_KEY);

        Audit::record('auth.totp.enabled', $request->user());

        return back()->with('success', 'Application d\'authentification activee. Elle sera demandee a la prochaine connexion.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }

        $request->user()->forceFill(['otp_secret' => null])->save();
        $request->session()->forget(self::SETUP_KEY);

        Audit::record('auth.totp.disabled', $request->user());

        return back()->with('success', 'Application d\'authentification desactivee. La connexion repasse au code par email.');
    }
}
