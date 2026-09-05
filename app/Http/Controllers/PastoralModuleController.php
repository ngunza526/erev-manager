<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\NewConvert;
use App\Models\SecurityIncident;
use App\Models\SermonMedia;
use App\Models\TrainingCourse;
use App\Models\Visitor;
use App\Models\VolunteerAssignment;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PastoralModuleController extends Controller
{
    public const MODULES = [
        'visiteurs' => [
            'page' => 'Pastoral/Visitors',
            'model' => Visitor::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'full_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:80'],
                'email' => ['nullable', 'email', 'max:255'],
                'visit_source' => ['required', 'string', 'max:120'],
                'visited_at' => ['required', 'date'],
                'follow_up_status' => ['required', 'string', 'max:120'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'convertis' => [
            'page' => 'Pastoral/NewConverts',
            'model' => NewConvert::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'full_name' => ['required', 'string', 'max:255'],
                'conversion_date' => ['required', 'date'],
                'discipleship_stage' => ['required', 'string', 'max:120'],
                'mentor_name' => ['nullable', 'string', 'max:255'],
                'baptism_target_date' => ['nullable', 'date'],
                'status' => ['required', 'string', 'max:120'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'enfants' => [
            'page' => 'Pastoral/Children',
            'model' => Child::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'full_name' => ['required', 'string', 'max:255'],
                'birth_date' => ['required', 'date'],
                'guardian_name' => ['required', 'string', 'max:255'],
                'guardian_phone' => ['nullable', 'string', 'max:80'],
                'classroom' => ['nullable', 'string', 'max:120'],
                'check_in_code' => ['nullable', 'string', 'max:120'],
                'checked_in' => ['boolean'],
            ],
        ],
        'volontaires' => [
            'page' => 'Pastoral/Volunteers',
            'model' => VolunteerAssignment::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'volunteer_name' => ['required', 'string', 'max:255'],
                'team' => ['required', 'string', 'max:120'],
                'role' => ['required', 'string', 'max:120'],
                'service_date' => ['required', 'date'],
                'availability_status' => ['required', 'string', 'max:120'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'formations' => [
            'page' => 'Pastoral/Training',
            'model' => TrainingCourse::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'category' => ['required', 'string', 'max:120'],
                'instructor_name' => ['required', 'string', 'max:255'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'enrollments_count' => ['nullable', 'integer', 'min:0'],
                'certificate_enabled' => ['boolean'],
            ],
        ],
        'sermons-media' => [
            'page' => 'Pastoral/SermonsMedia',
            'model' => SermonMedia::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'preacher' => ['nullable', 'string', 'max:255'],
                'preached_at' => ['required', 'date'],
                'bible_reference' => ['nullable', 'string', 'max:120'],
                'media_type' => ['required', 'string', 'max:80'],
                'public_url' => ['nullable', 'url', 'max:255'],
                'is_public' => ['boolean'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'incidents' => [
            'page' => 'Pastoral/Security',
            'model' => SecurityIncident::class,
            'relation' => 'church:id,designation',
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'incident_type' => ['required', 'string', 'max:120'],
                'severity' => ['required', 'string', 'max:80'],
                'occurred_at' => ['required', 'date'],
                'reported_by' => ['required', 'string', 'max:255'],
                'status' => ['required', 'string', 'max:120'],
                'description' => ['required', 'string'],
            ],
        ],
    ];

    public function index(Request $request, AccessScope $scope, string $module): Response
    {
        $config = $this->config($module);
        $model = $config['model'];

        return Inertia::render($config['page'], [
            'churches' => $scope->churches($request->user()),
            'items' => $scope->scopeChurchOwned($model::with($config['relation']), $request->user())->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccessScope $scope, string $module): RedirectResponse
    {
        $config = $this->config($module);
        $model = $config['model'];
        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $model::create($data);

        return back()->with('success', 'Element enregistre.');
    }

    public function childCheckIn(Request $request, Child $child, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $child->church_id);

        $data = $request->validate([
            'check_in_code' => ['required', 'string', 'max:120'],
        ]);

        if ($child->check_in_code && $child->check_in_code !== $data['check_in_code']) {
            throw ValidationException::withMessages(['check_in_code' => 'Code check-in invalide.']);
        }

        $child->update([
            'checked_in' => true,
            'checked_in_at' => now(),
            'checked_out_at' => null,
            'released_to' => null,
        ]);

        return back()->with('success', 'Check-in enfant confirme.');
    }

    public function childCheckOut(Request $request, Child $child, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $child->church_id);

        $data = $request->validate([
            'released_to' => ['required', 'string', 'max:255'],
            'check_in_code' => ['required', 'string', 'max:120'],
        ]);

        if (! $child->checked_in) {
            throw ValidationException::withMessages(['child' => 'Cet enfant n est pas marque present.']);
        }

        if ($child->check_in_code && $child->check_in_code !== $data['check_in_code']) {
            throw ValidationException::withMessages(['check_in_code' => 'Code de sortie invalide.']);
        }

        $child->update([
            'checked_in' => false,
            'checked_out_at' => now(),
            'released_to' => $data['released_to'],
        ]);

        return back()->with('success', 'Sortie enfant securisee.');
    }

    private function config(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }
}
