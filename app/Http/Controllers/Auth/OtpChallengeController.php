<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OtpChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('auth.pending_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/Otp', [
            'method' => (string) $request->session()->get('auth.otp_method', 'email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pendingUserId = $request->session()->get('auth.pending_user_id');
        $expiresAt = (int) $request->session()->get('auth.otp_expires_at', 0);
        $method = (string) $request->session()->get('auth.otp_method', 'email');

        // Session OTP absente ou expiree -> on repart du login.
        if (! $pendingUserId || $expiresAt < now()->getTimestamp()) {
            $this->flushOtp($request);

            throw ValidationException::withMessages(['code' => 'Session OTP expiree, reconnectez-vous.']);
        }

        $attempts = (int) $request->session()->get('auth.otp_attempts', 0) + 1;
        $request->session()->put('auth.otp_attempts', $attempts);

        $user = User::findOrFail($pendingUserId);

        $valid = $method === 'totp'
            ? Totp::verify((string) $user->otp_secret, $data['code'])
            : hash_equals((string) $request->session()->get('auth.otp_code', ''), $data['code']);

        if (! $valid) {
            if ($attempts >= (int) config('auth.otp_max_attempts', 5)) {
                $this->flushOtp($request);

                throw ValidationException::withMessages(['code' => 'Trop de tentatives, reconnectez-vous.']);
            }

            throw ValidationException::withMessages(['code' => 'Code OTP invalide.']);
        }

        abort_unless($user->status === 'actif', 403);

        $this->flushOtp($request);
        $user->forceFill(['otp_verified_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        Audit::record('auth.login', $user, ['method' => $method]);

        return redirect()->intended(route('dashboard'));
    }

    private function flushOtp(Request $request): void
    {
        $request->session()->forget([
            'auth.pending_user_id',
            'auth.otp_code',
            'auth.otp_expires_at',
            'auth.otp_attempts',
            'auth.otp_method',
        ]);
    }
}
