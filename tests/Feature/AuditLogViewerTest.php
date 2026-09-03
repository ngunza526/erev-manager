<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use App\Support\Audit;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

/**
 * Journal d'audit en lecture seule (SEC-01, permission audit.view) :
 * gardiennage par permission et perimetre AccessScope.
 */
class AuditLogViewerTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    private Community $community;

    private Church $church;

    private Church $foreignChurch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->community = Community::create([
            'designation' => 'Communaute Audit',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-AUDIT-001',
        ]);

        $this->church = Church::create([
            'community_id' => $this->community->id,
            'designation' => 'Eglise Audit',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $foreignCommunity = Community::create([
            'designation' => 'Communaute Etrangere',
            'headquarters_city' => 'Kinshasa',
            'headquarters_province' => 'Kinshasa',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-AUDIT-002',
        ]);

        $this->foreignChurch = Church::create([
            'community_id' => $foreignCommunity->id,
            'designation' => 'Eglise Etrangere',
            'address_district' => 'Gombe',
            'address_city' => 'Kinshasa',
            'address_province' => 'Kinshasa',
            'address_country' => 'RDC',
        ]);
    }

    private function administrateur(): User
    {
        $user = User::factory()->create([
            'level' => Rbac::LEVEL_COORDINATION,
            'status' => 'actif',
            'church_id' => null,
            'community_id' => $this->community->id,
        ]);

        return $this->withRoles($user, Rbac::ADMINISTRATEUR);
    }

    private function log(string $action, ?int $churchId, ?int $communityId = null): AuditLog
    {
        return AuditLog::create([
            'actor_label' => 'testeur <testeur@ereve.cd>',
            'action' => $action,
            'church_id' => $churchId,
            'community_id' => $communityId,
            'context' => ['k' => 'v'],
        ]);
    }

    public function test_administrateur_sees_only_events_within_its_community(): void
    {
        $this->log('accounting.entry.posted', $this->church->id);
        $this->log('member.promoted', $this->foreignChurch->id);
        $this->log('rbac.role.permissions_synced', null);

        $this->actingAs($this->administrateur())
            ->get('/journal-audit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('AuditLogs/Index')
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'accounting.entry.posted')
                ->where('logs.data.0.church_id', $this->church->id)
            );
    }

    public function test_administrateur_sees_community_level_events_without_church(): void
    {
        // Evenement de niveau coordination (church_id null) rattache a sa communaute.
        $this->log('user.created', null, $this->community->id);
        // Meme type d'evenement pour une autre communaute : invisible.
        $this->log('user.created', null, Church::findOrFail($this->foreignChurch->id)->community_id);
        // Evenement eglise de sa communaute : visible aussi.
        $this->log('member.promoted', $this->church->id);

        $this->actingAs($this->administrateur())
            ->get('/journal-audit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 2)
                ->where('logs.data.0.action', 'member.promoted')
                ->where('logs.data.1.action', 'user.created')
                ->where('logs.data.1.community_id', $this->community->id)
            );
    }

    public function test_the_event_list_is_paginated(): void
    {
        for ($i = 0; $i < 23; $i++) {
            $this->log('auth.login', $this->church->id);
        }

        $admin = $this->administrateur();

        $this->actingAs($admin)
            ->get('/journal-audit?per_page=10')
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 23)
                ->where('logs.per_page', 10)
                ->where('logs.last_page', 3)
                ->where('perPage', 10)
                ->count('logs.data', 10)
            );

        $this->actingAs($admin)
            ->get('/journal-audit?per_page=10&page=3')
            ->assertInertia(fn ($page) => $page
                ->where('logs.current_page', 3)
                ->count('logs.data', 3)
            );

        // per_page hors liste blanche -> valeur par defaut.
        $this->actingAs($admin)
            ->get('/journal-audit?per_page=999')
            ->assertInertia(fn ($page) => $page->where('logs.per_page', 25));
    }

    public function test_audit_helper_backfills_community_from_church(): void
    {
        // church_id connu, aucune entite auditee : la communaute est deduite de l'eglise.
        Audit::record('accounting.entry.posted', null, [], $this->church->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'accounting.entry.posted',
            'church_id' => $this->church->id,
            'community_id' => $this->community->id,
        ]);
    }

    public function test_roles_without_audit_view_permission_are_forbidden(): void
    {
        foreach ([Rbac::SECRETAIRE, Rbac::CAISSIER, Rbac::ADMIN_FIN] as $role) {
            $user = User::factory()->create([
                'level' => Rbac::LEVEL_EGLISE,
                'status' => 'actif',
                'church_id' => $this->church->id,
                'community_id' => $this->community->id,
            ]);
            $this->withRoles($user, $role);

            $this->actingAs($user)->get('/journal-audit')->assertForbidden();
        }
    }

    public function test_action_and_date_filters_narrow_the_result_set(): void
    {
        $this->log('auth.login', $this->church->id);
        $this->log('auth.logout', $this->church->id);
        $old = $this->log('auth.login_failed', $this->church->id);
        DB::table('audit_logs')->where('id', $old->id)->update(['created_at' => now()->subDays(10)]);

        $admin = $this->administrateur();

        $this->actingAs($admin)
            ->get('/journal-audit?action=auth.login_failed')
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'auth.login_failed')
            );

        $this->actingAs($admin)
            ->get('/journal-audit?from='.now()->subDays(2)->toDateString())
            ->assertInertia(fn ($page) => $page->where('logs.total', 2));
    }

    public function test_journal_is_read_only_no_write_route_exists(): void
    {
        $admin = $this->administrateur();

        $this->actingAs($admin)->post('/journal-audit', [])->assertStatus(405);
        $this->actingAs($admin)->put('/journal-audit', [])->assertStatus(405);
        $this->actingAs($admin)->delete('/journal-audit')->assertStatus(405);
    }
}
