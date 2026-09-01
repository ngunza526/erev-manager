<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DeploymentDocumentationTest extends TestCase
{
    public function test_environment_example_is_configured_for_ereve_rdc(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_NAME="eReve Church"', $env);
        $this->assertStringContainsString('APP_TIMEZONE=Africa/Lubumbashi', $env);
        $this->assertStringContainsString('APP_LOCALE=fr', $env);
        $this->assertStringContainsString('DB_DATABASE=ereve', $env);
    }

    public function test_deployment_docs_cover_public_flows_and_verification(): void
    {
        $readme = file_get_contents(base_path('README.md'));
        $deployment = file_get_contents(base_path('DEPLOYMENT.md'));
        $checklist = file_get_contents(base_path('DEPLOYMENT_CHECKLIST.md'));

        $this->assertStringContainsString('/solutions', $readme);
        $this->assertStringContainsString('/public/eglises/1/don', $readme);
        $this->assertStringContainsString('composer audit', $deployment);
        $this->assertStringContainsString('npm audit --audit-level=moderate', $deployment);
        $this->assertStringContainsString('Check-in/check-out enfant', $checklist);
        $this->assertStringContainsString('Paiement fournisseur', $checklist);
    }

    public function test_api_reference_documents_every_exposed_api_route(): void
    {
        $reference = file_get_contents(base_path('API_REFERENCE.md'));

        $routes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->flatMap(function ($route) {
                return collect($route->methods())
                    ->reject(fn ($method) => $method === 'HEAD')
                    ->map(fn ($method) => "`{$method} /{$route->uri()}`");
            })
            ->unique()
            ->values();

        $this->assertNotEmpty($routes);

        foreach ($routes as $documentedRoute) {
            $this->assertStringContainsString($documentedRoute, $reference);
        }
    }

    public function test_api_reference_contains_payload_examples_for_critical_integrations(): void
    {
        $reference = file_get_contents(base_path('API_REFERENCE.md'));

        foreach ([
            '"device_name": "mobile-lubumbashi-001"',
            '"status": "sympathisant"',
            '"payment_method": "mobile_money"',
            'Exemple pastoral `POST /api/pastoral/visiteurs`',
            'Exemple administration `POST /api/administration/communications`',
            'Exemple boutique avec ecriture de revenu',
            'Exemple mouvement de fonds',
            '"type": "manual_journal_entry"',
            '"account_code": "703"',
        ] as $expectedFragment) {
            $this->assertStringContainsString($expectedFragment, $reference);
        }
    }

    public function test_frontend_framework_stack_includes_bootstrap_and_tailwind(): void
    {
        $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
        $css = file_get_contents(resource_path('css/app.css'));
        $js = file_get_contents(resource_path('js/app.js'));
        $vite = file_get_contents(base_path('vite.config.js'));

        $this->assertArrayHasKey('bootstrap', $package['dependencies']);
        $this->assertArrayHasKey('tailwindcss', $package['dependencies']);
        $this->assertArrayHasKey('@tailwindcss/vite', $package['dependencies']);
        $this->assertStringContainsString('@import "tailwindcss";', $css);
        $this->assertStringContainsString('@import "bootstrap/dist/css/bootstrap.min.css";', $css);
        $this->assertStringContainsString("import 'bootstrap';", $js);
        $this->assertStringContainsString("import tailwindcss from '@tailwindcss/vite';", $vite);
        $this->assertStringContainsString('tailwindcss(),', $vite);
    }

    public function test_route_scope_audit_covers_sensitive_secondary_routes(): void
    {
        $audit = file_get_contents(base_path('ROUTE_SCOPE_AUDIT.md'));
        $readme = file_get_contents(base_path('README.md'));

        foreach ([
            '/membres/{member}/statut',
            '/enfants/{child}/check-in',
            '/enfants/{child}/check-out',
            '/fournisseurs/{vendorBill}/payer',
            '/paie/{payrollRun}/payer',
            '/counseling/{counselingCase}/planifier',
            '/counseling/{counselingCase}/cloturer',
            '/api/offline/sync',
            '/api/media/offline-manifest',
            'SecondaryRouteScopeTest',
            'AccessScope',
        ] as $expectedFragment) {
            $this->assertStringContainsString($expectedFragment, $audit);
        }

        $this->assertStringContainsString('ROUTE_SCOPE_AUDIT.md', $readme);
    }

    public function test_openapi_spec_covers_every_exposed_api_route(): void
    {
        $spec = json_decode(file_get_contents(base_path('OPENAPI.json')), true, flags: JSON_THROW_ON_ERROR);
        $readme = file_get_contents(base_path('README.md'));
        $reference = file_get_contents(base_path('API_REFERENCE.md'));

        $this->assertSame('3.1.0', $spec['openapi']);
        $this->assertArrayHasKey('sanctumBearer', $spec['components']['securitySchemes']);
        $this->assertStringContainsString('OPENAPI.json', $readme);
        $this->assertStringContainsString('OPENAPI.json', $reference);

        $routes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->flatMap(function ($route) {
                $path = '/'.$route->uri();

                return collect($route->methods())
                    ->reject(fn ($method) => $method === 'HEAD')
                    ->map(fn ($method) => [$path, strtolower($method)]);
            })
            ->unique(fn ($route) => $route[1].' '.$route[0])
            ->values();

        foreach ($routes as [$path, $method]) {
            $this->assertArrayHasKey($path, $spec['paths'], "{$path} missing from OPENAPI.json");
            $this->assertArrayHasKey($method, $spec['paths'][$path], "{$method} {$path} missing from OPENAPI.json");
        }
    }
}
