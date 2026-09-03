<?php

namespace App\Providers;

use App\Support\Rbac;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le role technique plateforme (exploitant du SaaS) court-circuite
        // toutes les verifications d'autorisation applicatives.
        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole(Rbac::SUPERADMIN_PLATEFORME) ? true : null;
        });

        $this->configureRateLimiting();
        $this->configurePasswordPolicy();
    }

    /**
     * SEC-20 — Limitation de debit sur les surfaces d'authentification et les
     * formulaires publics (force brute mot de passe / OTP, pollution comptable).
     */
    private function configureRateLimiting(): void
    {
        $byCredential = fn (Request $request) => Str::lower((string) $request->input('email')).'|'.$request->ip();

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(6)->by($byCredential($request)),
            Limit::perMinute(30)->by($request->ip()),
        ]);

        RateLimiter::for('api-token', fn (Request $request) => [
            Limit::perMinute(6)->by($byCredential($request)),
            Limit::perMinute(30)->by($request->ip()),
        ]);

        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(10)
            ->by((string) ($request->session()->get('auth.pending_user_id') ?? $request->ip())));

        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
    }

    /**
     * SEC-25 — Politique de mot de passe : min 10, mixte, non compromis
     * (verification HIBP desactivee hors environnements distants).
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->letters()->numbers();

            // La verification HIBP fait un appel reseau : on l'evite en local/test.
            return $this->app->environment(['local', 'testing']) ? $rule : $rule->uncompromised();
        });
    }
}
