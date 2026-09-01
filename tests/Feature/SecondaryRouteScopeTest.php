<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Church;
use App\Models\Community;
use App\Models\CounselingCase;
use App\Models\Member;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\VendorBill;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

class SecondaryRouteScopeTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    public function test_member_status_action_rejects_member_outside_user_scope(): void
    {
        [$user, , $otherChurch] = $this->makeScopedChurches();

        $member = Member::create([
            'church_id' => $otherChurch->id,
            'last_name' => 'Hors',
            'middle_name' => 'Perimetre',
            'first_name' => 'Membre',
            'sex' => 'Masculin',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Lubumbashi',
            'profession' => 'Employe',
            'marital_status' => 'Celibataire',
            'status' => 'sympathisant',
        ]);

        $this->actingAs($user)
            ->patch(route('membres.promote', $member), ['status' => 'actif'])
            ->assertSessionHasErrors('church_id');

        $this->assertSame('sympathisant', $member->fresh()->status->value);
    }

    public function test_child_check_actions_reject_children_outside_user_scope(): void
    {
        [$user, , $otherChurch] = $this->makeScopedChurches();

        $child = Child::create([
            'church_id' => $otherChurch->id,
            'full_name' => 'Enfant hors perimetre',
            'birth_date' => '2018-01-01',
            'guardian_name' => 'Tuteur',
            'check_in_code' => 'SEC-001',
            'checked_in' => false,
        ]);

        $this->actingAs($user)
            ->post(route('children.check-in', $child), ['check_in_code' => 'SEC-001'])
            ->assertSessionHasErrors('church_id');

        $this->assertFalse($child->fresh()->checked_in);

        $child->update(['checked_in' => true]);

        $this->actingAs($user)
            ->post(route('children.check-out', $child), [
                'released_to' => 'Tuteur',
                'check_in_code' => 'SEC-001',
            ])
            ->assertSessionHasErrors('church_id');

        $this->assertNull($child->fresh()->checked_out_at);
    }

    public function test_financial_and_counseling_secondary_actions_reject_records_outside_user_scope(): void
    {
        [$user, , $otherChurch] = $this->makeScopedChurches();

        $vendorBill = VendorBill::create([
            'church_id' => $otherChurch->id,
            'vendor_name' => 'Fournisseur hors perimetre',
            'bill_number' => 'INV-OUT-001',
            'category' => 'fonctionnement',
            'currency' => 'USD',
            'amount' => 150,
            'exchange_rate' => 2850,
            'bill_date' => '2026-07-04',
            'payment_method' => 'bank',
            'status' => 'pending',
        ]);

        $payrollRun = PayrollRun::create([
            'church_id' => $otherChurch->id,
            'period_label' => 'Juillet 2026',
            'staff_name' => 'Staff hors perimetre',
            'role' => 'Agent',
            'currency' => 'USD',
            'gross_amount' => 200,
            'social_charges' => 20,
            'net_amount' => 180,
            'exchange_rate' => 2850,
            'payment_method' => 'bank',
            'status' => 'draft',
        ]);

        $counselingCase = CounselingCase::create([
            'church_id' => $otherChurch->id,
            'case_code' => 'CASE-OUT-001',
            'requester_name' => 'Dossier hors perimetre',
            'care_type' => 'pastoral',
            'confidentiality_level' => 'restreint',
            'status' => 'open',
            'summary' => 'Dossier confidentiel.',
        ]);

        $this->actingAs($user)
            ->post(route('vendor-bills.pay', $vendorBill), ['payment_method' => 'bank'])
            ->assertSessionHasErrors('church_id');

        $this->assertSame('pending', $vendorBill->fresh()->status);
        $this->assertNull($vendorBill->fresh()->journal_entry_id);

        $this->actingAs($user)
            ->post(route('payroll-runs.pay', $payrollRun), [
                'payment_method' => 'bank',
                'paid_at' => '2026-07-04',
            ])
            ->assertSessionHasErrors('church_id');

        $this->assertSame('draft', $payrollRun->fresh()->status);
        $this->assertNull($payrollRun->fresh()->journal_entry_id);

        $this->actingAs($user)
            ->post(route('counseling.schedule', $counselingCase), [
                'appointment_date' => '2026-07-10',
                'assigned_to' => 'Pasteur',
            ])
            ->assertSessionHasErrors('church_id');

        $this->assertSame('open', $counselingCase->fresh()->status);

        $this->actingAs($user)
            ->post(route('counseling.close', $counselingCase), [
                'last_follow_up_note' => 'Cloture interdite hors perimetre.',
            ])
            ->assertSessionHasErrors('church_id');

        $this->assertNull($counselingCase->fresh()->closed_at);
    }

    private function makeScopedChurches(): array
    {
        $community = Community::create([
            'designation' => 'Communaute Scope Secondaire',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-SECONDARY-001',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Scope A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Scope B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::factory()->create([
            'level' => 'eglise',
            'status' => 'actif',
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
        ]);
        $this->withRoles($user, Rbac::ADMIN_FIN, Rbac::SECRETAIRE);

        return [$user, $ownChurch, $otherChurch];
    }
}
