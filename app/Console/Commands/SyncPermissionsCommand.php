<?php

namespace App\Console\Commands;

use App\Support\Rbac;
use App\Support\RoutePermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cree dans Spatie les permissions attendues (referentiel Rbac + middleware
 * `permission:` des routes) et absentes en base. A lancer apres l'ajout de
 * routes protegees par une nouvelle permission.
 *
 *   php artisan permissions:sync
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync {--guard=web : Guard des permissions}';

    protected $description = 'Synchronise les permissions Spatie avec le referentiel Rbac et les routes.';

    public function handle(): int
    {
        $guard = (string) $this->option('guard');

        $wanted = collect(Rbac::permissions())
            ->merge(RoutePermissions::referenced())
            ->unique()
            ->sort()
            ->values();

        $existing = Permission::where('guard_name', $guard)->pluck('name');
        $missing = $wanted->diff($existing)->values();

        $missing->each(fn (string $name) => Permission::findOrCreate($name, $guard));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->report($missing, $existing->diff($wanted)->values());

        return self::SUCCESS;
    }

    private function report(Collection $created, Collection $unused): void
    {
        $created->isEmpty()
            ? $this->components->info('Permissions a jour, rien a creer.')
            : $this->components->info($created->count().' permission(s) creee(s) : '.$created->implode(', '));

        if ($unused->isNotEmpty()) {
            $this->components->warn(
                'En base mais referencees nulle part (routes ni Rbac) : '.$unused->implode(', ')
            );
        }
    }
}
