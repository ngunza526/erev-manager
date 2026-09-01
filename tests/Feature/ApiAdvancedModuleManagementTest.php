<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Community;
use App\Models\Fund;
use App\Models\JournalEntry;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

class ApiAdvancedModuleManagementTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    public function test_api_advanced_resource_sale_creates_revenue_entry(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $token = $user->createToken('advanced-resource')->plainTextToken;
        $before = JournalEntry::count();

        $response = $this->withToken($token)
            ->postJson('/api/advanced/boutique-ressources', [
                'church_id' => $church->id,
                'item_name' => 'Livre API',
                'buyer_name' => 'Acheteur API',
                'quantity' => 2,
                'currency' => 'CDF',
                'unit_price' => 12000,
                'exchange_rate' => 2850,
                'payment_method' => 'mobile_money',
                'status' => 'paid',
                'sold_at' => now()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('family', 'advanced')
            ->assertJsonPath('module', 'boutique-ressources')
            ->assertJsonPath('data.total_amount', '24000.00');

        $this->assertSame($before + 1, JournalEntry::count());
        $this->assertDatabaseHas('resource_sales', [
            'id' => $response->json('data.id'),
            'journal_entry_id' => $response->json('data.journal_entry_id'),
            'total_amount' => 24000,
        ]);

        $saleId = $response->json('data.id');
        $this->withToken($token)
            ->putJson("/api/advanced/boutique-ressources/{$saleId}", [
                'church_id' => $church->id,
                'item_name' => 'Livre API modifie',
                'buyer_name' => 'Acheteur API',
                'quantity' => 2,
                'currency' => 'CDF',
                'unit_price' => 12000,
                'exchange_rate' => 2850,
                'payment_method' => 'mobile_money',
                'status' => 'paid',
                'sold_at' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.item_name', 'Livre API modifie');
    }

    public function test_api_advanced_fund_movement_updates_fund_and_creates_entry(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $fund = Fund::firstOrFail();
        $token = $user->createToken('advanced-fund')->plainTextToken;
        $beforeBalance = (float) $fund->current_balance;
        $beforeEntries = JournalEntry::count();

        $this->withToken($token)
            ->postJson('/api/advanced/mouvements-fonds', [
                'church_id' => $church->id,
                'fund_id' => $fund->id,
                'movement_type' => 'receipt',
                'source_name' => 'Donateur API',
                'currency' => 'USD',
                'amount' => 75,
                'exchange_rate' => 2850,
                'movement_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'status' => 'posted',
                'description' => 'Don fonds API',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', '75.00');

        $this->assertSame($beforeEntries + 1, JournalEntry::count());
        $this->assertSame($beforeBalance + 75, (float) $fund->fresh()->current_balance);
    }

    public function test_church_user_cannot_manage_advanced_module_outside_scope_or_foreign_fund(): void
    {
        $this->seed(EreveSeeder::class);
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();
        $token = $user->createToken('advanced-scope')->plainTextToken;
        $ownFund = Fund::create($this->fundPayload($ownChurch->id, 'OWN-FUND'));
        $otherFund = Fund::create($this->fundPayload($otherChurch->id, 'OTHER-FUND'));

        $this->withToken($token)
            ->postJson('/api/advanced/mouvements-fonds', $this->fundMovementPayload($ownChurch->id, $ownFund->id))
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/advanced/mouvements-fonds', $this->fundMovementPayload($otherChurch->id, $otherFund->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);

        $this->withToken($token)
            ->postJson('/api/advanced/mouvements-fonds', $this->fundMovementPayload($ownChurch->id, $otherFund->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fund_id']);
    }

    private function makeScopedChurchUser(): array
    {
        $community = Community::create([
            'designation' => 'Communaute Advanced API',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-ADV-001',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Advanced A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Advanced B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::create([
            'name' => 'Advanced Scope User',
            'email' => 'advanced-scope@example.cd',
            'password' => Hash::make('password'),
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        $this->withRoles($user, Rbac::ADMIN_FIN);

        return [$user, $ownChurch, $otherChurch];
    }

    private function fundPayload(int $churchId, string $code): array
    {
        return [
            'church_id' => $churchId,
            'code' => $code,
            'name' => $code,
            'restriction_type' => 'affecte',
            'currency' => 'USD',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ];
    }

    private function fundMovementPayload(int $churchId, int $fundId): array
    {
        return [
            'church_id' => $churchId,
            'fund_id' => $fundId,
            'movement_type' => 'receipt',
            'source_name' => 'Source API',
            'currency' => 'USD',
            'amount' => 20,
            'exchange_rate' => 2850,
            'movement_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'posted',
            'description' => 'Mouvement API',
        ];
    }
}
