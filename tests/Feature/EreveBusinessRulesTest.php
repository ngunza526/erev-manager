<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\ChartOfAccount;
use App\Models\Church;
use App\Models\Community;
use App\Models\Member;
use App\Services\Accounting\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EreveBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_is_created_as_sympathisant(): void
    {
        $church = $this->church();

        $member = Member::create([
            'church_id' => $church->id,
            'last_name' => 'Kabongo',
            'middle_name' => 'Mbuyi',
            'first_name' => 'Jean',
            'sex' => 'Masculin',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Kolwezi',
            'profession' => 'Comptable',
            'marital_status' => 'Celibataire',
            'status' => MemberStatus::Sympathisant->value,
        ]);

        $this->assertSame(MemberStatus::Sympathisant, $member->status);
    }

    public function test_accounting_service_rejects_unbalanced_entries(): void
    {
        $church = $this->church();
        ChartOfAccount::create(['code' => '511', 'label' => 'Caisse', 'class' => 5, 'normal_side' => 'debit']);
        ChartOfAccount::create(['code' => '701', 'label' => 'Dimes', 'class' => 7, 'normal_side' => 'credit']);

        $this->expectException(ValidationException::class);

        app(AccountingService::class)->recordBalancedEntry([
            'church_id' => $church->id,
            'type' => 'manual',
            'description' => 'Ecriture invalide',
            'currency' => 'USD',
            'exchange_rate' => 2850,
            'lines' => [
                ['account_code' => '511', 'label' => 'Caisse', 'debit' => 100, 'credit' => 0],
                ['account_code' => '701', 'label' => 'Dimes', 'debit' => 0, 'credit' => 90],
            ],
        ]);
    }

    public function test_accounting_service_records_balanced_manual_entries(): void
    {
        $church = $this->church();
        ChartOfAccount::create(['code' => '511', 'label' => 'Caisse', 'class' => 5, 'normal_side' => 'debit']);
        ChartOfAccount::create(['code' => '601', 'label' => 'Achats', 'class' => 6, 'normal_side' => 'debit']);

        $entry = app(AccountingService::class)->recordBalancedEntry([
            'church_id' => $church->id,
            'type' => 'manual',
            'description' => 'Achat brochures',
            'currency' => 'USD',
            'exchange_rate' => 2850,
            'lines' => [
                ['account_code' => '601', 'label' => 'Brochures', 'debit' => 100, 'credit' => 0],
                ['account_code' => '511', 'label' => 'Paiement caisse', 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $this->assertCount(2, $entry->lines);
        $this->assertSame('manual', $entry->type);
    }

    private function church(): Church
    {
        $community = Community::create([
            'designation' => 'Communaute Test',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-TEST-001',
        ]);

        return Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Test',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);
    }
}
