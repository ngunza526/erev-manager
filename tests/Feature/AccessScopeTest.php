<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\Church;
use App\Models\Community;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_church_user_only_sees_members_and_churches_in_own_scope(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();

        Member::create($this->memberPayload($ownChurch->id, 'Scope', 'Own', 'Member'));
        Member::create($this->memberPayload($otherChurch->id, 'Scope', 'Other', 'Member'));

        $this->actingAs($user)
            ->get('/membres')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('churches.0.id', $ownChurch->id)
                ->missing('churches.1')
                ->where('members.data.0.church_id', $ownChurch->id)
            );
    }

    public function test_church_user_cannot_create_member_in_other_church(): void
    {
        [$user, , $otherChurch] = $this->makeScopedChurchUser();

        $this->actingAs($user)
            ->post('/membres', $this->memberPayload($otherChurch->id, 'Blocked', 'Other', 'Member'))
            ->assertInvalid(['church_id']);
    }

    public function test_dashboard_metrics_are_limited_to_church_scope(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();

        Member::create($this->memberPayload($ownChurch->id, 'Metric', 'Own', 'Member'));
        Member::create($this->memberPayload($otherChurch->id, 'Metric', 'Other', 'Member'));

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.churches', 1)
                ->where('metrics.members', 1)
            );
    }

    private function makeScopedChurchUser(): array
    {
        $community = Community::create([
            'designation' => 'Communaute Scope',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-SCOPE-001',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Scope A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Scope B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::create([
            'name' => 'Scope User',
            'email' => 'scope@example.cd',
            'password' => Hash::make('password'),
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        return [$user, $ownChurch, $otherChurch];
    }

    private function memberPayload(int $churchId, string $lastName, string $middleName, string $firstName): array
    {
        return [
            'church_id' => $churchId,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'first_name' => $firstName,
            'sex' => 'Masculin',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Lubumbashi',
            'profession' => 'Employe',
            'marital_status' => 'Celibataire',
            'spouse' => null,
            'status' => MemberStatus::Sympathisant->value,
        ];
    }
}
