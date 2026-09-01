<?php

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cree les permissions et les 5 roles metier (+ le role technique plateforme)
 * a partir de la matrice App\Support\Rbac. Idempotent : peut etre rejoue.
 *
 * Utilisable seul dans les tests qui ont besoin du RBAC sans le jeu de
 * donnees de demonstration complet.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Rbac::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Rbac::matrix() as $roleName => $permissions) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['level' => Rbac::levelFor($roleName)],
            );

            $role->syncPermissions($permissions);
        }

        // Retrait des anciens roles heterogenes (SuperUser, Pasteur, Membre, ...).
        Role::whereNotIn('name', Rbac::roles())->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
