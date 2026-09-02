<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\ChurchEvent;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_donation_creates_a_pending_contribution_without_accounting(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();

        $this->get("/public/eglises/{$church->id}/don")->assertOk();

        $this->post("/public/eglises/{$church->id}/don", [
            'giver_name' => 'Donateur public',
            'type' => 'don',
            'amount' => 25000,
            'currency' => 'CDF',
            'payment_method' => 'mobile_money',
            'phone' => '+243990000404',
        ])->assertRedirect();

        // SEC-27 : la contribution attend une validation, rien au grand livre.
        $this->assertDatabaseHas('public_contributions', [
            'church_id' => $church->id,
            'kind' => 'donation',
            'contribution_type' => 'don',
            'contributor_name' => 'Donateur public',
            'currency' => 'CDF',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_public_visitor_page_creates_follow_up_record(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();

        $this->get("/public/eglises/{$church->id}/visiteur")->assertOk();

        $this->post("/public/eglises/{$church->id}/visiteur", [
            'full_name' => 'Invite QR',
            'phone' => '+243990000505',
            'email' => 'invite@example.cd',
            'visit_source' => 'qr accueil',
            'notes' => 'Souhaite etre recontacte.',
        ])->assertRedirect();

        $this->assertDatabaseHas('visitors', [
            'church_id' => $church->id,
            'full_name' => 'Invite QR',
            'follow_up_status' => 'a_relancer',
        ]);
    }

    public function test_public_event_registration_issues_ticket_and_queues_payment_for_validation(): void
    {
        $this->seed(EreveSeeder::class);
        $event = ChurchEvent::where('title', 'Conference de reveil')->firstOrFail();
        $before = $event->registrations_count;

        $this->get("/public/evenements/{$event->id}")->assertOk();

        $this->post("/public/evenements/{$event->id}", [
            'attendee_name' => 'Participant public',
            'phone' => '+243990000606',
            'amount_paid' => 18000,
            'currency' => 'CDF',
            'payment_method' => 'mobile_money',
        ])->assertRedirect();

        // Le billet est emis tout de suite...
        $this->assertDatabaseHas('event_registrations', [
            'church_event_id' => $event->id,
            'attendee_name' => 'Participant public',
            'amount_paid' => 18000,
            'journal_entry_id' => null,
        ]);
        $this->assertSame($before + 1, $event->fresh()->registrations_count);

        // ...mais l'encaissement attend une validation (SEC-27).
        $this->assertDatabaseHas('public_contributions', [
            'church_id' => $event->church_id,
            'kind' => 'event_registration',
            'church_event_id' => $event->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('journal_entries', 0);
    }
}
