<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\FacilityBooking;
use App\Models\JournalEntry;
use App\Models\Pledge;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revue du modele finance (2026-09-05) : les reservations de locaux payees
 * et les versements sur promesse de dons doivent, comme tout autre
 * mouvement d'argent de l'application, etre rattaches a une ecriture
 * comptable equilibree (SEC : coherence balance / grand livre).
 */
class EngagementModulesPostAccountingEntriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_paid_facility_booking_posts_a_balanced_journal_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();

        $response = $this->actingAs($user)->post('/reservations-locaux', [
            'church_id' => $church->id,
            'requester_name' => 'Choeur des jeunes',
            'facility_name' => 'Salle de conference',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(3)->toDateTimeString(),
            'fee_currency' => 'USD',
            'fee_amount' => 50,
            'payment_method' => 'bank',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect();

        $booking = FacilityBooking::where('facility_name', 'Salle de conference')->firstOrFail();
        $this->assertNotNull($booking->journal_entry_id);

        $entry = JournalEntry::with('lines')->findOrFail($booking->journal_entry_id);
        $this->assertSame(
            round($entry->lines->sum('debit'), 2),
            round($entry->lines->sum('credit'), 2)
        );
        $this->assertEquals(50.0, round((float) $entry->lines->sum('debit'), 2));
    }

    public function test_an_unpaid_facility_booking_posts_no_journal_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();

        $this->actingAs($user)->post('/reservations-locaux', [
            'church_id' => $church->id,
            'requester_name' => 'Choeur des jeunes',
            'facility_name' => 'Salle non payee',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(3)->toDateTimeString(),
            'fee_currency' => 'USD',
            'fee_amount' => 50,
            'payment_method' => 'bank',
            'payment_status' => 'unpaid',
        ])->assertRedirect();

        $booking = FacilityBooking::where('facility_name', 'Salle non payee')->firstOrFail();
        $this->assertNull($booking->journal_entry_id);
    }

    public function test_a_pledge_payment_posts_a_balanced_journal_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();

        $this->actingAs($user)->post('/promesses-dons', [
            'church_id' => $church->id,
            'donor_name' => 'Famille Kabeya',
            'campaign' => 'Toiture temple central',
            'currency' => 'USD',
            'pledged_amount' => 500,
            'received_amount' => 200,
            'payment_method' => 'mobile_money',
            'status' => 'active',
        ])->assertRedirect();

        $pledge = Pledge::where('campaign', 'Toiture temple central')->firstOrFail();
        $this->assertNotNull($pledge->journal_entry_id);

        $entry = JournalEntry::with('lines')->findOrFail($pledge->journal_entry_id);
        $this->assertSame(
            round($entry->lines->sum('debit'), 2),
            round($entry->lines->sum('credit'), 2)
        );
        $this->assertEquals(200.0, round((float) $entry->lines->sum('debit'), 2));
    }
}
