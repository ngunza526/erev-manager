<?php

namespace Tests\Feature;

use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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

    public function test_permission_and_role_can_be_created_and_synced(): void
    {
        $this->seed(EreveSeeder::class);
        $user = $this->seededAdministrateur();

        $this->actingAs($user)->post('/roles-permissions/permissions', [
            'name' => 'counseling.confidentiel',
        ])->assertRedirect();

        $this->assertDatabaseHas('permissions', ['name' => 'counseling.confidentiel']);

        $this->actingAs($user)->post('/roles-permissions/roles', [
            'name' => 'Conseiller pastoral',
            'level' => 'eglise',
            'permissions' => ['counseling.confidentiel'],
        ])->assertRedirect();

        $role = Role::where('name', 'Conseiller pastoral')->firstOrFail();
        $this->assertSame('eglise', $role->level);
        $this->assertTrue($role->hasPermissionTo('counseling.confidentiel'));

        Permission::findOrCreate('enfants.checkin');

        $this->actingAs($user)->put("/roles-permissions/roles/{$role->id}/permissions", [
            'permissions' => ['enfants.checkin'],
        ])->assertRedirect();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo('counseling.confidentiel'));
        $this->assertTrue($role->hasPermissionTo('enfants.checkin'));
    }
}
