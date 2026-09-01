<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementAdministrationModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_engagement_and_administration_modules(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertDatabaseHas('communications', ['subject' => 'Annonce culte special']);
        $this->assertDatabaseHas('service_requests', ['requester_name' => 'Famille Ilunga']);
        $this->assertDatabaseHas('facility_bookings', ['facility_name' => 'Salle polyvalente']);
        $this->assertDatabaseHas('church_assets', ['asset_code' => 'ACT-AUDIO-001']);
        $this->assertDatabaseHas('board_meetings', ['title' => 'Conseil paroissial mensuel']);
        $this->assertDatabaseHas('pledges', ['campaign' => 'Construction annexe enfants']);
        $this->assertDatabaseHas('surveys', ['title' => 'Feedback culte dominical']);
        $this->assertDatabaseHas('testimonies', ['author_name' => 'Membre temoignant']);
    }

    public function test_authenticated_user_can_view_engagement_and_administration_pages(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        foreach (['communications', 'demandes-service', 'reservations-locaux', 'patrimoine', 'conseils-reunions', 'promesses-dons', 'sondages', 'temoignages'] as $uri) {
            $this->actingAs($user)->get("/{$uri}")->assertOk();
        }
    }
}
