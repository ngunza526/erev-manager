<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Accounting\FinancialStatementService;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_trial_balance_pdf_and_excel(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        $this->actingAs($user)
            ->get('/rapports/balance.pdf')
            ->assertOk();

        $this->actingAs($user)
            ->get('/rapports/balance.xlsx')
            ->assertOk();
    }

    public function test_authenticated_user_can_download_ohada_financial_statements(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        $this->actingAs($user)
            ->get('/rapports/etats-ohada.pdf')
            ->assertOk();

        $this->actingAs($user)
            ->get('/rapports/etats-ohada.xlsx')
            ->assertOk();
    }

    public function test_ohada_financial_statement_totals_are_calculated_from_posted_entries(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $this->createReportEntries($user);

        $statements = app(FinancialStatementService::class)->statements($user);

        $this->assertGreaterThan(0, $statements['income_statement']['revenues_total']);
        $this->assertGreaterThan(0, $statements['income_statement']['expenses_total']);
        $this->assertSame(
            round($statements['income_statement']['revenues_total'] - $statements['income_statement']['expenses_total'], 2),
            $statements['income_statement']['net_result']
        );
        $this->assertArrayHasKey('assets_total', $statements['balance_sheet']);
        $this->assertArrayHasKey('liabilities_total', $statements['balance_sheet']);
    }

    public function test_ohada_annexes_are_generated_from_scoped_account_balances(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $this->createReportEntries($user);

        $statements = app(FinancialStatementService::class)->statements($user);

        foreach (['cash_and_bank', 'restricted_funds', 'receivables', 'payables', 'control'] as $annex) {
            $this->assertArrayHasKey($annex, $statements['annexes']);
        }

        $this->assertGreaterThan(0, $statements['annexes']['cash_and_bank']['total']);
        $this->assertSame(
            $statements['balance_sheet']['assets_total'],
            $statements['annexes']['control']['assets_total']
        );
        $this->assertSame(
            $statements['balance_sheet']['liabilities_total'],
            $statements['annexes']['control']['liabilities_total']
        );
        $this->assertSame(
            $statements['balance_sheet']['control_gap'],
            $statements['annexes']['control']['control_gap']
        );
    }

    private function createReportEntries(User $user): void
    {
        $church = Church::firstOrFail();
        $accounting = app(AccountingService::class);

        $accounting->recordCollection([
            'church_id' => $church->id,
            'type' => 'don',
            'amount' => 1000,
            'currency' => 'USD',
            'exchange_rate' => 2850,
            'description' => 'Don rapport OHADA',
            'created_by' => $user->id,
        ]);

        $accounting->recordBalancedEntry([
            'church_id' => $church->id,
            'type' => 'expense',
            'entry_date' => now()->toDateString(),
            'description' => 'Charge rapport OHADA',
            'currency' => 'USD',
            'exchange_rate' => 2850,
            'created_by' => $user->id,
            'lines' => [
                ['account_code' => '601', 'label' => 'Charge rapport', 'debit' => 300, 'credit' => 0],
                ['account_code' => '511', 'label' => 'Paiement caisse', 'debit' => 0, 'credit' => 300],
            ],
        ]);
    }
}
