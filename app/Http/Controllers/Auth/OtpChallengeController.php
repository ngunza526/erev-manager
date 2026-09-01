<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        return Inertia::render('Auth/Otp');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($data['code'] !== $request->session()->get('auth.otp_code')) {
            throw ValidationException::withMessages(['code' => 'Code OTP invalide.']);
        }

        $user = User::findOrFail($request->session()->pull('auth.pending_user_id'));
        $request->session()->forget('auth.otp_code');
        $user->forceFill(['otp_verified_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
