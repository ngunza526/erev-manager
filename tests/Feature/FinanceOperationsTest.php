<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_expense_creates_accounting_entry(): void
    {
        $church = $this->church();
        $user = User::factory()->create([
            'status' => 'actif',
            'level' => 'eglise',
            'church_id' => $church->id,
            'community_id' => $church->community_id,
        ]);
        ChartOfAccount::create(['code' => '601', 'label' => 'Achats', 'class' => 6, 'normal_side' => 'debit']);
        ChartOfAccount::create(['code' => '511', 'label' => 'Caisse', 'class' => 5, 'normal_side' => 'debit']);

        $this->actingAs($user)->post('/depenses', [
            'church_id' => $church->id,
            'description' => 'Transport mission',
            'vendor' => 'Agence locale',
            'category' => 'mission',
            'currency' => 'USD',
            'amount' => 75,
            'exchange_rate' => 2850,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['description' => 'Transport mission']);
        $this->assertDatabaseHas('journal_entries', ['description' => 'Transport mission', 'type' => 'expense']);
        $this->assertDatabaseHas('journal_entry_lines', ['debit' => 75, 'credit' => 0]);
        $this->assertDatabaseHas('journal_entry_lines', ['debit' => 0, 'credit' => 75]);
    }

    public function test_approved_expense_does_not_create_cash_accounting_entry(): void
    {
        $church = $this->church();
        $user = User::factory()->create([
            'status' => 'actif',
            'level' => 'eglise',
            'church_id' => $church->id,
            'community_id' => $church->community_id,
        ]);
        ChartOfAccount::create(['code' => '601', 'label' => 'Achats', 'class' => 6, 'normal_side' => 'debit']);
        ChartOfAccount::create(['code' => '511', 'label' => 'Caisse', 'class' => 5, 'normal_side' => 'debit']);

        $this->actingAs($user)->post('/depenses', [
            'church_id' => $church->id,
            'description' => 'Depense approuvee a payer',
            'vendor' => 'Agence locale',
            'category' => 'mission',
            'currency' => 'USD',
            'amount' => 75,
            'exchange_rate' => 2850,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'approved',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['description' => 'Depense approuvee a payer', 'journal_entry_id' => null]);
        $this->assertDatabaseMissing('journal_entries', ['description' => 'Depense approuvee a payer', 'type' => 'expense']);
    }

    private function church(): Church
    {
        $community = Community::create([
            'designation' => 'Communaute Finance',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-FIN-001',
        ]);

        return Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Finance',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);
    }
}
