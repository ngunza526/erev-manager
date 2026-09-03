<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\VendorBill;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifie que les actions sensibles alimentent le journal d'audit :
 * gestion du referentiel (communaute, eglise, plan comptable) et paiements.
 * Les connexions, la promotion de membre et les ecritures comptables sont
 * couvertes par ailleurs (AuthenticationOtpTest, AuditLogViewerTest, etc.).
 */
class AuditRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EreveSeeder::class);
    }

    public function test_reference_creation_is_audited(): void
    {
        $admin = $this->seededAdministrateur();
        $platform = $this->seededSuperAdmin();
        $community = $admin->community;

        $this->actingAs($admin)->post('/eglises', [
            'community_id' => $community->id,
            'designation' => 'Eglise Audit Ref',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reference.church.created',
            'auditable_type' => Church::class,
            'community_id' => $community->id,
        ]);

        $this->actingAs($platform)->post('/communautes', [
            'designation' => 'Communaute Auditee',
            'headquarters_city' => 'Kinshasa',
            'headquarters_province' => 'Kinshasa',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-AUDIT-REC-001',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'reference.community.created']);

        $this->actingAs($platform)->post('/plan-comptable', [
            'code' => '706100',
            'label' => 'Offrandes speciales',
            'class' => 7,
            'normal_side' => 'credit',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reference.chart_of_account.created',
        ]);
    }

    public function test_chart_of_account_update_and_delete_are_audited(): void
    {
        $platform = $this->seededSuperAdmin();

        $this->actingAs($platform)->post('/plan-comptable', [
            'code' => '706200',
            'label' => 'Dons affectes',
            'class' => 7,
            'normal_side' => 'credit',
        ])->assertRedirect();

        $accountId = ChartOfAccount::where('code', '706200')->value('id');

        $this->actingAs($platform)->put("/plan-comptable/{$accountId}", [
            'code' => '706200',
            'label' => 'Dons affectes (revise)',
            'class' => 7,
            'normal_side' => 'credit',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'reference.chart_of_account.updated']);

        $this->actingAs($platform)->delete("/plan-comptable/{$accountId}")->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'reference.chart_of_account.deleted']);
    }

    public function test_vendor_bill_and_payroll_payments_are_audited(): void
    {
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();

        $bill = VendorBill::create([
            'church_id' => $church->id,
            'vendor_name' => 'Imprimerie Audit',
            'bill_number' => 'FAC-AUD-001',
            'category' => 'communication',
            'currency' => 'USD',
            'amount' => 120,
            'exchange_rate' => 2850,
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'payment_method' => 'bank',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post("/fournisseurs/{$bill->id}/payer", [
            'payment_method' => 'bank',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.vendor_bill.paid',
            'auditable_type' => VendorBill::class,
            'auditable_id' => $bill->id,
            'church_id' => $church->id,
            'community_id' => $church->community_id,
        ]);

        $payroll = PayrollRun::create([
            'church_id' => $church->id,
            'period_label' => 'Juillet 2026',
            'staff_name' => 'Pasteur Audit',
            'role' => 'Pasteur',
            'currency' => 'USD',
            'gross_amount' => 500,
            'social_charges' => 40,
            'net_amount' => 460,
            'exchange_rate' => 2850,
            'payment_method' => 'bank',
            'status' => 'draft',
        ]);

        $this->actingAs($user)->post("/paie/{$payroll->id}/payer", [
            'payment_method' => 'bank',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.payroll_run.paid',
            'auditable_type' => PayrollRun::class,
            'auditable_id' => $payroll->id,
            'church_id' => $church->id,
        ]);
    }
}
