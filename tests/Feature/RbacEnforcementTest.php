<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifie que les routes web sont bien gardees par permission (SEC-01) :
 * un utilisateur sans permission recoit 403, le role legitime passe.
 */
class RbacEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Church $church;

    private Community $community;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->community = Community::create([
            'designation' => 'Communaute RBAC',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-RBAC-001',
        ]);

        $this->church = Church::create([
            'community_id' => $this->community->id,
            'designation' => 'Eglise RBAC',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);
    }

    private function user(string $level, ?string $role = null): User
    {
        $user = User::factory()->create([
            'level' => $level,
            'status' => 'actif',
            'church_id' => $level === Rbac::LEVEL_EGLISE ? $this->church->id : null,
            'community_id' => $this->community->id,
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_church_user_without_any_role_is_denied_everywhere(): void
    {
        $user = $this->user(Rbac::LEVEL_EGLISE);

        foreach (['/membres', '/services', '/budgets', '/comptabilite', '/depenses', '/fournisseurs', '/rapprochements', '/plan-comptable', '/visiteurs', '/communications'] as $route) {
            $this->actingAs($user)->get($route)->assertForbidden();
        }
    }

    public function test_secretaire_manages_members_and_services_only(): void
    {
        $user = $this->user(Rbac::LEVEL_EGLISE, Rbac::SECRETAIRE);

        $this->actingAs($user)->get('/membres')->assertOk();
        $this->actingAs($user)->get('/services')->assertOk();

        // Pas d'acces aux surfaces financieres.
        $this->actingAs($user)->get('/comptabilite')->assertForbidden();
        $this->actingAs($user)->post('/comptabilite/ecritures', [])->assertForbidden();
        $this->actingAs($user)->get('/fournisseurs')->assertForbidden();
        $this->actingAs($user)->post('/budgets', [])->assertForbidden();
    }

    public function test_auditeur_is_read_only(): void
    {
        $user = $this->user(Rbac::LEVEL_EGLISE, Rbac::AUDITEUR);

        // Lecture autorisee.
        $this->actingAs($user)->get('/comptabilite')->assertOk();
        $this->actingAs($user)->get('/rapports/balance.pdf')->assertOk();
        $this->actingAs($user)->get('/depenses')->assertOk();

        // Aucune action mutante.
        $this->actingAs($user)->post('/comptabilite/ecritures', [])->assertForbidden();
        $this->actingAs($user)->post('/comptabilite/collectes', [])->assertForbidden();
        $this->actingAs($user)->post('/membres', [])->assertForbidden();
        $this->actingAs($user)->post('/fournisseurs', [])->assertForbidden();
        $this->actingAs($user)->post('/depenses', [])->assertForbidden();
    }

    public function test_caissier_operates_cash_but_cannot_post_general_ledger(): void
    {
        $user = $this->user(Rbac::LEVEL_EGLISE, Rbac::CAISSIER);

        // Le caissier peut initier une requisition et une collecte.
        $this->actingAs($user)->post('/depenses', [])->assertStatus(302); // validation, pas 403
        $this->actingAs($user)->post('/comptabilite/collectes', [])->assertStatus(302);

        // Mais pas passer d'ecriture comptable directe ni configurer le budget.
        $this->actingAs($user)->post('/comptabilite/ecritures', [])->assertForbidden();
        $this->actingAs($user)->post('/budgets', [])->assertForbidden();
        $this->actingAs($user)->get('/rapports/balance.pdf')->assertForbidden();
    }

    public function test_chart_of_accounts_mutations_are_platform_only(): void
    {
        $account = ChartOfAccount::create([
            'code' => '999', 'label' => 'Compte test', 'class' => 6, 'normal_side' => 'debit',
        ]);

        $adminFin = $this->user(Rbac::LEVEL_EGLISE, Rbac::ADMIN_FIN);
        $this->actingAs($adminFin)->get('/plan-comptable')->assertOk();
        $this->actingAs($adminFin)->post('/plan-comptable', [])->assertForbidden();
        $this->actingAs($adminFin)->put("/plan-comptable/{$account->id}", [])->assertForbidden();
        $this->actingAs($adminFin)->delete("/plan-comptable/{$account->id}")->assertForbidden();

        $platform = $this->user(Rbac::LEVEL_PLATFORM, Rbac::SUPERADMIN_PLATEFORME);
        // Le role plateforme franchit la garde (echoue ensuite sur la validation).
        $this->actingAs($platform)->post('/plan-comptable', [])->assertStatus(302);
    }

    public function test_tenant_provisioning_is_platform_only(): void
    {
        $administrateur = $this->user(Rbac::LEVEL_COORDINATION, Rbac::ADMINISTRATEUR);
        $this->actingAs($administrateur)->get('/communautes')->assertForbidden();
        $this->actingAs($administrateur)->get('/eglises')->assertOk();

        $platform = $this->user(Rbac::LEVEL_PLATFORM, Rbac::SUPERADMIN_PLATEFORME);
        $this->actingAs($platform)->get('/communautes')->assertOk();
    }
}
