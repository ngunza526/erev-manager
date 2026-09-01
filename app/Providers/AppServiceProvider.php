<?php

namespace App\Providers;

use App\Support\Rbac;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
    }
}
