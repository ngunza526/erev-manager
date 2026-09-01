<?php

namespace Tests\Concerns;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Aide de test : garantit que les roles/permissions RBAC existent puis les
 * attribue a un utilisateur cree a la main dans les tests.
 */
trait AssignsRoles
{
    private bool $rolesSeeded = false;

    protected function seedRoles(): void
    {
        if (! $this->rolesSeeded) {
            $this->seed(RolePermissionSeeder::class);
            $this->rolesSeeded = true;
        }
    }

    protected function withRoles(User $user, string ...$roles): User
    {
        $this->seedRoles();
        $user->syncRoles($roles);

        return $user->load('roles', 'permissions');
    }
}
