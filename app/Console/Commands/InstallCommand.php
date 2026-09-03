<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Prepare une instance de production : le referentiel RBAC et un unique compte
 * "SuperAdmin plateforme", sans le jeu de donnees de demonstration
 * (EreveSeeder refuse de tourner en production — SEC-26).
 *
 *   php artisan ereve:install --email=admin@exemple.cd
 *
 * Idempotent : re-executable pour reparer le RBAC ou re-provisionner l'admin.
 */
class InstallCommand extends Command
{
    protected $signature = 'ereve:install
        {--email= : Email du compte SuperAdmin plateforme}
        {--password= : Mot de passe (sinon demande de maniere masquee)}
        {--name=SuperAdmin plateforme : Nom affiche du compte}
        {--migrate : Lancer aussi les migrations avant le seeding}';

    protected $description = 'Initialise une instance production : RBAC + compte SuperAdmin plateforme (sans donnees de demonstration).';

    public function handle(): int
    {
        $this->info('Initialisation eReve Church — RBAC + SuperAdmin plateforme.');

        if ($this->option('migrate')) {
            $this->call('migrate', ['--force' => true]);
        }

        if (! Schema::hasTable('permissions')) {
            $this->error('Table "permissions" absente. Lancez d\'abord: php artisan migrate --force  (ou --migrate).');

            return self::FAILURE;
        }

        $email = $this->option('email') ?: $this->ask('Email du SuperAdmin plateforme');
        $password = $this->option('password') ?: $this->secret('Mot de passe (10 caracteres minimum)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:10'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $this->components->task('Referentiel RBAC (roles + permissions)', function () {
            return $this->callSilent('db:seed', [
                '--class' => RolePermissionSeeder::class,
                '--force' => true,
            ]) === self::SUCCESS;
        });

        $existing = User::where('email', $email)->exists();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $this->option('name') ?: 'SuperAdmin plateforme',
                'password' => Hash::make($password),
                'level' => Rbac::LEVEL_PLATFORM,
                'status' => 'actif',
                'member_id' => null,
                'church_id' => null,
                'community_id' => null,
            ],
        );

        $user->syncRoles([Rbac::SUPERADMIN_PLATEFORME]);

        $this->components->info(sprintf(
            'Compte %s %s : %s',
            Rbac::SUPERADMIN_PLATEFORME,
            $existing ? 'mis a jour' : 'cree',
            $email,
        ));
        $this->line('Connexion : mot de passe + code OTP recu par email (verifier la configuration MAIL_*).');

        return self::SUCCESS;
    }
}
