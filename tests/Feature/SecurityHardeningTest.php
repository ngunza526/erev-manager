<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Couverture des correctifs de l'audit securite (SEC-20 a SEC-25).
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush(); // isole les limiteurs de debit des autres tests
    }

    protected function tearDown(): void
    {
        cache()->flush();
        parent::tearDown();
    }

    private function activeUser(string $email = 'user@ereve.cd'): User
    {
        return User::create([
            'name' => 'Utilisateur',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'actif',
            'level' => 'coordination',
        ]);
    }

    // --- SEC-20 -------------------------------------------------------------

    public function test_login_is_rate_limited(): void
    {
        $this->activeUser('brute@ereve.cd');

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'brute@ereve.cd', 'password' => 'mauvais'])
                ->assertStatus(302);
        }

        $this->post('/login', ['email' => 'brute@ereve.cd', 'password' => 'mauvais'])
            ->assertStatus(429);
    }

    public function test_api_token_endpoint_is_rate_limited(): void
    {
        $this->activeUser('apibrute@ereve.cd');

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/token', ['email' => 'apibrute@ereve.cd', 'password' => 'mauvais'])
                ->assertStatus(422);
        }

        $this->postJson('/api/auth/token', ['email' => 'apibrute@ereve.cd', 'password' => 'mauvais'])
            ->assertStatus(429);
    }

    // --- SEC-21 -----------------------------------------------------------

    public function test_expired_otp_is_rejected(): void
    {
        $user = $this->activeUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('otp.create'));
        $code = session('auth.otp_code');

        $this->travel(config('auth.otp_ttl') + 60)->seconds();

        $this->post('/otp', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_otp_locks_after_max_attempts(): void
    {
        $user = $this->activeUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $realCode = session('auth.otp_code');

        for ($i = 0; $i < (int) config('auth.otp_max_attempts'); $i++) {
            $this->post('/otp', ['code' => '000000'])->assertSessionHasErrors('code');
        }

        // Session OTP purgee : meme le bon code ne passe plus.
        $this->post('/otp', ['code' => $realCode])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertNull(session('auth.pending_user_id'));
    }

    public function test_otp_code_is_not_disclosed_when_demo_mode_is_off(): void
    {
        config(['auth.otp_demo' => false]);
        $user = $this->activeUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('otp.create'))
            ->assertSessionHas('success', fn ($message) => is_string($message)
                && ! str_contains($message, 'demonstration')
                && ! preg_match('/\b\d{6}\b/', $message));
    }

    // --- SEC-22 -----------------------------------------------------------

    public function test_media_upload_rejects_dangerous_extension(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/media/uploads', [
            'church_id' => $church->id,
            'title' => 'Piege',
            'media_type' => 'document',
            'category' => 'sermon',
            'original_filename' => 'shell.php',
            'total_chunks' => 1,
        ])->assertJsonValidationErrors(['original_filename']);

        $this->withToken($token)->postJson('/api/media/uploads', [
            'church_id' => $church->id,
            'title' => 'Piege',
            'media_type' => 'executable',
            'category' => 'sermon',
            'original_filename' => 'clip.mp4',
            'total_chunks' => 1,
        ])->assertJsonValidationErrors(['media_type']);
    }

    // --- SEC-27 -----------------------------------------------------------

    public function test_public_donation_ignores_client_exchange_rate(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();

        $this->post("/public/eglises/{$church->id}/don", [
            'giver_name' => 'Tricheur',
            'type' => 'don',
            'amount' => 1000,
            'currency' => 'CDF',
            'exchange_rate' => 99999, // ignore : le serveur impose le taux du jour (2850)
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->assertDatabaseHas('public_contributions', [
            'contributor_name' => 'Tricheur',
            'currency' => 'CDF',
            'exchange_rate' => 2850,
            'status' => 'pending',
        ]);
    }

    public function test_public_donation_amount_is_capped(): void
    {
        $this->seed(EreveSeeder::class);
        $church = Church::firstOrFail();

        $this->post("/public/eglises/{$church->id}/don", [
            'giver_name' => 'Gros montant',
            'type' => 'don',
            'amount' => 999999,
            'currency' => 'USD',
            'payment_method' => 'bank',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('public_contributions', ['contributor_name' => 'Gros montant']);
    }

    // --- SEC-28 -----------------------------------------------------------

    public function test_offline_sync_manual_entry_requires_accounting_post(): void
    {
        $this->seed(EreveSeeder::class);
        // Le Caissier a offline.sync mais pas accounting.post.
        $caissier = User::where('email', 'caissier@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();

        $response = $this->actingAs($caissier)->postJson('/offline/sync', [
            'device_id' => 'dev-sec28',
            'client_batch_id' => 'batch-sec28',
            'church_id' => $church->id,
            'records' => [[
                'client_id' => 'je-1',
                'type' => 'manual_journal_entry',
                'payload' => [
                    'entry_date' => now()->toDateString(),
                    'description' => 'Tentative escalade offline',
                    'currency' => 'USD',
                    'exchange_rate' => 2850,
                    'lines' => [
                        ['account_code' => '511', 'label' => 'x', 'debit' => 10, 'credit' => 0],
                        ['account_code' => '703', 'label' => 'y', 'debit' => 0, 'credit' => 10],
                    ],
                ],
            ]],
        ]);

        $response->assertJsonPath('processed_count', 0)->assertJsonCount(1, 'conflicts');
        $this->assertDatabaseMissing('journal_entries', ['description' => 'Tentative escalade offline']);
    }

    // --- SEC-24 -----------------------------------------------------------

    public function test_security_headers_are_present(): void
    {
        $this->get('/login')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // --- SEC-25 -----------------------------------------------------------

    public function test_weak_password_is_rejected_on_user_creation(): void
    {
        $this->seed(EreveSeeder::class);
        $admin = $this->seededAdministrateur();

        $this->actingAs($admin)->post('/utilisateurs', [
            'name' => 'Faible',
            'email' => 'faible@ereve.cd',
            'password' => 'court',
            'level' => 'eglise',
            'church_id' => Church::firstOrFail()->id,
            'role' => Rbac::SECRETAIRE,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'faible@ereve.cd']);
    }
}
