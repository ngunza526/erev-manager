<?php

namespace Tests;

use App\Models\User;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Le compte Administrateur (coordination) cree par EreveSeeder, resolu par
     * role : insensible a l'adresse email configuree dans le seeder.
     */
    protected function seededAdministrateur(): User
    {
        return User::role(Rbac::ADMINISTRATEUR)->firstOrFail();
    }
}
