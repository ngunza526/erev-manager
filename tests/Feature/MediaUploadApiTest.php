<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Community;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AssignsRoles;
use Tests\TestCase;

class MediaUploadApiTest extends TestCase
{
    use AssignsRoles;
    use RefreshDatabase;

    public function test_sanctum_user_can_resume_and_complete_chunked_media_upload(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->seed(EreveSeeder::class);

        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $church = Church::firstOrFail();
        $token = $user->createToken('mobile-media')->plainTextToken;

        $created = $this->withToken($token)->postJson('/api/media/uploads', [
            'church_id' => $church->id,
            'title' => 'Predication offline',
            'media_type' => 'audio',
            'category' => 'sermon',
            'original_filename' => 'predication.txt',
            'total_chunks' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'initiated')
            ->assertJsonPath('data.received_chunks', []);

        $uploadId = $created->json('data.id');

        $this->withToken($token)->postJson("/api/media/uploads/{$uploadId}/chunks", [
            'chunk_index' => 1,
            'content_base64' => base64_encode(' monde'),
        ])->assertOk()
            ->assertJsonPath('data.status', 'uploading')
            ->assertJsonPath('data.received_chunks', [1]);

        $this->withToken($token)->getJson("/api/media/uploads/{$uploadId}")
            ->assertOk()
            ->assertJsonPath('data.received_chunks', [1]);

        $this->withToken($token)->postJson("/api/media/uploads/{$uploadId}/chunks", [
            'chunk_index' => 0,
            'content_base64' => base64_encode('Bonjour'),
        ])->assertOk()
            ->assertJsonPath('data.status', 'ready_to_complete')
            ->assertJsonPath('data.received_chunks', [0, 1]);

        $completed = $this->withToken($token)->postJson("/api/media/uploads/{$uploadId}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('media.title', 'Predication offline')
            ->assertJsonPath('media.offline_available', true);

        $session = MediaUploadSession::findOrFail($completed->json('data.id'));
        Storage::disk('public')->assertExists($session->storage_path);
        $this->assertSame('Bonjour monde', Storage::disk('public')->get($session->storage_path));

        $this->assertDatabaseHas('church_media_items', [
            'church_id' => $church->id,
            'title' => 'Predication offline',
            'offline_available' => true,
            'status' => 'published',
        ]);
    }

    public function test_church_user_cannot_manage_media_upload_outside_scope(): void
    {
        [$user, $ownChurch, $otherChurch] = $this->makeScopedChurchUser();
        $token = $user->createToken('media-scope')->plainTextToken;

        $upload = MediaUploadSession::create([
            'church_id' => $otherChurch->id,
            'user_id' => null,
            'upload_id' => 'foreign-upload',
            'title' => 'Media hors perimetre',
            'media_type' => 'audio',
            'category' => 'sermon',
            'original_filename' => 'foreign.txt',
            'total_chunks' => 1,
            'received_chunks' => [],
            'status' => 'initiated',
        ]);

        $this->withToken($token)->postJson('/api/media/uploads', [
            'church_id' => $otherChurch->id,
            'title' => 'Tentative hors scope',
            'media_type' => 'audio',
            'category' => 'sermon',
            'original_filename' => 'blocked.txt',
            'total_chunks' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);

        $this->withToken($token)->getJson("/api/media/uploads/{$upload->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);

        $this->withToken($token)->postJson("/api/media/uploads/{$upload->id}/chunks", [
            'chunk_index' => 0,
            'content_base64' => base64_encode('Interdit'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['church_id']);

        $this->assertDatabaseMissing('church_media_items', [
            'church_id' => $ownChurch->id,
            'title' => 'Media hors perimetre',
        ]);
    }

    private function makeScopedChurchUser(): array
    {
        $community = Community::create([
            'designation' => 'Communaute Media API',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'authorization_number' => 'AUT-MEDIA-API',
        ]);

        $ownChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Media A',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $otherChurch = Church::create([
            'community_id' => $community->id,
            'designation' => 'Eglise Media B',
            'address_district' => 'Kenya',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
        ]);

        $user = User::factory()->create([
            'name' => 'Media Scope User',
            'email' => 'media-scope@example.cd',
            'church_id' => $ownChurch->id,
            'community_id' => $community->id,
            'level' => 'eglise',
            'status' => 'actif',
        ]);

        $this->withRoles($user, Rbac::SECRETAIRE);

        return [$user, $ownChurch, $otherChurch];
    }
}
