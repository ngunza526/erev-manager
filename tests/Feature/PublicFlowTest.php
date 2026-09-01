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

    public function test_public_donation_page_records_accounting_collection(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();

        $this->get("/public/eglises/{$church->id}/don")->assertOk();

        $this->post("/public/eglises/{$church->id}/don", [
            'giver_name' => 'Donateur public',
            'type' => 'don',
            'amount' => 25000,
            'currency' => 'CDF',
            'exchange_rate' => 2850,
            'payment_method' => 'mobile_money',
            'phone' => '+243990000404',
        ])->assertRedirect();

        $this->assertDatabaseHas('journal_entries', [
            'church_id' => $church->id,
            'type' => 'don',
            'description' => 'Don public Donateur public',
            'currency' => 'CDF',
        ]);
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

    public function test_public_event_registration_creates_ticket_and_accounting_entry(): void
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
            'exchange_rate' => 2850,
            'payment_method' => 'mobile_money',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_registrations', [
            'church_event_id' => $event->id,
            'attendee_name' => 'Participant public',
            'amount_paid' => 18000,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'type' => 'event_registration',
            'description' => 'Inscription publique Conference de reveil',
        ]);
        $this->assertSame($before + 1, $event->fresh()->registrations_count);
    }
}
