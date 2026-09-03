<?php

namespace Tests;

use App\Models\User;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Un compte de EreveSeeder resolu par role : insensible aux adresses email
     * configurees dans le seeder.
     */
    protected function seededUserWithRole(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    protected function seededAdministrateur(): User
    {
        return $this->seededUserWithRole(Rbac::ADMINISTRATEUR);
    }

    protected function seededSuperAdmin(): User
    {
        return $this->seededUserWithRole(Rbac::SUPERADMIN_PLATEFORME);
    }
}
