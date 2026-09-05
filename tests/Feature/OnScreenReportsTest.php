<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rapports comptables affiches et imprimables a l'ecran : balance generale,
 * etats financiers et grand livre par compte (demande utilisateur du
 * 2026-09-05 : rapport coherent/exact, mouvements attaches a un compte,
 * balance globale/particuliere et journal de transactions par compte).
 */
class OnScreenReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_the_trial_balance_on_screen(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $this->createReportEntries($user);

        $response = $this->actingAs($user)->get('/rapports/balance');
        $response->assertOk();

        $props = $response->inertiaPage()['props'];
        $this->assertSame('Reports/TrialBalance', $response->inertiaPage()['component']);
        $this->assertNotEmpty($props['accounts']);
        $this->assertEquals(round($props['totals']['debit'], 2), round($props['totals']['credit'], 2));
    }

    public function test_authenticated_user_can_view_financial_statements_on_screen(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $this->createReportEntries($user);

        $response = $this->actingAs($user)->get('/rapports/etats-financiers');
        $response->assertOk();

        $page = $response->inertiaPage();
        $this->assertSame('Reports/FinancialStatements', $page['component']);
        $statements = $page['props']['statements'];

        $this->assertArrayHasKey('balance_sheet', $statements);
        $this->assertArrayHasKey('income_statement', $statements);
        $this->assertArrayHasKey('annexes', $statements);
        $this->assertEquals(0.0, round($statements['balance_sheet']['control_gap'], 2));
    }

    public function test_account_ledger_lists_lines_with_a_running_balance(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $this->createReportEntries($user);

        $caisse = ChartOfAccount::where('code', '511')->firstOrFail();

        $response = $this->actingAs($user)->get("/rapports/grand-livre/{$caisse->id}");
        $response->assertOk();

        $page = $response->inertiaPage();
        $this->assertSame('Reports/AccountLedger', $page['component']);
        $this->assertSame('511', $page['props']['account']['code']);

        $rows = $page['props']['rows'];
        $totals = $page['props']['totals'];

        $this->assertNotEmpty($rows);
        $lastRow = end($rows);
        $this->assertEquals(round($totals['debit'], 2) - round($totals['credit'], 2), round($totals['balance'], 2));
        $this->assertEquals(round($lastRow['balance'], 2), round($totals['balance'], 2));
    }

    public function test_account_ledger_can_be_filtered_by_date_range(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $this->createReportEntries($user);

        $caisse = ChartOfAccount::where('code', '511')->firstOrFail();
        $future = now()->addDay()->toDateString();

        $response = $this->actingAs($user)->get("/rapports/grand-livre/{$caisse->id}?from={$future}");
        $response->assertOk();

        $rows = $response->inertiaPage()['props']['rows'];
        $this->assertEmpty($rows);
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
            'description' => 'Don rapport ecran',
            'created_by' => $user->id,
        ]);

        $accounting->recordBalancedEntry([
            'church_id' => $church->id,
            'type' => 'expense',
            'entry_date' => now()->toDateString(),
            'description' => 'Charge rapport ecran',
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
