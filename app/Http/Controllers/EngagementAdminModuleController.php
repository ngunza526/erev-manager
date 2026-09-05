<?php

namespace App\Http\Controllers;

use App\Models\BoardMeeting;
use App\Models\ChurchAsset;
use App\Models\Communication;
use App\Models\FacilityBooking;
use App\Models\Pledge;
use App\Models\ServiceRequest;
use App\Models\Survey;
use App\Models\Testimony;
use App\Services\AccessScope;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EngagementAdminModuleController extends Controller
{
    public const MODULES = [
        'communications' => [
            'title' => 'Communications',
            'description' => 'Messages et annonces internes.',
            'model' => Communication::class,
            'primary' => 'subject',
            'secondary' => 'audience',
            'badge' => 'channel',
            'fields' => [
                ['name' => 'channel', 'label' => 'Canal', 'default' => 'sms'],
                ['name' => 'audience', 'label' => 'Audience', 'default' => 'membres'],
                ['name' => 'subject', 'label' => 'Sujet', 'required' => true],
                ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                ['name' => 'scheduled_at', 'label' => 'Planifie le', 'type' => 'datetime-local'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'draft'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'channel' => ['required', 'string', 'max:80'],
                'audience' => ['required', 'string', 'max:120'],
                'subject' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'scheduled_at' => ['nullable', 'date'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'demandes-service' => [
            'title' => 'Demandes de service',
            'description' => 'Demandes pastorales, assistance sociale, maintenance et affectation interne.',
            'model' => ServiceRequest::class,
            'primary' => 'requester_name',
            'secondary' => 'request_type',
            'badge' => 'status',
            'fields' => [
                ['name' => 'requester_name', 'label' => 'Demandeur', 'required' => true],
                ['name' => 'request_type', 'label' => 'Type de demande', 'default' => 'assistance sociale', 'required' => true],
                ['name' => 'priority', 'label' => 'Priorite', 'default' => 'normal'],
                ['name' => 'assigned_to', 'label' => 'Assigne a'],
                ['name' => 'due_at', 'label' => 'Echeance', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'open'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'requester_name' => ['required', 'string', 'max:255'],
                'request_type' => ['required', 'string', 'max:120'],
                'priority' => ['required', 'string', 'max:80'],
                'assigned_to' => ['nullable', 'string', 'max:255'],
                'due_at' => ['nullable', 'date'],
                'status' => ['required', 'string', 'max:80'],
                'description' => ['required', 'string'],
            ],
        ],
        'reservations-locaux' => [
            'title' => 'Reservation locaux',
            'description' => 'Planning des temples, salles et locations avec montant CDF/USD, comptabilise quand le paiement est encaisse.',
            'model' => FacilityBooking::class,
            'primary' => 'facility_name',
            'secondary' => 'requester_name',
            'badge' => 'payment_status',
            'fields' => [
                ['name' => 'requester_name', 'label' => 'Demandeur', 'required' => true],
                ['name' => 'facility_name', 'label' => 'Local', 'required' => true],
                ['name' => 'starts_at', 'label' => 'Debut', 'type' => 'datetime-local', 'required' => true],
                ['name' => 'ends_at', 'label' => 'Fin', 'type' => 'datetime-local', 'required' => true],
                ['name' => 'fee_currency', 'label' => 'Devise', 'default' => 'CDF'],
                ['name' => 'fee_amount', 'label' => 'Montant', 'type' => 'number', 'default' => 0],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'cash'],
                ['name' => 'payment_status', 'label' => 'Statut paiement', 'default' => 'unpaid'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'requester_name' => ['required', 'string', 'max:255'],
                'facility_name' => ['required', 'string', 'max:255'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['required', 'date', 'after:starts_at'],
                'fee_currency' => ['required', 'in:USD,CDF'],
                'fee_amount' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'max:80'],
                'payment_status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'patrimoine' => [
            'title' => 'Patrimoine',
            'description' => 'Terrains, temples, mobilier, instruments, audio-visuel et responsables.',
            'model' => ChurchAsset::class,
            'primary' => 'name',
            'secondary' => 'asset_code',
            'badge' => 'condition_status',
            'fields' => [
                ['name' => 'asset_code', 'label' => 'Code actif', 'required' => true],
                ['name' => 'name', 'label' => 'Designation', 'required' => true],
                ['name' => 'category', 'label' => 'Categorie', 'default' => 'materiel', 'required' => true],
                ['name' => 'location', 'label' => 'Localisation'],
                ['name' => 'purchase_date', 'label' => 'Date acquisition', 'type' => 'date'],
                ['name' => 'value_currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'value_amount', 'label' => 'Valeur', 'type' => 'number', 'default' => 0],
                ['name' => 'condition_status', 'label' => 'Etat', 'default' => 'bon'],
                ['name' => 'custodian', 'label' => 'Responsable'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'asset_code' => ['required', 'string', 'max:120'],
                'name' => ['required', 'string', 'max:255'],
                'category' => ['required', 'string', 'max:120'],
                'location' => ['nullable', 'string', 'max:255'],
                'purchase_date' => ['nullable', 'date'],
                'value_currency' => ['required', 'in:USD,CDF'],
                'value_amount' => ['required', 'numeric', 'min:0'],
                'condition_status' => ['required', 'string', 'max:80'],
                'custodian' => ['nullable', 'string', 'max:255'],
            ],
        ],
        'conseils-reunions' => [
            'title' => 'Conseils et reunions',
            'description' => 'Proces-verbaux, decisions, quorum et statut de validation.',
            'model' => BoardMeeting::class,
            'primary' => 'title',
            'secondary' => 'chairperson',
            'badge' => 'status',
            'fields' => [
                ['name' => 'title', 'label' => 'Titre', 'required' => true],
                ['name' => 'meeting_date', 'label' => 'Date reunion', 'type' => 'date', 'required' => true],
                ['name' => 'chairperson', 'label' => 'President de seance', 'required' => true],
                ['name' => 'quorum_count', 'label' => 'Quorum', 'type' => 'number', 'default' => 0],
                ['name' => 'decisions', 'label' => 'Decisions', 'type' => 'textarea', 'required' => true],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'draft'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'meeting_date' => ['required', 'date'],
                'chairperson' => ['required', 'string', 'max:255'],
                'quorum_count' => ['nullable', 'integer', 'min:0'],
                'decisions' => ['required', 'string'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'promesses-dons' => [
            'title' => 'Promesses de dons',
            'description' => 'Campagnes de construction, missions et engagements en USD/CDF, comptabilise pour le montant deja recu.',
            'model' => Pledge::class,
            'primary' => 'donor_name',
            'secondary' => 'campaign',
            'badge' => 'status',
            'fields' => [
                ['name' => 'donor_name', 'label' => 'Donateur', 'required' => true],
                ['name' => 'campaign', 'label' => 'Campagne', 'required' => true],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'pledged_amount', 'label' => 'Montant promis', 'type' => 'number', 'required' => true],
                ['name' => 'received_amount', 'label' => 'Montant recu', 'type' => 'number', 'default' => 0],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'cash'],
                ['name' => 'due_date', 'label' => 'Echeance', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'active'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'donor_name' => ['required', 'string', 'max:255'],
                'campaign' => ['required', 'string', 'max:255'],
                'currency' => ['required', 'in:USD,CDF'],
                'pledged_amount' => ['required', 'numeric', 'min:0'],
                'received_amount' => ['nullable', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'max:80'],
                'due_date' => ['nullable', 'date'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'sondages' => [
            'title' => 'Sondages',
            'description' => 'Consultations des membres, feedback de culte et suivi de participation.',
            'model' => Survey::class,
            'primary' => 'title',
            'secondary' => 'audience',
            'badge' => 'status',
            'fields' => [
                ['name' => 'title', 'label' => 'Titre', 'required' => true],
                ['name' => 'audience', 'label' => 'Audience', 'default' => 'membres'],
                ['name' => 'opens_at', 'label' => 'Ouverture', 'type' => 'date', 'required' => true],
                ['name' => 'closes_at', 'label' => 'Fermeture', 'type' => 'date'],
                ['name' => 'responses_count', 'label' => 'Reponses', 'type' => 'number', 'default' => 0],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'open'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'audience' => ['required', 'string', 'max:120'],
                'opens_at' => ['required', 'date'],
                'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
                'responses_count' => ['nullable', 'integer', 'min:0'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'temoignages' => [
            'title' => 'Temoignages',
            'description' => 'Soumission, moderation pastorale et publication des temoignages.',
            'model' => Testimony::class,
            'primary' => 'author_name',
            'secondary' => 'category',
            'badge' => 'moderation_status',
            'fields' => [
                ['name' => 'author_name', 'label' => 'Auteur', 'required' => true],
                ['name' => 'testimony_date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'category', 'label' => 'Categorie', 'default' => 'general'],
                ['name' => 'moderation_status', 'label' => 'Moderation', 'default' => 'pending'],
                ['name' => 'is_public', 'label' => 'Publier', 'type' => 'checkbox'],
                ['name' => 'content', 'label' => 'Temoignage', 'type' => 'textarea', 'required' => true],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'author_name' => ['required', 'string', 'max:255'],
                'testimony_date' => ['required', 'date'],
                'category' => ['required', 'string', 'max:120'],
                'moderation_status' => ['required', 'string', 'max:80'],
                'is_public' => ['boolean'],
                'content' => ['required', 'string'],
            ],
        ],
    ];

    public function index(Request $request, AccessScope $scope, string $module): Response
    {
        $config = $this->config($module);
        $model = $config['model'];

        return Inertia::render('Operations/GenericModule', [
            'moduleKey' => $module,
            'module' => collect($config)->except(['model', 'rules'])->all(),
            'churches' => $scope->churches($request->user()),
            'items' => $scope->scopeChurchOwned($model::with('church:id,designation'), $request->user())->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, AccessScope $scope, AccountingService $accounting, string $module): RedirectResponse
    {
        $config = $this->config($module);
        $model = $config['model'];
        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);

        $entry = $this->maybeCreateJournalEntry($module, $data, $request, $accounting);
        if ($entry) {
            $data['journal_entry_id'] = $entry->id;
        }

        $model::create($data);

        return back()->with('success', $entry ? 'Module enregistre avec ecriture comptable.' : 'Module enregistre.');
    }

    /**
     * Ces deux modules font apparaitre un montant d'argent reellement
     * encaisse (location payee, versement sur une promesse) : on les
     * comptabilise au meme titre que les autres mouvements de tresorerie
     * de l'application (cf. AdvancedChurchModuleController).
     */
    private function maybeCreateJournalEntry(string $module, array $data, Request $request, AccountingService $accounting): mixed
    {
        if ($module === 'reservations-locaux' && ($data['payment_status'] ?? null) === 'paid' && (float) ($data['fee_amount'] ?? 0) > 0) {
            return $accounting->recordBalancedEntry([
                'church_id' => $data['church_id'],
                'type' => 'facility_booking',
                'entry_date' => now()->toDateString(),
                'description' => "Location salle {$data['facility_name']}",
                'currency' => $data['fee_currency'],
                'exchange_rate' => 1,
                'created_by' => $request->user()?->id,
                'lines' => [
                    ['account_code' => $this->cashAccount($data['payment_method'] ?? 'cash'), 'label' => 'Encaissement location', 'debit' => $data['fee_amount'], 'credit' => 0],
                    ['account_code' => '704', 'label' => 'Revenus des ventes', 'debit' => 0, 'credit' => $data['fee_amount']],
                ],
            ]);
        }

        if ($module === 'promesses-dons' && (float) ($data['received_amount'] ?? 0) > 0) {
            return $accounting->recordBalancedEntry([
                'church_id' => $data['church_id'],
                'type' => 'pledge_payment',
                'entry_date' => now()->toDateString(),
                'description' => "Versement promesse {$data['donor_name']} - {$data['campaign']}",
                'currency' => $data['currency'],
                'exchange_rate' => 1,
                'created_by' => $request->user()?->id,
                'lines' => [
                    ['account_code' => $this->cashAccount($data['payment_method'] ?? 'cash'), 'label' => 'Encaissement promesse', 'debit' => $data['received_amount'], 'credit' => 0],
                    ['account_code' => '703', 'label' => 'Dons recus', 'debit' => 0, 'credit' => $data['received_amount']],
                ],
            ]);
        }

        return null;
    }

    private function cashAccount(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bank', 'card' => '501',
            'mobile_money', 'mpesa', 'airtel_money', 'orange_money' => '515',
            default => '511',
        };
    }

    private function config(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }
}
