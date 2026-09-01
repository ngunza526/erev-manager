<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkspaceSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_user_only_accesses_community_workspace_routes(): void
    {
        [$communityUser] = $this->makeWorkspaceUsers();

        $this->actingAs($communityUser)->get('/communautes')->assertOk();
        $this->actingAs($communityUser)->get('/eglises')->assertOk();
        $this->actingAs($communityUser)->get('/utilisateurs')->assertOk();
        $this->actingAs($communityUser)->get('/roles-permissions')->assertOk();

        $this->actingAs($communityUser)->get('/membres')->assertForbidden();
        $this->actingAs($communityUser)->get('/services')->assertForbidden();
        $this->actingAs($communityUser)->get('/budgets')->assertForbidden();
        $this->actingAs($communityUser)->get('/communications')->assertForbidden();
    }

    public function test_church_user_only_accesses_church_workspace_routes(): void
    {
        [, $churchUser] = $this->makeWorkspaceUsers();

        $this->actingAs($churchUser)->get('/membres')->assertOk();
        $this->actingAs($churchUser)->get('/services')->assertOk();
        $this->actingAs($churchUser)->get('/budgets')->assertOk();
        $this->actingAs($churchUser)->get('/communications')->assertOk();

        $this->actingAs($churchUser)->get('/communautes')->assertForbidden();
        $this->actingAs($churchUser)->get('/eglises')->assertForbidden();
        $this->actingAs($churchUser)->get('/utilisateurs')->assertForbidden();
        $this->actingAs($churchUser)->get('/roles-permissions')->assertForbidden();
    }

    public function test_dashboard_payload_matches_connected_workspace(): void
    {
        [$communityUser, $churchUser] = $this->makeWorkspaceUsers();

        $this->actingAs($communityUser)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('workspace', 'communaute')
                ->has('metrics.communities')
                ->has('metrics.users')
            );

        $this->actingAs($churchUser)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('workspace', 'eglise')
                ->has('metrics.members')
                ->has('metrics.entries')
            );
    }

    public function test_community_user_can_assign_superadmin_to_a_church(): void
    {
        [$communityUser, , $church] = $this->makeWorkspaceUsers();

        $this->actingAs($communityUser)
            ->post('/utilisateurs', [
                'name' => 'Super Admin Eglise',
                'email' => 'superadmin-eglise@example.cd',
                'password' => 'password',
                'level' => 'eglise',
                'church_id' => $church->id,
                'role' => 'SuperAdmin',
            ])
            ->assertRedirect();

        $user = User::where('email', 'superadmin-eglise@example.cd')->firstOrFail();

        $this->assertSame('eglise', $user->level);
        $this->assertSame($church->id, $user->church_id);
        $this->assertTrue($user->hasRole('SuperAdmin'));
    }

    public function test_superadmin_can_switch_between_community_and_church_contexts(): void
    {
        [, , $church, $otherChurch, $superAdmin] = $this->makeWorkspaceUsers();

        $this->actingAs($superAdmin)->get('/membres')->assertOk();
        $this->actingAs($superAdmin)->get('/communautes')->assertForbidden();

        $this->actingAs($superAdmin)
            ->post('/workspace/switch', [
                'space' => 'communaute',
                'community_id' => $church->community_id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($superAdmin)->get('/communautes')->assertOk();
        $this->actingAs($superAdmin)->get('/membres')->assertForbidden();
        $this->actingAs($superAdmin)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('workspace', 'communaute')
                ->where('auth.space', 'communaute')
            );

        $this->actingAs($superAdmin)
            ->post('/workspace/switch', [
                'space' => 'eglise',
                'church_id' => $otherChurch->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($superAdmin)
            ->get('/membres')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.space', 'eglise')
                ->where('auth.active_church_id', $otherChurch->id)
                ->where('churches.0.id', $otherChurch->id)
                ->missing('churches.1')
            );
    }

    public function test_non_superadmin_cannot_switch_workspace_context(): void
    {
        [, $churchUser, $church] = $this->makeWorkspaceUsers();

        $this->actingAs($churchUser)
            ->post('/workspace/switch', [
                'space' => 'communaute',
                'community_id' => $church->community_id,
            ])
            ->assertForbidden();
    }

    private function makeWorkspaceUsers(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $community = Community::create([
            'designation' => 'Communaute Workspace',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-WORKSPACE-001',
        ]);

        $church = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Workspace',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Workspace B',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $communityUser = User::create([
            'name' => 'User Communaute',
            'email' => 'community-workspace@example.cd',
            'password' => Hash::make('password'),
            'community_id' => $community->id,
            'level' => 'coordination',
            'status' => 'actif',
        ]);

        $churchUser = User::create([
            'name' => 'User Eglise',
            'email' => 'church-workspace@example.cd',
            'password' => Hash::make('password'),
            'community_id' => $community->id,
            'church_id' => $church->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        $superAdmin = User::create([
            'name' => 'SuperAdmin Switch',
            'email' => 'superadmin-switch@example.cd',
            'password' => Hash::make('password'),
            'community_id' => $community->id,
            'church_id' => $church->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);
        $superAdmin->assignRole(Rbac::ADMINISTRATEUR);

        return [$communityUser, $churchUser, $church, $otherChurch, $superAdmin];
    }
}
