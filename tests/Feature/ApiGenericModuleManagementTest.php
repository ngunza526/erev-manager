<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Communication;
use App\Models\Community;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

class ApiGenericModuleManagementTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    public function test_api_can_manage_pastoral_and_administration_modules(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $token = $user->createToken('generic-modules')->plainTextToken;

        $visitorId = $this->withToken($token)
            ->postJson('/api/pastoral/visiteurs', $this->visitorPayload($church->id, 'Visiteur API'))
            ->assertCreated()
            ->assertJsonPath('family', 'pastoral')
            ->assertJsonPath('module', 'visiteurs')
            ->assertJsonPath('data.full_name', 'Visiteur API')
            ->json('data.id');

        $this->withToken($token)
            ->putJson("/api/pastoral/visiteurs/{$visitorId}", $this->visitorPayload($church->id, 'Visiteur API modifie'))
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Visiteur API modifie');

        $this->withToken($token)
            ->getJson('/api/pastoral/visiteurs')
            ->assertOk()
            ->assertJsonFragment(['full_name' => 'Visiteur API modifie']);

        $communicationId = $this->withToken($token)
            ->postJson('/api/administration/communications', $this->communicationPayload($church->id, 'Annonce API'))
            ->assertCreated()
            ->assertJsonPath('family', 'administration')
            ->assertJsonPath('module', 'communications')
            ->assertJsonPath('data.subject', 'Annonce API')
            ->json('data.id');

        $this->withToken($token)
            ->putJson("/api/administration/communications/{$communicationId}", $this->communicationPayload($church->id, 'Annonce API modifiee'))
            ->assertOk()
            ->assertJsonPath('data.subject', 'Annonce API modifiee');

        $this->assertDatabaseHas('visitors', ['id' => $visitorId, 'full_name' => 'Visiteur API modifie']);
        $this->assertDatabaseHas('communications', ['id' => $communicationId, 'subject' => 'Annonce API modifiee']);
    }

    public function test_church_level_api_user_cannot_manage_generic_modules_outside_scope(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();
        $token = $user->createToken('generic-scope')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/administration/communications', $this->communicationPayload($ownChurch->id, 'Annonce locale'))
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/administration/communications', $this->communicationPayload($otherChurch->id, 'Annonce bloquee'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);

        $foreign = Communication::create($this->communicationPayload($otherChurch->id, 'Annonce externe'));

        $this->withToken($token)
            ->putJson("/api/administration/communications/{$foreign->id}", $this->communicationPayload($otherChurch->id, 'Tentative externe'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);
    }

    private function makeScopedChurchUser(): array
    {
        $community = Community::create([
            'designation' => 'Communaute Generic API',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-GENERIC-001',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Generic A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Generic B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::create([
            'name' => 'Generic Scope User',
            'email' => 'generic-scope@example.cd',
            'password' => Hash::make('password'),
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        $this->withRoles($user, Rbac::SECRETAIRE);

        return [$user, $ownChurch, $otherChurch];
    }

    private function visitorPayload(int $churchId, string $name): array
    {
        return [
            'church_id' => $churchId,
            'full_name' => $name,
            'phone' => '+243990123456',
            'email' => 'visiteur.api@example.cd',
            'visit_source' => 'api mobile',
            'visited_at' => now()->toDateString(),
            'follow_up_status' => 'a_relancer',
            'notes' => 'Cree par API generique.',
        ];
    }

    private function communicationPayload(int $churchId, string $subject): array
    {
        return [
            'church_id' => $churchId,
            'channel' => 'whatsapp',
            'audience' => 'membres',
            'subject' => $subject,
            'body' => 'Message cree par API generique.',
            'scheduled_at' => now()->addDay()->toISOString(),
            'status' => 'scheduled',
        ];
    }
}
