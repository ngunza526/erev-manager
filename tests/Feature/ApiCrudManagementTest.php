<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\Budget;
use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\ChurchService;
use App\Models\Community;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\MinistryGroup;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiCrudManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_api_can_create_and_update_core_church_modules(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $token = $user->createToken('crud-client')->plainTextToken;

        $memberId = $this->withToken($token)->postJson('/api/members', $this->memberPayload($church->id, 'ApiCore'))
            ->assertCreated()
            ->assertJsonPath('data.last_name', 'ApiCore')
            ->json('data.id');

        $this->withToken($token)->putJson("/api/members/{$memberId}", $this->memberPayload($church->id, 'ApiCoreUpdated'))
            ->assertOk()
            ->assertJsonPath('data.last_name', 'ApiCoreUpdated');

        $serviceId = $this->withToken($token)->postJson('/api/services', $this->servicePayload($church->id))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Culte API')
            ->json('data.id');

        $this->withToken($token)->putJson("/api/services/{$serviceId}", [...$this->servicePayload($church->id), 'title' => 'Culte API modifie'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Culte API modifie');

        $groupId = $this->withToken($token)->postJson('/api/groups', $this->groupPayload($church->id))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Cellule API')
            ->json('data.id');

        $this->withToken($token)->putJson("/api/groups/{$groupId}", [...$this->groupPayload($church->id), 'members_count' => 14])
            ->assertOk()
            ->assertJsonPath('data.members_count', 14);

        $eventId = $this->withToken($token)->postJson('/api/events', $this->eventPayload($church->id))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Conference API')
            ->json('data.id');

        $this->withToken($token)->putJson("/api/events/{$eventId}", [...$this->eventPayload($church->id), 'capacity' => 500])
            ->assertOk()
            ->assertJsonPath('data.capacity', 500);

        $budgetId = $this->withToken($token)->postJson('/api/budgets', $this->budgetPayload($church->id))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Budget API')
            ->json('data.id');

        $this->withToken($token)->putJson("/api/budgets/{$budgetId}", [...$this->budgetPayload($church->id), 'amount' => 900])
            ->assertOk()
            ->assertJsonPath('data.amount', 900);

        $this->assertDatabaseHas('members', ['id' => $memberId, 'last_name' => 'ApiCoreUpdated']);
        $this->assertDatabaseHas('church_services', ['id' => $serviceId, 'title' => 'Culte API modifie']);
        $this->assertDatabaseHas('ministry_groups', ['id' => $groupId, 'members_count' => 14]);
        $this->assertDatabaseHas('church_events', ['id' => $eventId, 'capacity' => 500]);
        $this->assertDatabaseHas('budgets', ['id' => $budgetId, 'amount' => 900]);
    }

    public function test_api_paid_expense_creates_accounting_entry(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $budget = Budget::firstOrFail();
        $token = $user->createToken('expense-client')->plainTextToken;
        $before = JournalEntry::count();

        $response = $this->withToken($token)->postJson('/api/expenses', $this->expensePayload($church->id, $budget->id))
            ->assertCreated()
            ->assertJsonPath('data.description', 'Depense API payee');

        $this->assertSame($before + 1, JournalEntry::count());
        $this->assertDatabaseHas('expenses', [
            'id' => $response->json('data.id'),
            'journal_entry_id' => $response->json('data.journal_entry_id'),
            'status' => 'paid',
        ]);
    }

    public function test_church_level_api_user_cannot_write_outside_own_scope(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();
        $token = $user->createToken('scoped-crud')->plainTextToken;

        $this->withToken($token)->postJson('/api/services', $this->servicePayload($ownChurch->id))
            ->assertCreated();

        $this->withToken($token)->postJson('/api/services', $this->servicePayload($otherChurch->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);

        $foreignService = ChurchService::create($this->servicePayload($otherChurch->id));

        $this->withToken($token)->putJson("/api/services/{$foreignService->id}", $this->servicePayload($otherChurch->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);
    }

    private function makeScopedChurchUser(): array
    {
        $community = Community::create([
            'designation' => 'Communaute API CRUD',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-CRUD-001',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise CRUD A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise CRUD B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::create([
            'name' => 'CRUD Scope User',
            'email' => 'crud-scope@example.cd',
            'password' => Hash::make('password'),
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        return [$user, $ownChurch, $otherChurch];
    }

    private function memberPayload(int $churchId, string $lastName): array
    {
        return [
            'church_id' => $churchId,
            'last_name' => $lastName,
            'middle_name' => 'Mobile',
            'first_name' => 'Api',
            'sex' => 'Masculin',
            'birth_date' => '1991-01-01',
            'birth_place' => 'Lubumbashi',
            'profession' => 'Technicien',
            'marital_status' => 'Celibataire',
            'spouse' => null,
            'status' => MemberStatus::Sympathisant->value,
        ];
    }

    private function servicePayload(int $churchId): array
    {
        return [
            'church_id' => $churchId,
            'title' => 'Culte API',
            'service_type' => 'culte',
            'starts_at' => now()->addWeek()->setTime(9, 0)->toISOString(),
            'ends_at' => now()->addWeek()->setTime(11, 0)->toISOString(),
            'preacher' => 'Pasteur API',
            'worship_leader' => 'Louange API',
            'attendance_count' => 120,
            'notes' => 'Cree via API REST.',
        ];
    }

    private function groupPayload(int $churchId): array
    {
        return [
            'church_id' => $churchId,
            'name' => 'Cellule API',
            'group_type' => 'cellule',
            'leader_name' => 'Leader API',
            'meeting_day' => 'Vendredi',
            'district' => 'Golf',
            'city' => 'Lubumbashi',
            'members_count' => 12,
        ];
    }

    private function eventPayload(int $churchId): array
    {
        return [
            'church_id' => $churchId,
            'title' => 'Conference API',
            'event_type' => 'conference',
            'starts_at' => now()->addMonth()->setTime(16, 0)->toISOString(),
            'ends_at' => now()->addMonth()->setTime(19, 0)->toISOString(),
            'venue' => 'Temple API',
            'currency' => 'CDF',
            'ticket_price' => 0,
            'capacity' => 400,
            'registrations_count' => 0,
            'is_public' => true,
        ];
    }

    private function budgetPayload(int $churchId): array
    {
        return [
            'church_id' => $churchId,
            'name' => 'Budget API',
            'department' => 'Jeunesse',
            'currency' => 'USD',
            'amount' => 750,
            'period_starts_at' => now()->startOfYear()->toDateString(),
            'period_ends_at' => now()->endOfYear()->toDateString(),
            'status' => 'active',
        ];
    }

    private function expensePayload(int $churchId, int $budgetId): array
    {
        return [
            'church_id' => $churchId,
            'budget_id' => $budgetId,
            'description' => 'Depense API payee',
            'vendor' => 'Fournisseur API',
            'category' => 'evangelisation',
            'currency' => 'USD',
            'amount' => 45,
            'exchange_rate' => 2850,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'paid',
        ];
    }
}
