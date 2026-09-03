<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Extrait les noms de permissions references par le middleware `permission:`
 * sur les routes enregistrees. Sert a creer automatiquement dans Spatie les
 * permissions attendues par de nouvelles routes (commande permissions:sync,
 * RolePermissionSeeder).
 */
final class RoutePermissions
{
    /**
     * @return list<string>
     */
    public static function referenced(): array
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                    continue;
                }

                foreach (explode('|', substr($middleware, strlen('permission:'))) as $name) {
                    $name = trim($name);

                    if ($name !== '') {
                        $names[$name] = true;
                    }
                }
            }
        }

        return array_keys($names);
    }
}
