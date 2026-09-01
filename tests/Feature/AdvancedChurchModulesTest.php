<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\CounselingCase;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\VendorBill;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedChurchModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_advanced_church_modules_without_accounting_entries(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertDatabaseHas('resource_sales', ['item_name' => 'Bible francais courant']);
        $this->assertDatabaseHas('vendor_bills', ['vendor_name' => 'Sono Katanga Services']);
        $this->assertDatabaseHas('payroll_runs', ['period_label' => 'Juin 2026']);
        $this->assertDatabaseHas('bank_reconciliations', ['account_name' => 'Banque principale USD']);
        $this->assertDatabaseHas('church_payouts', ['beneficiary' => 'Coordination provinciale']);
        $this->assertDatabaseHas('counseling_cases', ['case_code' => 'CARE-001']);
        $this->assertDatabaseHas('outreach_campaigns', ['title' => 'Evangelisation quartier Golf']);
        $this->assertDatabaseHas('public_qr_codes', ['short_code' => 'DON-MTS-001']);
        $this->assertDatabaseHas('live_stream_sessions', ['title' => 'Live culte dominical']);
        $this->assertDatabaseHas('ai_tool_requests', ['prompt_title' => 'Annonce campagne evangelisation']);
        $this->assertSame(0, JournalEntry::count());
        $this->assertDatabaseHas('resource_sales', ['item_name' => 'Bible francais courant', 'status' => 'draft', 'journal_entry_id' => null]);
        $this->assertDatabaseHas('vendor_bills', ['vendor_name' => 'Sono Katanga Services', 'status' => 'pending', 'journal_entry_id' => null]);
        $this->assertDatabaseHas('payroll_runs', ['period_label' => 'Juin 2026', 'status' => 'draft', 'journal_entry_id' => null]);
        $this->assertDatabaseHas('church_payouts', ['beneficiary' => 'Coordination provinciale', 'status' => 'pending', 'journal_entry_id' => null]);
    }

    public function test_authenticated_user_can_view_advanced_module_pages(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        foreach (['boutique-ressources', 'fournisseurs', 'paie', 'rapprochements', 'reversements', 'counseling', 'evangelisation', 'qr-publics', 'live-studio', 'outils-ia'] as $uri) {
            $this->actingAs($user)->get("/{$uri}")->assertOk();
        }
    }

    public function test_paid_resource_sale_creates_accounting_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();

        $this->actingAs($user)->post('/boutique-ressources', [
            'church_id' => $church->id,
            'item_name' => 'Manuel de discipolat',
            'buyer_name' => 'Marie Nouvelle',
            'quantity' => 2,
            'currency' => 'CDF',
            'unit_price' => 12000,
            'exchange_rate' => 2850,
            'payment_method' => 'cash',
            'status' => 'paid',
            'sold_at' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('resource_sales', [
            'item_name' => 'Manuel de discipolat',
            'total_amount' => 24000,
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'type' => 'resource_sale',
            'description' => 'Vente boutique Manuel de discipolat',
            'currency' => 'CDF',
        ]);
    }

    public function test_pending_vendor_bill_can_be_paid_later_with_accounting_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $bill = VendorBill::create([
            'church_id' => $church->id,
            'vendor_name' => 'Imprimerie locale',
            'bill_number' => 'FAC-IMP-002',
            'category' => 'communication',
            'currency' => 'USD',
            'amount' => 75,
            'exchange_rate' => 2850,
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'payment_method' => 'bank',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post("/fournisseurs/{$bill->id}/payer", [
            'payment_method' => 'bank',
        ])->assertRedirect();

        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertNotNull($bill->journal_entry_id);
        $this->assertDatabaseHas('journal_entries', ['id' => $bill->journal_entry_id, 'type' => 'vendor_bill']);
    }

    public function test_draft_payroll_can_be_paid_later_with_social_charge_entry(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $payroll = PayrollRun::create([
            'church_id' => $church->id,
            'period_label' => 'Juillet 2026',
            'staff_name' => 'Comptable adjoint',
            'role' => 'Finance',
            'currency' => 'USD',
            'gross_amount' => 400,
            'social_charges' => 40,
            'net_amount' => 360,
            'exchange_rate' => 2850,
            'payment_method' => 'bank',
            'status' => 'draft',
        ]);

        $this->actingAs($user)->post("/paie/{$payroll->id}/payer", [
            'payment_method' => 'bank',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $payroll->refresh();
        $this->assertSame('paid', $payroll->status);
        $this->assertNotNull($payroll->journal_entry_id);
        $this->assertDatabaseHas('journal_entries', ['id' => $payroll->journal_entry_id, 'type' => 'payroll']);
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $payroll->journal_entry_id, 'label' => 'Charges sociales a payer', 'credit' => 40]);
    }

    public function test_counseling_case_can_be_scheduled_and_closed_confidentially(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $case = CounselingCase::where('case_code', 'CARE-001')->firstOrFail();

        $this->actingAs($user)->post("/counseling/{$case->id}/planifier", [
            'appointment_date' => now()->addDays(2)->toDateString(),
            'next_follow_up_at' => now()->addDays(9)->toDateString(),
            'assigned_to' => 'Pasteur assistant',
            'last_follow_up_note' => 'Premier rendez-vous planifie avec discretion.',
        ])->assertRedirect();

        $case->refresh();
        $this->assertSame('scheduled', $case->status);
        $this->assertSame('Pasteur assistant', $case->assigned_to);
        $this->assertNotNull($case->next_follow_up_at);

        $this->actingAs($user)->post("/counseling/{$case->id}/cloturer", [
            'last_follow_up_note' => 'Accompagnement termine, dossier archive confidentiellement.',
        ])->assertRedirect();

        $case->refresh();
        $this->assertSame('closed', $case->status);
        $this->assertNotNull($case->closed_at);
        $this->assertSame('Accompagnement termine, dossier archive confidentiellement.', $case->last_follow_up_note);
    }

    public function test_closed_counseling_case_cannot_be_rescheduled(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $case = CounselingCase::where('case_code', 'CARE-001')->firstOrFail();
        $case->update(['status' => 'closed', 'closed_at' => now()]);

        $this->actingAs($user)->post("/counseling/{$case->id}/planifier", [
            'appointment_date' => now()->addDays(2)->toDateString(),
            'next_follow_up_at' => now()->addDays(9)->toDateString(),
            'assigned_to' => 'Pasteur assistant',
            'last_follow_up_note' => 'Tentative de replanification.',
        ])->assertInvalid(['counseling_case']);
    }
}
