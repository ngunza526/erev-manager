<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\PublicContribution;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

/**
 * SEC-27 — File d'attente des contributions publiques : validation / rejet,
 * gardiennage par permission et par perimetre.
 */
class PublicContributionWorkflowTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    private function agent(Church $church, string $role = Rbac::CAISSIER): User
    {
        $user = User::factory()->create([
            'status' => 'actif',
            'level' => 'eglise',
            'church_id' => $church->id,
            'community_id' => $church->community_id,
        ]);

        return $this->withRoles($user, $role);
    }

    private function submitDonation(Church $church, string $name = 'Donateur'): PublicContribution
    {
        $this->post("/public/eglises/{$church->id}/don", [
            'giver_name' => $name,
            'type' => 'don',
            'amount' => 40,
            'currency' => 'USD',
            'payment_method' => 'cash',
        ])->assertRedirect();

        return PublicContribution::where('contributor_name', $name)->firstOrFail();
    }

    public function test_agent_sees_the_pending_queue(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();
        $this->submitDonation($church, 'Pending Un');

        $this->actingAs($this->agent($church))
            ->get('/contributions-publiques')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PublicContributions/Index')
                ->where('pending.0.contributor_name', 'Pending Un')
                ->where('pending.0.status', 'pending')
            );
    }

    public function test_validation_posts_the_journal_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();
        $contribution = $this->submitDonation($church);

        $this->actingAs($this->agent($church))
            ->post("/contributions-publiques/{$contribution->id}/valider")
            ->assertRedirect();

        $contribution->refresh();
        $this->assertSame('validated', $contribution->status);
        $this->assertNotNull($contribution->journal_entry_id);
        $this->assertDatabaseHas('journal_entries', ['id' => $contribution->journal_entry_id, 'type' => 'don']);
    }

    public function test_validating_an_event_contribution_links_the_registration(): void
    {
        $this->seed(EreveSeeder::class);
        $event = ChurchEvent::where('title', 'Conference de reveil')->firstOrFail();

        $this->post("/public/evenements/{$event->id}", [
            'attendee_name' => 'Payeur',
            'amount_paid' => 15,
            'currency' => 'USD',
            'payment_method' => 'bank',
        ])->assertRedirect();

        $contribution = PublicContribution::where('kind', 'event_registration')->firstOrFail();

        $this->actingAs($this->agent(Church::findOrFail($event->church_id)))
            ->post("/contributions-publiques/{$contribution->id}/valider")
            ->assertRedirect();

        $contribution->refresh();
        $this->assertSame('validated', $contribution->status);
        $this->assertDatabaseHas('event_registrations', [
            'id' => $contribution->event_registration_id,
            'journal_entry_id' => $contribution->journal_entry_id,
        ]);
    }

    public function test_rejection_records_a_reason_and_posts_nothing(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();
        $contribution = $this->submitDonation($church);

        $this->actingAs($this->agent($church))
            ->post("/contributions-publiques/{$contribution->id}/rejeter", ['note' => 'Doublon'])
            ->assertRedirect();

        $contribution->refresh();
        $this->assertSame('rejected', $contribution->status);
        $this->assertSame('Doublon', $contribution->review_note);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_a_processed_contribution_cannot_be_processed_again(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();
        $contribution = $this->submitDonation($church);
        $agent = $this->agent($church);

        $this->actingAs($agent)->post("/contributions-publiques/{$contribution->id}/valider")->assertRedirect();
        $this->actingAs($agent)->post("/contributions-publiques/{$contribution->id}/rejeter")->assertStatus(422);
    }

    public function test_agent_cannot_validate_a_contribution_of_another_church(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();
        $contribution = $this->submitDonation($church);

        $foreign = Church::create([
            'community_id' => $church->community_id,
            'designation' => 'Eglise Etrangere PC',
            'address_district' => 'X',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $this->actingAs($this->agent($foreign))
            ->post("/contributions-publiques/{$contribution->id}/valider")
            ->assertSessionHasErrors('church_id');

        $this->assertDatabaseHas('public_contributions', ['id' => $contribution->id, 'status' => 'pending']);
    }

    public function test_permission_is_required(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();
        $contribution = $this->submitDonation($church);

        // Auditeur : aucune permission contributions.record.
        $this->actingAs($this->agent($church, Rbac::AUDITEUR))
            ->get('/contributions-publiques')
            ->assertForbidden();

        $this->actingAs($this->agent($church, Rbac::AUDITEUR))
            ->post("/contributions-publiques/{$contribution->id}/valider")
            ->assertForbidden();
    }
}
