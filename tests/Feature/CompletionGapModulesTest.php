<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\Fund;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletionGapModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_completion_gap_modules(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertDatabaseHas('families', ['household_name' => 'Foyer de demonstration']);
        $this->assertDatabaseHas('discipleship_paths', ['participant_name' => 'Marie Nouvelle']);
        $this->assertDatabaseHas('church_media_items', ['title' => 'Affiche conference de reveil']);
        $this->assertDatabaseHas('funds', ['code' => 'FND-CONSTRUCTION', 'opening_balance' => 0, 'current_balance' => 0]);
        $this->assertDatabaseHas('fund_movements', ['description' => 'Promesse affectee fonds construction', 'status' => 'draft', 'journal_entry_id' => null]);
        $this->assertDatabaseHas('event_registrations', ['ticket_code' => 'EVT-REV-001', 'amount_paid' => 0, 'journal_entry_id' => null]);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_authenticated_user_can_view_completion_gap_pages(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        foreach (['familles', 'discipolat', 'mediatheque', 'fonds-dedies', 'mouvements-fonds', 'inscriptions-evenements'] as $uri) {
            $this->actingAs($user)->get("/{$uri}")->assertOk();
        }
    }

    public function test_fund_and_event_pages_expose_business_select_options(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        $this->actingAs($user)
            ->get('/mouvements-fonds')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('options.funds.0.value')
                ->has('options.funds.0.label')
            );

        $this->actingAs($user)
            ->get('/inscriptions-evenements')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('options.events.0.value')
                ->has('options.events.0.label')
            );
    }

    public function test_event_registration_payment_creates_accounting_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $event = ChurchEvent::where('title', 'Conference de reveil')->firstOrFail();

        $this->actingAs($user)->post('/inscriptions-evenements', [
            'church_id' => $church->id,
            'church_event_id' => $event->id,
            'attendee_name' => 'Sarah Mbuyi',
            'phone' => '+243990000303',
            'ticket_code' => 'EVT-REV-002',
            'currency' => 'CDF',
            'amount_paid' => 20000,
            'exchange_rate' => 2850,
            'payment_method' => 'mobile_money',
            'check_in_status' => 'registered',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_registrations', ['ticket_code' => 'EVT-REV-002', 'church_event_id' => $event->id]);
        $this->assertDatabaseHas('journal_entries', [
            'type' => 'event_registration',
            'description' => 'Inscription evenement Sarah Mbuyi',
        ]);
    }

    public function test_posted_fund_movement_updates_balance_and_creates_accounting_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $fund = Fund::where('code', 'FND-CONSTRUCTION')->firstOrFail();
        $before = (float) $fund->current_balance;

        $this->actingAs($user)->post('/mouvements-fonds', [
            'church_id' => $church->id,
            'fund_id' => $fund->id,
            'movement_type' => 'receipt',
            'source_name' => 'Collecte jeunesse',
            'currency' => 'USD',
            'amount' => 125,
            'exchange_rate' => 2850,
            'movement_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'posted',
            'description' => 'Collecte affectee construction',
        ])->assertRedirect();

        $this->assertDatabaseHas('fund_movements', ['description' => 'Collecte affectee construction']);
        $this->assertDatabaseHas('journal_entries', ['type' => 'fund_movement', 'description' => 'Collecte affectee construction']);
        $this->assertSame($before + 125, (float) $fund->fresh()->current_balance);
    }
}
