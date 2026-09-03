<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * B3 — `ereve:install` : amorcage d'une instance production sans donnees de demo.
 */
class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_rbac_and_the_platform_superadmin(): void
    {
        $this->artisan('ereve:install', [
            '--email' => 'boss@ereve.cd',
            '--password' => 'MotDePasseFort1',
            '--name' => 'Patron',
        ])->assertSuccessful();

        // RBAC en place.
        foreach (Rbac::roles() as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "role manquant: {$role}");
        }

        $user = User::where('email', 'boss@ereve.cd')->firstOrFail();
        $this->assertSame(Rbac::LEVEL_PLATFORM, $user->level);
        $this->assertSame('actif', $user->status);
        $this->assertTrue($user->hasRole(Rbac::SUPERADMIN_PLATEFORME));
    }

    public function test_it_is_idempotent(): void
    {
        $args = ['--email' => 'boss@ereve.cd', '--password' => 'MotDePasseFort1'];

        $this->artisan('ereve:install', $args)->assertSuccessful();
        $this->artisan('ereve:install', array_merge($args, ['--password' => 'AutreMotDePasse2']))->assertSuccessful();

        $this->assertSame(1, User::where('email', 'boss@ereve.cd')->count());
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->artisan('ereve:install', [
            '--email' => 'boss@ereve.cd',
            '--password' => 'court',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'boss@ereve.cd']);
    }

    public function test_it_does_not_load_demo_data(): void
    {
        $this->artisan('ereve:install', [
            '--email' => 'boss@ereve.cd',
            '--password' => 'MotDePasseFort1',
        ])->assertSuccessful();

        // Aucun compte de demonstration (EreveSeeder).
        $this->assertDatabaseMissing('users', ['email' => 'admin@ereve.cd']);
        $this->assertSame(1, User::count());
        $this->assertSame(0, Church::count());
    }
}
