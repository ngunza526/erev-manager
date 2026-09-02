<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $otp = (string) random_int(100000, 999999);
        $request->session()->put('auth.pending_user_id', $user->id);
        $request->session()->put('auth.otp_code', $otp);
        $request->session()->put('auth.otp_expires_at', now()->addSeconds((int) config('auth.otp_ttl', 300))->getTimestamp());
        $request->session()->put('auth.otp_attempts', 0);

        $redirect = redirect()->route('otp.create');

        // SEC-21 : le code n'est revele que hors production (mode demo).
        return config('auth.otp_demo')
            ? $redirect->with('success', "Code OTP de demonstration: {$otp}")
            : $redirect;
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
