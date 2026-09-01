<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\Community;
use App\Models\ExchangeRate;
use App\Models\JournalEntryLine;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RdcSpecificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_exposes_rdc_rate_payment_methods_and_complete_addresses(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertTrue(ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'CDF')
            ->whereDate('rated_at', now()->toDateString())
            ->where('source', 'manuel')
            ->exists());
        $this->assertDatabaseHas('payment_methods', ['code' => 'card', 'label' => 'Carte bancaire']);
        $this->assertDatabaseHas('payment_methods', ['code' => 'mobile_money', 'label' => 'Mobile Money']);
        $this->assertDatabaseHas('communities', ['headquarters_number' => '12', 'headquarters_avenue' => 'Avenue Kasavubu', 'headquarters_district' => 'Golf']);
        $this->assertDatabaseHas('churches', ['address_number' => '45', 'address_avenue' => 'Avenue de la Paix', 'address_district' => 'Golf']);
    }

    public function test_forms_share_daily_exchange_rate_and_card_payment_method(): void
    {
        $church = $this->church();
        $user = User::factory()->create([
            'status' => 'actif',
            'level' => 'eglise',
            'church_id' => $church->id,
            'community_id' => $church->community_id,
        ]);
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'CDF',
            'rate' => 2865,
            'rated_at' => now()->toDateString(),
            'source' => 'manuel',
        ]);

        $this->actingAs($user)
            ->get('/depenses')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('rdc.default_exchange_rate', 2865)
                ->where('rdc.payment_methods.card', 'Carte bancaire')
                ->where('rdc.payment_methods.mobile_money', 'Mobile Money')
            );
    }

    public function test_community_and_church_crud_store_complete_rdc_addresses(): void
    {
        $user = User::factory()->create(['status' => 'actif', 'level' => 'coordination']);

        $this->actingAs($user)->post('/communautes', [
            'designation' => 'Communaute Adresse RDC',
            'headquarters_number' => '101',
            'headquarters_avenue' => 'Avenue Lumumba',
            'headquarters_district' => 'Kampemba',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-RDC-ADDR-001',
            'email' => 'adresse@example.cd',
            'website' => 'https://eglise.example.cd',
            'phone' => '+243990000555',
        ])->assertRedirect();

        $community = Community::where('authorization_number', 'AUT-RDC-ADDR-001')->firstOrFail();

        $this->actingAs($user)->post('/eglises', [
            'community_id' => $community->id,
            'designation' => 'Eglise Adresse RDC',
            'address_number' => '12B',
            'address_avenue' => 'Avenue du Temple',
            'address_district' => 'Bel-Air',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
            'email' => 'temple@example.cd',
            'phone' => '+243990000556',
        ])->assertRedirect();

        $this->assertDatabaseHas('communities', [
            'id' => $community->id,
            'headquarters_number' => '101',
            'headquarters_avenue' => 'Avenue Lumumba',
            'headquarters_district' => 'Kampemba',
            'website' => 'https://eglise.example.cd',
        ]);
        $this->assertDatabaseHas('churches', [
            'designation' => 'Eglise Adresse RDC',
            'address_number' => '12B',
            'address_avenue' => 'Avenue du Temple',
            'address_district' => 'Bel-Air',
        ]);
    }

    public function test_card_public_donation_is_accounted_on_bank_account(): void
    {
        $church = $this->church();
        ChartOfAccount::create(['code' => '501', 'label' => 'Banque principale', 'class' => 5, 'normal_side' => 'debit']);
        ChartOfAccount::create(['code' => '703', 'label' => 'Dons recus', 'class' => 7, 'normal_side' => 'credit']);

        $this->post("/public/eglises/{$church->id}/don", [
            'giver_name' => 'Donateur carte',
            'type' => 'don',
            'amount' => 50,
            'currency' => 'USD',
            'exchange_rate' => 2865,
            'payment_method' => 'card',
            'phone' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('journal_entries', ['description' => 'Don public Donateur carte', 'currency' => 'USD']);
        $this->assertTrue(JournalEntryLine::whereHas('account', fn ($query) => $query->where('code', '501'))->where('debit', 50)->exists());
    }

    public function test_api_member_creation_is_forced_to_sympathisant(): void
    {
        $church = $this->church();
        $user = User::create([
            'name' => 'API RDC',
            'email' => 'api-rdc@example.cd',
            'password' => Hash::make('password'),
            'church_id' => $church->id,
            'community_id' => $church->community_id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        $token = $user->createToken('mobile-rdc')->plainTextToken;

        $this->withToken($token)->postJson('/api/members', [
            'church_id' => $church->id,
            'last_name' => 'Kanku',
            'middle_name' => 'Ilunga',
            'first_name' => 'Pierre',
            'sex' => 'Masculin',
            'birth_date' => '1991-02-03',
            'birth_place' => 'Lubumbashi',
            'profession' => 'Entrepreneur',
            'marital_status' => 'Celibataire',
            'status' => 'effectif',
        ])->assertCreated()->assertJsonPath('data.status', 'sympathisant');

        $this->assertDatabaseHas('members', [
            'last_name' => 'Kanku',
            'status' => 'sympathisant',
        ]);
    }

    private function church(): Church
    {
        $community = Community::create([
            'designation' => 'Communaute RDC Test',
            'headquarters_number' => '1',
            'headquarters_avenue' => 'Avenue Test',
            'headquarters_district' => 'Golf',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-RDC-'.strtoupper(bin2hex(random_bytes(3))),
        ]);

        return Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise RDC Test',
            'address_number' => '2',
            'address_avenue' => 'Avenue Test',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);
    }
}
