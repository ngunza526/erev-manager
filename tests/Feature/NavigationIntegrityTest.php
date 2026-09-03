<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\ChurchEvent;
use App\Models\User;
use Database\Seeders\EreveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_layout_navigation_is_grouped_by_business_theme(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/AppLayout.vue'));

        foreach (['Dashboard', 'Membres', 'Cultes', 'Budget', 'Messages'] as $primaryLabel) {
            $this->assertStringContainsString("label: '{$primaryLabel}'", $layout);
        }

        foreach (['Organisation', 'Pastorale', 'Finance', 'Engagement', 'Digital'] as $sectionLabel) {
            $this->assertStringContainsString("label: '{$sectionLabel}'", $layout);
        }

        $this->assertStringContainsString('class="primary-nav"', $layout);
        $this->assertStringContainsString('class="nav-section"', $layout);
        $this->assertStringContainsString('aria-label="Navigation principale"', $layout);
    }

    public function test_every_layout_menu_link_points_to_a_live_authenticated_page(): void
    {
        $this->seed(EreveSeeder::class);

        $communityUser = $this->seededAdministrateur();
        $churchUser = User::where('email', 'eglise.admin@ereve.cd')->firstOrFail();
        $links = $this->layoutLinks();
        $communityLinks = ['/communautes', '/eglises', '/utilisateurs', '/roles-permissions', '/journal-audit'];

        $this->assertGreaterThan(30, count($links));

        foreach ($links as $href) {
            $this->assertRouteExistsForGet($href);

            $user = in_array($href, $communityLinks, true) ? $communityUser : $churchUser;

            $this->actingAs($user)
                ->get($href)
                ->assertOk();
        }
    }

    public function test_public_get_routes_exposed_from_seed_are_not_dead_links(): void
    {
        $this->seed(EreveSeeder::class);

        $church = Church::firstOrFail();
        $event = ChurchEvent::firstOrFail();

        foreach ([
            route('login', absolute: false),
            route('public.donation', $church, false),
            route('public.visitor', $church, false),
            route('public.event', $event, false),
        ] as $href) {
            $this->assertRouteExistsForGet($href);
            $this->get($href)->assertOk();
        }
    }

    private function layoutLinks(): array
    {
        $layout = file_get_contents(resource_path('js/Layouts/AppLayout.vue'));

        preg_match_all("/href: '([^']+)'/", $layout, $matches);

        return collect($matches[1])
            ->filter(fn (string $href) => str_starts_with($href, '/'))
            ->unique()
            ->values()
            ->all();
    }

    private function assertRouteExistsForGet(string $href): void
    {
        $exists = rescue(
            fn () => (bool) Route::getRoutes()->match(Request::create($href, 'GET')),
            false,
            report: false,
        );

        $this->assertTrue($exists, "Aucune route GET/HEAD ne correspond a {$href}.");
    }
}
