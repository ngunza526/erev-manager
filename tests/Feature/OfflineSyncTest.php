<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\Community;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\OfflineSyncBatch;
use App\Models\User;
use App\Models\Visitor;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    public function test_web_offline_sync_processes_visitors_donations_events_and_manual_entries_idempotently(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $event = ChurchEvent::firstOrFail();
        $entriesBefore = JournalEntry::count();

        $payload = $this->offlinePayload($church->id, $event->id);

        $first = $this->actingAs($user)
            ->postJson('/offline/sync', $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('processed_count', 4)
            ->assertJsonCount(4, 'results');

        $this->assertDatabaseHas('offline_sync_batches', [
            'id' => $first->json('batch_id'),
            'device_id' => 'rdc-tablette-001',
            'client_batch_id' => 'batch-lubumbashi-001',
            'status' => 'synced',
            'processed_count' => 4,
        ]);
        $this->assertDatabaseHas('visitors', ['full_name' => 'Visiteur Offline', 'church_id' => $church->id]);
        $this->assertDatabaseHas('event_registrations', ['attendee_name' => 'Participant Offline', 'church_id' => $church->id]);
        $this->assertDatabaseHas('journal_entries', ['description' => 'Ecriture terrain offline', 'type' => 'manual_offline']);
        $this->assertSame($entriesBefore + 3, JournalEntry::count());

        $this->actingAs($user)
            ->postJson('/offline/sync', $payload)
            ->assertOk()
            ->assertJsonPath('batch_id', $first->json('batch_id'))
            ->assertJsonPath('processed_count', 4);

        $this->assertSame(1, OfflineSyncBatch::where('client_batch_id', 'batch-lubumbashi-001')->count());
        $this->assertSame(1, Visitor::where('full_name', 'Visiteur Offline')->count());
        $this->assertSame($entriesBefore + 3, JournalEntry::count());
    }

    public function test_sanctum_api_offline_sync_can_create_member_from_mobile_queue(): void
    {
        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $token = $user->createToken('mobile-offline')->plainTextToken;

        $this->withToken($token)->postJson('/api/offline/sync', [
            'device_id' => 'mobile-katanga-002',
            'client_batch_id' => 'batch-members-001',
            'church_id' => $church->id,
            'records' => [[
                'client_id' => 'member-001',
                'type' => 'member',
                'payload' => [
                    'last_name' => 'Offline',
                    'middle_name' => 'Nouveau',
                    'first_name' => 'Membre',
                    'sex' => 'Masculin',
                    'birth_date' => '1992-02-02',
                    'birth_place' => 'Likasi',
                    'profession' => 'Commercant',
                    'marital_status' => 'Celibataire',
                ],
            ]],
        ])->assertCreated()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('processed_count', 1)
            ->assertJsonPath('results.0.resource', 'members');

        $member = Member::where('last_name', 'Offline')->firstOrFail();
        $this->assertSame($church->id, $member->church_id);
        $this->assertSame('sympathisant', $member->status->value);
    }

    public function test_offline_sync_idempotency_is_scoped_per_church(): void
    {
        $this->seed(EreveSeeder::class);

        $baseChurch = Church::firstOrFail();
        $otherChurch = $this->makeChurch('Eglise Offline B', 'AUT-OFFLINE-B', $baseChurch->community_id);
        $otherUser = User::factory()->create([
            'name' => 'Offline Eglise B',
            'email' => 'offline-eglise-b@example.cd',
            'level' => 'eglise',
            'church_id' => $otherChurch->id,
            'community_id' => $otherChurch->community_id,
            'status' => 'actif',
        ]);
        $this->withRoles($otherUser, Rbac::CAISSIER);
        $churches = collect([
            [$baseChurch, User::where('email', 'eglise.admin@ereve.cd')->firstOrFail()],
            [$otherChurch, $otherUser],
        ]);

        foreach ($churches as [$church, $user]) {
            $this->actingAs($user)
                ->postJson('/offline/sync', [
                    'device_id' => 'shared-device',
                    'client_batch_id' => 'same-client-batch',
                    'church_id' => $church->id,
                    'records' => [[
                        'client_id' => 'visitor-'.$church->id,
                        'type' => 'visitor',
                        'payload' => [
                            'full_name' => 'Visiteur eglise '.$church->id,
                            'phone' => '+2439900010'.$church->id,
                            'email' => 'visiteur'.$church->id.'@example.cd',
                            'visit_source' => 'accueil offline',
                        ],
                    ]],
                ])
                ->assertCreated()
                ->assertJsonPath('processed_count', 1);
        }

        $this->assertSame(2, OfflineSyncBatch::where('device_id', 'shared-device')->where('client_batch_id', 'same-client-batch')->count());
    }

    public function test_church_user_cannot_replay_or_create_offline_batch_outside_scope(): void
    {
        $this->seed(EreveSeeder::class);

        $ownChurch = Church::firstOrFail();
        $otherChurch = $this->makeChurch('Eglise Offline Hors Scope', 'AUT-OFFLINE-SCOPE');

        $user = User::factory()->create([
            'name' => 'Offline Scope',
            'email' => 'offline-scope@example.cd',
            'level' => 'eglise',
            'church_id' => $ownChurch->id,
            'community_id' => $ownChurch->community_id,
        ]);
        $this->withRoles($user, Rbac::CAISSIER);

        OfflineSyncBatch::create([
            'church_id' => $otherChurch->id,
            'user_id' => User::where('email', 'eglise.admin@ereve.cd')->firstOrFail()->id,
            'device_id' => 'foreign-device',
            'client_batch_id' => 'foreign-batch',
            'payload' => ['results' => [['client_id' => 'hidden', 'resource' => 'visitors', 'server_id' => 999]]],
            'status' => 'synced',
            'processed_count' => 1,
            'synced_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/offline/sync', [
                'device_id' => 'foreign-device',
                'client_batch_id' => 'foreign-batch',
                'church_id' => $otherChurch->id,
                'records' => [[
                    'client_id' => 'visitor-foreign',
                    'type' => 'visitor',
                    'payload' => [
                        'full_name' => 'Visiteur hors perimetre',
                        'visit_source' => 'offline',
                    ],
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);
    }

    private function offlinePayload(int $churchId, int $eventId): array
    {
        return [
            'device_id' => 'rdc-tablette-001',
            'client_batch_id' => 'batch-lubumbashi-001',
            'church_id' => $churchId,
            'records' => [
                [
                    'client_id' => 'visitor-001',
                    'type' => 'visitor',
                    'payload' => [
                        'full_name' => 'Visiteur Offline',
                        'phone' => '+243990001001',
                        'email' => 'offline.visiteur@example.cd',
                        'visit_source' => 'cellule hors ligne',
                        'notes' => 'Synchronise apres retour reseau.',
                    ],
                ],
                [
                    'client_id' => 'donation-001',
                    'type' => 'donation',
                    'payload' => [
                        'giver_name' => 'Donateur Offline',
                        'type' => 'don',
                        'amount' => 25,
                        'currency' => 'USD',
                        'exchange_rate' => 2850,
                        'payment_method' => 'mobile_money',
                    ],
                ],
                [
                    'client_id' => 'event-001',
                    'type' => 'event_registration',
                    'payload' => [
                        'church_event_id' => $eventId,
                        'attendee_name' => 'Participant Offline',
                        'phone' => '+243990001002',
                        'amount_paid' => 10000,
                        'currency' => 'CDF',
                        'exchange_rate' => 2850,
                        'payment_method' => 'mobile_money',
                    ],
                ],
                [
                    'client_id' => 'manual-001',
                    'type' => 'manual_journal_entry',
                    'payload' => [
                        'entry_date' => now()->toDateString(),
                        'description' => 'Ecriture terrain offline',
                        'currency' => 'USD',
                        'exchange_rate' => 2850,
                        'lines' => [
                            ['account_code' => '511', 'label' => 'Caisse terrain', 'debit' => 40, 'credit' => 0],
                            ['account_code' => '703', 'label' => 'Don terrain', 'debit' => 0, 'credit' => 40],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function makeChurch(string $designation, string $authorizationNumber, ?int $communityId = null): Church
    {
        $communityId ??= Community::create([
            'designation' => 'Communaute '.$designation,
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => $authorizationNumber,
        ])->id;

        return Church::create([
            'community_id' => $communityId,
            'designation' => $designation,
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);
    }
}
