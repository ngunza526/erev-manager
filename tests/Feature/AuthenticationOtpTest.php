<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_logs_in_with_password_and_otp(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@ereve.cd',
            'password' => Hash::make('password'),
            'status' => 'actif',
            'level' => 'coordination',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@ereve.cd',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('otp.create'));
        $code = session('auth.otp_code');

        $this->post('/otp', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_or_inactive_accounts(): void
    {
        User::create([
            'name' => 'Inactive',
            'email' => 'inactive@ereve.cd',
            'password' => Hash::make('password'),
            'status' => 'suspendu',
            'level' => 'coordination',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactive@ereve.cd',
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_otp_screen_requires_pending_login_and_logout_clears_session(): void
    {
        $this->get('/otp')->assertRedirect(route('login'));

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@ereve.cd',
            'password' => Hash::make('password'),
            'status' => 'actif',
            'level' => 'coordination',
        ]);

        $this->post('/login', [
            'email' => 'admin@ereve.cd',
            'password' => 'password',
        ])->assertRedirect(route('otp.create'));

        $this->post('/otp', ['code' => session('auth.otp_code')])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/')->assertRedirect(route('login'));
    }
}
