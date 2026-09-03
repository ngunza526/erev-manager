<?php

namespace Tests\Feature;

use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_role_permission_page(): void
    {
        $this->seed(EreveSeeder::class);
        $user = $this->seededAdministrateur();

        $this->actingAs($user)->get('/roles-permissions')->assertOk();
    }

    public function test_a_role_can_be_created_and_its_permissions_synced(): void
    {
        $this->seed(EreveSeeder::class);
        $user = $this->seededAdministrateur();

        // Les permissions ne sont plus creees via l'UI : on s'appuie sur celles
        // du referentiel, deja presentes apres le seed.
        $this->actingAs($user)->post('/roles-permissions/roles', [
            'name' => 'Conseiller pastoral',
            'level' => 'eglise',
            'permissions' => [Rbac::MEMBERS_MANAGE],
        ])->assertRedirect();

        $role = Role::where('name', 'Conseiller pastoral')->firstOrFail();
        $this->assertSame('eglise', $role->level);
        $this->assertTrue($role->hasPermissionTo(Rbac::MEMBERS_MANAGE));

        $this->actingAs($user)->put("/roles-permissions/roles/{$role->id}/permissions", [
            'permissions' => [Rbac::SERVICES_MANAGE],
        ])->assertRedirect();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo(Rbac::MEMBERS_MANAGE));
        $this->assertTrue($role->hasPermissionTo(Rbac::SERVICES_MANAGE));
    }

    public function test_the_permission_creation_route_no_longer_exists(): void
    {
        $this->seed(EreveSeeder::class);

        $this->actingAs($this->seededAdministrateur())
            ->post('/roles-permissions/permissions', ['name' => 'test.manuel'])
            ->assertNotFound();

        $this->assertDatabaseMissing('permissions', ['name' => 'test.manuel']);
    }
}
