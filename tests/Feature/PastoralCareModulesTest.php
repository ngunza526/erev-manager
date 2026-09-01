<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PastoralCareModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_pastoral_care_and_media_modules(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertDatabaseHas('visitors', ['full_name' => 'Jean Visiteur']);
        $this->assertDatabaseHas('new_converts', ['full_name' => 'Marie Nouvelle']);
        $this->assertDatabaseHas('children', ['full_name' => 'Enfant ecole du dimanche', 'guardian_member_id' => null]);
        $this->assertDatabaseHas('volunteer_assignments', ['volunteer_name' => 'Sarah Mbuyi']);
        $this->assertDatabaseHas('training_courses', ['title' => 'Formation responsables de cellules']);
        $this->assertDatabaseHas('sermon_media', ['title' => 'La foi qui agit']);
        $this->assertDatabaseHas('security_incidents', ['title' => 'Controle sortie enfant']);
    }

    public function test_authenticated_user_can_view_pastoral_module_pages(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        foreach (['visiteurs', 'convertis', 'enfants', 'volontaires', 'formations', 'sermons-media', 'incidents'] as $uri) {
            $this->actingAs($user)->get("/{$uri}")->assertOk();
        }
    }

    public function test_child_check_in_and_secure_check_out_are_tracked(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $child = Child::where('full_name', 'Enfant ecole du dimanche')->firstOrFail();
        $child->update(['checked_in' => false, 'checked_in_at' => null, 'checked_out_at' => null, 'released_to' => null]);

        $this->actingAs($user)
            ->post("/enfants/{$child->id}/check-in", ['check_in_code' => 'ENF-001'])
            ->assertRedirect();

        $child->refresh();
        $this->assertTrue($child->checked_in);
        $this->assertNotNull($child->checked_in_at);
        $this->assertNull($child->checked_out_at);

        $this->actingAs($user)
            ->post("/enfants/{$child->id}/check-out", ['check_in_code' => 'ENF-001', 'released_to' => 'Parent responsable'])
            ->assertRedirect();

        $child->refresh();
        $this->assertFalse($child->checked_in);
        $this->assertNotNull($child->checked_out_at);
        $this->assertSame('Parent responsable', $child->released_to);
    }
}
