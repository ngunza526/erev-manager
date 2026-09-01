<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\Church;
use App\Models\ChurchMediaItem;
use App\Models\Community;
use App\Models\Member;
use App\Models\SolutionModule;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_login_exposes_core_rest_endpoints(): void
    {
        $this->seed(EreveSeeder::class);

        $this->getJson('/api/me')->assertUnauthorized();

        $tokenResponse = $this->postJson('/api/auth/token', [
            'email' => 'admin@ereve.cd',
            'password' => 'password',
            'device_name' => 'mobile-rdc',
        ])->assertOk()
            ->assertJsonStructure(['token_type', 'access_token', 'user' => ['id', 'email', 'level']]);

        $token = $tokenResponse->json('access_token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@ereve.cd');

        $this->withToken($token)->getJson('/api/churches')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/members')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($token)->getJson('/api/accounting/entries')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($token)->getJson('/api/solutions')
            ->assertOk()
            ->assertJsonCount(39, 'data');

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Token API revoque.');
    }

    public function test_church_level_api_user_is_scoped_to_own_church_data(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();

        Member::create($this->memberPayload($ownChurch->id, 'Api', 'Own', 'Member'));
        Member::create($this->memberPayload($otherChurch->id, 'Api', 'Other', 'Member'));
        SolutionModule::create([
            'code' => 'api-proof',
            'name' => 'API proof',
            'category' => 'Technical',
            'description' => 'Module de preuve API.',
            'church_central_reference' => 'API',
            'rdc_adaptation' => 'Perimetre multi-tenant.',
            'status' => 'active',
            'is_core' => false,
        ]);

        $token = $user->createToken('api-test')->plainTextToken;

        $this->withToken($token)->getJson('/api/churches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownChurch->id);

        $this->withToken($token)->getJson('/api/members')
            ->assertOk()
            ->assertJsonFragment(['last_name' => 'Api', 'middle_name' => 'Own'])
            ->assertJsonMissing(['middle_name' => 'Other']);
    }

    public function test_offline_media_manifest_only_exposes_allowed_published_media(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();

        ChurchMediaItem::create([
            'church_id' => $ownChurch->id,
            'title' => 'Affiche culte offline',
            'media_type' => 'image',
            'category' => 'culte',
            'storage_url' => 'https://cdn.example.test/eglise-a/affiche.jpg',
            'copyright_status' => 'interne',
            'offline_available' => true,
            'status' => 'published',
        ]);

        ChurchMediaItem::create([
            'church_id' => $ownChurch->id,
            'title' => 'Brouillon non offline',
            'media_type' => 'image',
            'category' => 'culte',
            'storage_url' => 'https://cdn.example.test/eglise-a/brouillon.jpg',
            'copyright_status' => 'interne',
            'offline_available' => false,
            'status' => 'published',
        ]);

        ChurchMediaItem::create([
            'church_id' => $otherChurch->id,
            'title' => 'Media autre eglise',
            'media_type' => 'image',
            'category' => 'culte',
            'storage_url' => 'https://cdn.example.test/eglise-b/affiche.jpg',
            'copyright_status' => 'interne',
            'offline_available' => true,
            'status' => 'published',
        ]);

        $token = $user->createToken('media-offline')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/media/offline-manifest')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Affiche culte offline')
            ->assertJsonPath('data.0.url', 'https://cdn.example.test/eglise-a/affiche.jpg')
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['title' => 'Brouillon non offline'])
            ->assertJsonMissing(['title' => 'Media autre eglise']);
    }

    private function makeScopedChurchUser(): array
    {
        $community = Community::create([
            'designation' => 'Communaute API',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-API-001',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise API A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise API B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::create([
            'name' => 'API Scope User',
            'email' => 'api-scope@example.cd',
            'password' => Hash::make('password'),
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        return [$user, $ownChurch, $otherChurch];
    }

    private function memberPayload(int $churchId, string $lastName, string $middleName, string $firstName): array
    {
        return [
            'church_id' => $churchId,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'first_name' => $firstName,
            'sex' => 'Masculin',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Lubumbashi',
            'profession' => 'Employe',
            'marital_status' => 'Celibataire',
            'spouse' => null,
            'status' => MemberStatus::Sympathisant->value,
        ];
    }
}
