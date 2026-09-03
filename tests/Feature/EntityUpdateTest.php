<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\Church;
use App\Models\Community;
use App\Models\Member;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

/**
 * Edition des entites principales : communaute, eglise, membre, compte
 * utilisateur. Verifie l'ecriture, le journal d'audit et le perimetre.
 */
class EntityUpdateTest extends TestCase
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
            'designation' => 'Communaute A',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-UPD-A',
        ]);

        $this->church = Church::create([
            'community_id' => $this->community->id,
            'designation' => 'Eglise A',
            'address_district' => 'Golf', 'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga', 'address_country' => 'RDC',
        ]);

        $foreignCommunity = Community::create([
            'designation' => 'Communaute B',
            'headquarters_city' => 'Kinshasa', 'headquarters_province' => 'Kinshasa',
            'headquarters_country' => 'RDC', 'authorization_number' => 'AUT-UPD-B',
        ]);

        $this->foreignChurch = Church::create([
            'community_id' => $foreignCommunity->id,
            'designation' => 'Eglise B',
            'address_district' => 'Gombe', 'address_city' => 'Kinshasa',
            'address_province' => 'Kinshasa', 'address_country' => 'RDC',
        ]);
    }

    private function user(string $role, string $level, ?int $churchId, ?int $communityId): User
    {
        $user = User::factory()->create([
            'level' => $level,
            'status' => 'actif',
            'church_id' => $churchId,
            'community_id' => $communityId,
        ]);

        return $this->withRoles($user, $role);
    }

    private function administrateur(): User
    {
        return $this->user(Rbac::ADMINISTRATEUR, Rbac::LEVEL_COORDINATION, null, $this->community->id);
    }

    private function secretaire(?int $churchId = null): User
    {
        return $this->user(Rbac::SECRETAIRE, Rbac::LEVEL_EGLISE, $churchId ?? $this->church->id, $this->community->id);
    }

    private function memberPayload(int $churchId): array
    {
        return [
            'church_id' => $churchId,
            'last_name' => 'Kabeya', 'middle_name' => 'Ilunga', 'first_name' => 'Jean',
            'sex' => 'Masculin', 'birth_date' => '1990-01-01', 'birth_place' => 'Lubumbashi',
            'profession' => 'Enseignant', 'marital_status' => 'Celibataire',
        ];
    }

    // --- Communaute -------------------------------------------------------

    public function test_platform_superadmin_updates_a_community(): void
    {
        $platform = $this->user(Rbac::SUPERADMIN_PLATEFORME, Rbac::LEVEL_PLATFORM, null, null);

        $this->actingAs($platform)
            ->put("/communautes/{$this->community->id}", [
                'designation' => 'Communaute A renommee',
                'headquarters_city' => 'Lubumbashi',
                'headquarters_province' => 'Haut-Katanga',
                'headquarters_country' => 'RDC',
                'authorization_number' => 'AUT-UPD-A',
            ])->assertRedirect();

        $this->assertSame('Communaute A renommee', $this->community->fresh()->designation);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reference.community.updated']);
    }

    // --- Eglise ----------------------------------------------------------

    public function test_administrateur_updates_a_church_in_scope(): void
    {
        $this->actingAs($this->administrateur())
            ->put("/eglises/{$this->church->id}", [
                'designation' => 'Eglise A v2',
                'address_district' => 'Golf', 'address_city' => 'Likasi',
                'address_province' => 'Haut-Katanga', 'address_country' => 'RDC',
                'phone' => '+243990000111',
            ])->assertRedirect();

        $this->church->refresh();
        $this->assertSame('Eglise A v2', $this->church->designation);
        $this->assertSame('Likasi', $this->church->address_city);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reference.church.updated']);
    }

    public function test_administrateur_cannot_update_a_church_of_another_community(): void
    {
        $this->actingAs($this->administrateur())
            ->put("/eglises/{$this->foreignChurch->id}", [
                'designation' => 'Piratage',
                'address_district' => 'X', 'address_city' => 'X',
                'address_province' => 'X', 'address_country' => 'RDC',
            ])->assertSessionHasErrors('church_id');

        $this->assertSame('Eglise B', $this->foreignChurch->fresh()->designation);
    }

    // --- Membre --------------------------------------------------------

    public function test_secretaire_updates_a_member_without_touching_status(): void
    {
        $member = Member::create([...$this->memberPayload($this->church->id), 'status' => MemberStatus::Effectif->value]);

        $this->actingAs($this->secretaire())
            ->put("/membres/{$member->id}", [...$this->memberPayload($this->church->id), 'profession' => 'Medecin'])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame('Medecin', $member->profession);
        $this->assertSame(MemberStatus::Effectif->value, $member->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.updated']);
    }

    public function test_secretaire_cannot_update_member_of_another_church(): void
    {
        $member = Member::create([...$this->memberPayload($this->foreignChurch->id), 'status' => MemberStatus::Sympathisant->value]);

        $this->actingAs($this->secretaire())
            ->put("/membres/{$member->id}", [...$this->memberPayload($this->foreignChurch->id), 'profession' => 'Autre'])
            ->assertSessionHasErrors('church_id');
    }

    // --- Compte utilisateur -------------------------------------------

    public function test_administrateur_updates_a_user_account(): void
    {
        $target = $this->user(Rbac::CAISSIER, Rbac::LEVEL_EGLISE, $this->church->id, $this->community->id);

        $this->actingAs($this->administrateur())
            ->put("/utilisateurs/{$target->id}", [
                'name' => 'Compte modifie',
                'email' => $target->email,
                'level' => 'eglise',
                'role' => Rbac::AUDITEUR,
                'status' => 'suspendu',
                'church_id' => $this->church->id,
            ])->assertRedirect();

        $target->refresh();
        $this->assertSame('Compte modifie', $target->name);
        $this->assertSame('suspendu', $target->status);
        $this->assertTrue($target->hasRole(Rbac::AUDITEUR));
        $this->assertFalse($target->hasRole(Rbac::CAISSIER));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.updated']);
    }

    public function test_admin_cannot_suspend_its_own_account(): void
    {
        $admin = $this->administrateur();

        $this->actingAs($admin)
            ->put("/utilisateurs/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'level' => 'coordination',
                'role' => Rbac::ADMINISTRATEUR,
                'status' => 'suspendu',
                'community_id' => $this->community->id,
            ])->assertStatus(422);

        $this->assertSame('actif', $admin->fresh()->status);
    }
}
