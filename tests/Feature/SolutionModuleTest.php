<?php

namespace Tests\Feature;

use App\Models\SolutionModule;
use App\Models\User;
use App\Support\SolutionImplementationMap;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolutionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_solution_catalog_contains_church_central_modules_with_rdc_adaptations(): void
    {
        $this->seed(EreveSeeder::class);

        $this->assertDatabaseHas('solution_modules', ['code' => 'members', 'church_central_reference' => 'Members']);
        $this->assertDatabaseHas('solution_modules', ['code' => 'general_ledger', 'church_central_reference' => 'General Ledger']);
        $this->assertDatabaseHas('solution_modules', ['code' => 'childrens_church', 'church_central_reference' => 'Childrens Church']);
        $this->assertGreaterThanOrEqual(35, SolutionModule::count());
    }

    public function test_authenticated_user_can_view_solutions_page(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        $this->actingAs($user)->get('/solutions')->assertOk();
    }

    public function test_every_catalog_module_has_an_implementation_mapping(): void
    {
        $this->seed(EreveSeeder::class);

        foreach (SolutionModule::all() as $module) {
            $mapping = SolutionImplementationMap::for($module->code);

            $this->assertNotSame('missing', $mapping['level'], "Module {$module->code} is not mapped.");
            $this->assertNotEmpty($mapping['proof']);
        }
    }

    public function test_solutions_page_reports_full_catalog_coverage(): void
    {
        $this->seed(EreveSeeder::class);
        $user = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();

        $this->actingAs($user)
            ->get('/solutions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.coverage', 100)
                ->where('stats.implemented', SolutionModule::count())
            );
    }
}
