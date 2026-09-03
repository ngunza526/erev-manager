<?php

namespace Tests\Feature;

use App\Support\RoutePermissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * permissions:sync — les permissions sont creees cote back-end a partir des
 * routes (middleware permission:), plus jamais via un formulaire.
 */
class SyncPermissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_permission_referenced_by_a_new_route(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Route::get('/_test/nouvelle-surface', fn () => 'ok')->middleware('permission:reports.newthing');

        $this->assertContains('reports.newthing', RoutePermissions::referenced());
        $this->assertDatabaseMissing('permissions', ['name' => 'reports.newthing']);

        $this->artisan('permissions:sync')->assertSuccessful();

        $this->assertDatabaseHas('permissions', ['name' => 'reports.newthing', 'guard_name' => 'web']);
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $before = Permission::count();

        $this->artisan('permissions:sync')->assertSuccessful();

        $this->assertSame($before, Permission::count());
    }
}
