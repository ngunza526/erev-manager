<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

class MinistryOperationsTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    public function test_seed_creates_church_central_style_ministry_operations(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertDatabaseHas('church_services', ['title' => 'Culte dominical principal']);
        $this->assertDatabaseHas('ministry_groups', ['name' => 'Cellule Golf Lumiere']);
        $this->assertDatabaseHas('church_events', ['title' => 'Conference de reveil']);
        $this->assertDatabaseHas('budgets', ['name' => 'Budget fonctionnement annuel']);
        $this->assertDatabaseHas('expenses', ['description' => 'Achat brochures evangelisation']);
    }

    public function test_authenticated_user_can_create_service_group_and_event(): void
    {
        $church = $this->church();
        $user = User::factory()->create([
            'status' => 'actif',
            'level' => 'eglise',
            'church_id' => $church->id,
            'community_id' => $church->community_id,
        ]);
        $this->withRoles($user, Rbac::SECRETAIRE);

        $this->actingAs($user)->post('/services', [
            'church_id' => $church->id,
            'title' => 'Culte de jeunes',
            'service_type' => 'jeunesse',
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'attendance_count' => 80,
        ])->assertRedirect();
        $this->assertDatabaseHas('church_services', ['title' => 'Culte de jeunes']);

        $this->actingAs($user)->post('/groupes', [
            'church_id' => $church->id,
            'name' => 'Chorale centrale',
            'group_type' => 'chorale',
            'leader_name' => 'Leader Chorale',
            'members_count' => 35,
        ])->assertRedirect();
        $this->assertDatabaseHas('ministry_groups', ['name' => 'Chorale centrale']);

        $this->actingAs($user)->post('/evenements', [
            'church_id' => $church->id,
            'title' => 'Seminaire leadership',
            'event_type' => 'formation',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'venue' => 'Temple',
            'currency' => 'USD',
            'ticket_price' => 10,
        ])->assertRedirect();
        $this->assertDatabaseHas('church_events', ['title' => 'Seminaire leadership']);
    }

    private function church(): Church
    {
        $community = Community::create([
            'designation' => 'Communaute Test',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-MIN-001',
        ]);

        return Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Test',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);
    }
}
