<?php

namespace App\Http\Controllers;

use App\Models\AiToolRequest;
use App\Models\BankReconciliation;
use App\Models\ChurchEvent;
use App\Models\ChurchMediaItem;
use App\Models\ChurchPayout;
use App\Models\CounselingCase;
use App\Models\DiscipleshipPath;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\Fund;
use App\Models\FundMovement;
use App\Models\LiveStreamSession;
use App\Models\OutreachCampaign;
use App\Models\PayrollRun;
use App\Models\PublicQrCode;
use App\Models\ResourceSale;
use App\Models\VendorBill;
use App\Services\AccessScope;
use App\Services\Accounting\AccountingService;
use App\Support\Audit;
use App\Support\Rbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdvancedChurchModuleController extends Controller
{
    public const MODULES = [
        'boutique-ressources' => [
            'title' => 'Boutique ressources',
            'description' => 'Vente de bibles, brochures et ressources avec ecriture automatique quand la vente est payee.',
            'model' => ResourceSale::class,
            'primary' => 'item_name',
            'secondary' => 'buyer_name',
            'badge' => 'status',
            'fields' => [
                ['name' => 'item_name', 'label' => 'Article', 'required' => true],
                ['name' => 'buyer_name', 'label' => 'Acheteur'],
                ['name' => 'quantity', 'label' => 'Quantite', 'type' => 'number', 'default' => 1],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'CDF'],
                ['name' => 'unit_price', 'label' => 'Prix unitaire', 'type' => 'number', 'required' => true],
                ['name' => 'exchange_rate', 'label' => 'Taux', 'type' => 'number', 'default' => 2850],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'cash'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'draft'],
                ['name' => 'sold_at', 'label' => 'Date vente', 'type' => 'date', 'required' => true],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'item_name' => ['required', 'string', 'max:255'],
                'buyer_name' => ['nullable', 'string', 'max:255'],
                'quantity' => ['required', 'integer', 'min:1'],
                'currency' => ['required', 'in:USD,CDF'],
                'unit_price' => ['required', 'numeric', 'min:0.01'],
                'exchange_rate' => ['required', 'numeric', 'min:1'],
                'payment_method' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
                'sold_at' => ['required', 'date'],
            ],
        ],
        'fournisseurs' => [
            'title' => 'Fournisseurs',
            'description' => 'Factures fournisseurs, echeances et ecriture comptable au moment du paiement.',
            'model' => VendorBill::class,
            'primary' => 'vendor_name',
            'secondary' => 'bill_number',
            'badge' => 'status',
            'fields' => [
                ['name' => 'vendor_name', 'label' => 'Fournisseur', 'required' => true],
                ['name' => 'bill_number', 'label' => 'Facture'],
                ['name' => 'category', 'label' => 'Categorie', 'default' => 'fonctionnement'],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'amount', 'label' => 'Montant', 'type' => 'number', 'required' => true],
                ['name' => 'exchange_rate', 'label' => 'Taux', 'type' => 'number', 'default' => 2850],
                ['name' => 'bill_date', 'label' => 'Date facture', 'type' => 'date', 'required' => true],
                ['name' => 'due_date', 'label' => 'Echeance', 'type' => 'date'],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'bank'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'pending'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'vendor_name' => ['required', 'string', 'max:255'],
                'bill_number' => ['nullable', 'string', 'max:120'],
                'category' => ['required', 'string', 'max:120'],
                'currency' => ['required', 'in:USD,CDF'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'exchange_rate' => ['required', 'numeric', 'min:1'],
                'bill_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date'],
                'payment_method' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'paie' => [
            'title' => 'Paie',
            'description' => 'Paie du staff, charges sociales locales et paiement comptabilise quand la paie est payee.',
            'model' => PayrollRun::class,
            'primary' => 'staff_name',
            'secondary' => 'period_label',
            'badge' => 'status',
            'fields' => [
                ['name' => 'period_label', 'label' => 'Periode', 'required' => true],
                ['name' => 'staff_name', 'label' => 'Employe', 'required' => true],
                ['name' => 'role', 'label' => 'Role', 'required' => true],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'gross_amount', 'label' => 'Brut', 'type' => 'number', 'required' => true],
                ['name' => 'social_charges', 'label' => 'Charges sociales', 'type' => 'number', 'default' => 0],
                ['name' => 'net_amount', 'label' => 'Net paye', 'type' => 'number', 'required' => true],
                ['name' => 'exchange_rate', 'label' => 'Taux', 'type' => 'number', 'default' => 2850],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'bank'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'draft'],
                ['name' => 'paid_at', 'label' => 'Date paiement', 'type' => 'date'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'period_label' => ['required', 'string', 'max:120'],
                'staff_name' => ['required', 'string', 'max:255'],
                'role' => ['required', 'string', 'max:120'],
                'currency' => ['required', 'in:USD,CDF'],
                'gross_amount' => ['required', 'numeric', 'min:0.01'],
                'social_charges' => ['nullable', 'numeric', 'min:0'],
                'net_amount' => ['required', 'numeric', 'min:0.01'],
                'exchange_rate' => ['required', 'numeric', 'min:1'],
                'payment_method' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
                'paid_at' => ['nullable', 'date'],
            ],
        ],
        'rapprochements' => [
            'title' => 'Rapprochement bancaire',
            'description' => 'Controle banque, caisse et Mobile Money entre solde livre et releve.',
            'model' => BankReconciliation::class,
            'primary' => 'account_name',
            'secondary' => 'currency',
            'badge' => 'status',
            'fields' => [
                ['name' => 'account_name', 'label' => 'Compte', 'required' => true],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'statement_date', 'label' => 'Date releve', 'type' => 'date', 'required' => true],
                ['name' => 'book_balance', 'label' => 'Solde livre', 'type' => 'number', 'required' => true],
                ['name' => 'statement_balance', 'label' => 'Solde releve', 'type' => 'number', 'required' => true],
                ['name' => 'difference_amount', 'label' => 'Ecart', 'type' => 'number', 'default' => 0],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'open'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'account_name' => ['required', 'string', 'max:255'],
                'currency' => ['required', 'in:USD,CDF'],
                'statement_date' => ['required', 'date'],
                'book_balance' => ['required', 'numeric'],
                'statement_balance' => ['required', 'numeric'],
                'difference_amount' => ['nullable', 'numeric'],
                'status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'reversements' => [
            'title' => 'Reversements',
            'description' => 'Reversements coordination-eglises, missions et transferts avec ecriture.',
            'model' => ChurchPayout::class,
            'primary' => 'beneficiary',
            'secondary' => 'purpose',
            'badge' => 'status',
            'fields' => [
                ['name' => 'beneficiary', 'label' => 'Beneficiaire', 'required' => true],
                ['name' => 'purpose', 'label' => 'Objet', 'required' => true],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'amount', 'label' => 'Montant', 'type' => 'number', 'required' => true],
                ['name' => 'exchange_rate', 'label' => 'Taux', 'type' => 'number', 'default' => 2850],
                ['name' => 'payout_date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'bank'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'pending'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'beneficiary' => ['required', 'string', 'max:255'],
                'purpose' => ['required', 'string', 'max:255'],
                'currency' => ['required', 'in:USD,CDF'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'exchange_rate' => ['required', 'numeric', 'min:1'],
                'payout_date' => ['required', 'date'],
                'payment_method' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'counseling' => [
            'title' => 'Counseling',
            'description' => 'Accompagnement pastoral confidentiel avec niveau de restriction.',
            'model' => CounselingCase::class,
            'primary' => 'requester_name',
            'secondary' => 'case_code',
            'badge' => 'status',
            'fields' => [
                ['name' => 'case_code', 'label' => 'Code dossier', 'required' => true],
                ['name' => 'requester_name', 'label' => 'Demandeur', 'required' => true],
                ['name' => 'care_type', 'label' => 'Type', 'default' => 'pastoral'],
                ['name' => 'assigned_to', 'label' => 'Assigne a'],
                ['name' => 'appointment_date', 'label' => 'Rendez-vous', 'type' => 'date'],
                ['name' => 'confidentiality_level', 'label' => 'Confidentialite', 'default' => 'restreint'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'open'],
                ['name' => 'summary', 'label' => 'Resume', 'type' => 'textarea', 'required' => true],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'case_code' => ['required', 'string', 'max:120'],
                'requester_name' => ['required', 'string', 'max:255'],
                'care_type' => ['required', 'string', 'max:120'],
                'assigned_to' => ['nullable', 'string', 'max:255'],
                'appointment_date' => ['nullable', 'date'],
                'confidentiality_level' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
                'summary' => ['required', 'string'],
            ],
        ],
        'evangelisation' => [
            'title' => 'Evangelisation',
            'description' => 'Campagnes, contacts, conversions et suivi missionnaire local.',
            'model' => OutreachCampaign::class,
            'primary' => 'title',
            'secondary' => 'location',
            'badge' => 'status',
            'fields' => [
                ['name' => 'title', 'label' => 'Campagne', 'required' => true],
                ['name' => 'location', 'label' => 'Lieu', 'required' => true],
                ['name' => 'starts_at', 'label' => 'Debut', 'type' => 'date', 'required' => true],
                ['name' => 'ends_at', 'label' => 'Fin', 'type' => 'date'],
                ['name' => 'volunteers_count', 'label' => 'Volontaires', 'type' => 'number', 'default' => 0],
                ['name' => 'contacts_count', 'label' => 'Contacts', 'type' => 'number', 'default' => 0],
                ['name' => 'conversions_count', 'label' => 'Conversions', 'type' => 'number', 'default' => 0],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'planned'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'location' => ['required', 'string', 'max:255'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'volunteers_count' => ['nullable', 'integer', 'min:0'],
                'contacts_count' => ['nullable', 'integer', 'min:0'],
                'conversions_count' => ['nullable', 'integer', 'min:0'],
                'status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'qr-publics' => [
            'title' => 'QR publics',
            'description' => 'QR pour dons, visiteurs, evenements, sermons et formulaires publics.',
            'model' => PublicQrCode::class,
            'primary' => 'label',
            'secondary' => 'target_type',
            'badge' => 'short_code',
            'fields' => [
                ['name' => 'label', 'label' => 'Libelle', 'required' => true],
                ['name' => 'target_type', 'label' => 'Cible', 'default' => 'don'],
                ['name' => 'target_url', 'label' => 'URL', 'type' => 'url', 'required' => true],
                ['name' => 'short_code', 'label' => 'Code court', 'required' => true],
                ['name' => 'scan_count', 'label' => 'Scans', 'type' => 'number', 'default' => 0],
                ['name' => 'is_active', 'label' => 'Actif', 'type' => 'checkbox', 'default' => true],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'label' => ['required', 'string', 'max:255'],
                'target_type' => ['required', 'string', 'max:120'],
                'target_url' => ['required', 'url', 'max:255'],
                'short_code' => ['required', 'string', 'max:120'],
                'scan_count' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['boolean'],
            ],
        ],
        'live-studio' => [
            'title' => 'Live stream / studio',
            'description' => 'Planification streaming avec fallback audio pour faibles debits.',
            'model' => LiveStreamSession::class,
            'primary' => 'title',
            'secondary' => 'platform',
            'badge' => 'status',
            'fields' => [
                ['name' => 'title', 'label' => 'Titre', 'required' => true],
                ['name' => 'starts_at', 'label' => 'Debut', 'type' => 'datetime-local', 'required' => true],
                ['name' => 'platform', 'label' => 'Plateforme', 'default' => 'facebook'],
                ['name' => 'stream_url', 'label' => 'URL stream', 'type' => 'url'],
                ['name' => 'fallback_mode', 'label' => 'Fallback', 'default' => 'audio'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'scheduled'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'starts_at' => ['required', 'date'],
                'platform' => ['required', 'string', 'max:120'],
                'stream_url' => ['nullable', 'url', 'max:255'],
                'fallback_mode' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'outils-ia' => [
            'title' => 'Outils IA',
            'description' => 'Demandes IA pour redaction, preparation et design avec validation humaine.',
            'model' => AiToolRequest::class,
            'primary' => 'prompt_title',
            'secondary' => 'requested_by',
            'badge' => 'human_review_status',
            'fields' => [
                ['name' => 'tool_type', 'label' => 'Outil', 'default' => 'redaction'],
                ['name' => 'requested_by', 'label' => 'Demande par', 'required' => true],
                ['name' => 'prompt_title', 'label' => 'Titre', 'required' => true],
                ['name' => 'prompt_context', 'label' => 'Contexte', 'type' => 'textarea', 'required' => true],
                ['name' => 'human_review_status', 'label' => 'Validation humaine', 'default' => 'pending'],
                ['name' => 'output_summary', 'label' => 'Resume sortie', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'tool_type' => ['required', 'string', 'max:120'],
                'requested_by' => ['required', 'string', 'max:255'],
                'prompt_title' => ['required', 'string', 'max:255'],
                'prompt_context' => ['required', 'string'],
                'human_review_status' => ['required', 'string', 'max:80'],
                'output_summary' => ['nullable', 'string'],
            ],
        ],
        'familles' => [
            'title' => 'Familles',
            'description' => 'Foyers, contacts principaux, quartier et composition familiale.',
            'model' => Family::class,
            'primary' => 'household_name',
            'secondary' => 'primary_contact_name',
            'badge' => 'status',
            'fields' => [
                ['name' => 'household_name', 'label' => 'Foyer', 'required' => true],
                ['name' => 'primary_contact_name', 'label' => 'Contact principal', 'required' => true],
                ['name' => 'phone', 'label' => 'Telephone'],
                ['name' => 'district', 'label' => 'Quartier'],
                ['name' => 'city', 'label' => 'Ville', 'default' => 'Lubumbashi'],
                ['name' => 'members_count', 'label' => 'Nombre membres', 'type' => 'number', 'default' => 1],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'active'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'household_name' => ['required', 'string', 'max:255'],
                'primary_contact_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:80'],
                'district' => ['nullable', 'string', 'max:120'],
                'city' => ['required', 'string', 'max:120'],
                'members_count' => ['required', 'integer', 'min:1'],
                'status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'discipolat' => [
            'title' => 'Discipolat',
            'description' => 'Parcours, etapes, mentor et progression des membres ou nouveaux convertis.',
            'model' => DiscipleshipPath::class,
            'primary' => 'participant_name',
            'secondary' => 'track_name',
            'badge' => 'current_step',
            'fields' => [
                ['name' => 'participant_name', 'label' => 'Participant', 'required' => true],
                ['name' => 'track_name', 'label' => 'Parcours', 'default' => 'Fondements de la foi', 'required' => true],
                ['name' => 'current_step', 'label' => 'Etape', 'default' => 'accueil'],
                ['name' => 'progress_percent', 'label' => 'Progression %', 'type' => 'number', 'default' => 0],
                ['name' => 'mentor_name', 'label' => 'Mentor'],
                ['name' => 'next_meeting_at', 'label' => 'Prochaine rencontre', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'active'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'participant_name' => ['required', 'string', 'max:255'],
                'track_name' => ['required', 'string', 'max:255'],
                'current_step' => ['required', 'string', 'max:120'],
                'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
                'mentor_name' => ['nullable', 'string', 'max:255'],
                'next_meeting_at' => ['nullable', 'date'],
                'status' => ['required', 'string', 'max:80'],
            ],
        ],
        'mediatheque' => [
            'title' => 'Mediatheque',
            'description' => 'Images, videos, affiches et fichiers disponibles en ligne ou hors-ligne.',
            'model' => ChurchMediaItem::class,
            'primary' => 'title',
            'secondary' => 'category',
            'badge' => 'media_type',
            'fields' => [
                ['name' => 'title', 'label' => 'Titre', 'required' => true],
                ['name' => 'media_type', 'label' => 'Type', 'default' => 'image'],
                ['name' => 'category', 'label' => 'Categorie', 'default' => 'culte'],
                ['name' => 'storage_url', 'label' => 'URL fichier', 'type' => 'url'],
                ['name' => 'copyright_status', 'label' => 'Droits', 'default' => 'interne'],
                ['name' => 'offline_available', 'label' => 'Disponible hors-ligne', 'type' => 'checkbox'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'published'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'title' => ['required', 'string', 'max:255'],
                'media_type' => ['required', 'string', 'max:80'],
                'category' => ['required', 'string', 'max:120'],
                'storage_url' => ['nullable', 'url', 'max:255'],
                'copyright_status' => ['required', 'string', 'max:120'],
                'offline_available' => ['boolean'],
                'status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'fonds-dedies' => [
            'title' => 'Comptabilite par fonds',
            'description' => 'Fonds affectes construction, missions, jeunesse et soldes par devise.',
            'model' => Fund::class,
            'primary' => 'name',
            'secondary' => 'code',
            'badge' => 'restriction_type',
            'fields' => [
                ['name' => 'code', 'label' => 'Code fonds', 'required' => true],
                ['name' => 'name', 'label' => 'Nom', 'required' => true],
                ['name' => 'restriction_type', 'label' => 'Restriction', 'default' => 'affecte'],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'opening_balance', 'label' => 'Solde initial', 'type' => 'number', 'default' => 0],
                ['name' => 'current_balance', 'label' => 'Solde courant', 'type' => 'number', 'default' => 0],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'active'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'code' => ['required', 'string', 'max:120'],
                'name' => ['required', 'string', 'max:255'],
                'restriction_type' => ['required', 'string', 'max:120'],
                'currency' => ['required', 'in:USD,CDF'],
                'opening_balance' => ['nullable', 'numeric', 'min:0'],
                'current_balance' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string'],
            ],
        ],
        'mouvements-fonds' => [
            'title' => 'Mouvements de fonds',
            'description' => 'Encaissements et sorties affectes a un fonds, avec journal comptable quand le mouvement est poste.',
            'model' => FundMovement::class,
            'primary' => 'description',
            'secondary' => 'source_name',
            'badge' => 'movement_type',
            'fields' => [
                ['name' => 'fund_id', 'label' => 'Fonds dedie', 'type' => 'select', 'optionsKey' => 'funds', 'required' => true],
                ['name' => 'movement_type', 'label' => 'Type mouvement', 'default' => 'receipt'],
                ['name' => 'source_name', 'label' => 'Source'],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'USD'],
                ['name' => 'amount', 'label' => 'Montant', 'type' => 'number', 'required' => true],
                ['name' => 'exchange_rate', 'label' => 'Taux', 'type' => 'number', 'default' => 2850],
                ['name' => 'movement_date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'cash'],
                ['name' => 'status', 'label' => 'Statut', 'default' => 'draft'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'fund_id' => ['required', 'exists:funds,id'],
                'movement_type' => ['required', 'string', 'max:80'],
                'source_name' => ['nullable', 'string', 'max:255'],
                'currency' => ['required', 'in:USD,CDF'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'exchange_rate' => ['required', 'numeric', 'min:1'],
                'movement_date' => ['required', 'date'],
                'payment_method' => ['required', 'string', 'max:80'],
                'status' => ['required', 'string', 'max:80'],
                'description' => ['required', 'string'],
            ],
        ],
        'inscriptions-evenements' => [
            'title' => 'Inscriptions evenements',
            'description' => 'Tickets, codes QR, paiements et check-in des conferences et croisades.',
            'model' => EventRegistration::class,
            'primary' => 'attendee_name',
            'secondary' => 'ticket_code',
            'badge' => 'check_in_status',
            'fields' => [
                ['name' => 'church_event_id', 'label' => 'Evenement', 'type' => 'select', 'optionsKey' => 'events'],
                ['name' => 'attendee_name', 'label' => 'Participant', 'required' => true],
                ['name' => 'phone', 'label' => 'Telephone'],
                ['name' => 'ticket_code', 'label' => 'Code ticket', 'required' => true],
                ['name' => 'currency', 'label' => 'Devise', 'default' => 'CDF'],
                ['name' => 'amount_paid', 'label' => 'Montant paye', 'type' => 'number', 'default' => 0],
                ['name' => 'exchange_rate', 'label' => 'Taux', 'type' => 'number', 'default' => 2850],
                ['name' => 'payment_method', 'label' => 'Paiement', 'default' => 'cash'],
                ['name' => 'check_in_status', 'label' => 'Check-in', 'default' => 'registered'],
                ['name' => 'checked_in_at', 'label' => 'Heure check-in', 'type' => 'datetime-local'],
            ],
            'rules' => [
                'church_id' => ['required', 'exists:churches,id'],
                'church_event_id' => ['nullable', 'exists:church_events,id'],
                'attendee_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:80'],
                'ticket_code' => ['required', 'string', 'max:120'],
                'currency' => ['required', 'in:USD,CDF'],
                'amount_paid' => ['nullable', 'numeric', 'min:0'],
                'exchange_rate' => ['required', 'numeric', 'min:1'],
                'payment_method' => ['required', 'string', 'max:80'],
                'check_in_status' => ['required', 'string', 'max:80'],
                'checked_in_at' => ['nullable', 'date'],
            ],
        ],
    ];

    /**
     * Permission requise par module avance. Modules financiers -> accounting.post ;
     * rapprochement -> bank.reconcile ; counseling (sensible) -> members.manage ;
     * le reste -> services.manage. Source unique consommee par les routes web
     * et par le controleur API.
     */
    public const MODULE_PERMISSIONS = [
        'boutique-ressources' => Rbac::ACCOUNTING_POST,
        'fournisseurs' => Rbac::ACCOUNTING_POST,
        'paie' => Rbac::ACCOUNTING_POST,
        'rapprochements' => Rbac::BANK_RECONCILE,
        'reversements' => Rbac::ACCOUNTING_POST,
        'counseling' => Rbac::MEMBERS_MANAGE,
        'evangelisation' => Rbac::SERVICES_MANAGE,
        'qr-publics' => Rbac::SERVICES_MANAGE,
        'live-studio' => Rbac::SERVICES_MANAGE,
        'outils-ia' => Rbac::SERVICES_MANAGE,
        'familles' => Rbac::SERVICES_MANAGE,
        'discipolat' => Rbac::SERVICES_MANAGE,
        'mediatheque' => Rbac::SERVICES_MANAGE,
        'fonds-dedies' => Rbac::ACCOUNTING_POST,
        'mouvements-fonds' => Rbac::ACCOUNTING_POST,
        'inscriptions-evenements' => Rbac::ACCOUNTING_POST,
    ];

    public static function modules(): array
    {
        return array_keys(self::MODULES);
    }

    public static function permissionFor(string $module): string
    {
        return self::MODULE_PERMISSIONS[$module] ?? Rbac::SERVICES_MANAGE;
    }

    public function index(Request $request, AccessScope $scope, string $module): Response
    {
        $config = $this->config($module);
        $model = $config['model'];

        return Inertia::render('Operations/GenericModule', [
            'moduleKey' => $module,
            'module' => collect($config)->except(['model', 'rules'])->all(),
            'churches' => $scope->churches($request->user()),
            'options' => $this->options($module, $request, $scope),
            'items' => $scope->scopeChurchOwned($model::with('church:id,designation'), $request->user())->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, string $module, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $config = $this->config($module);
        $data = $request->validate($config['rules']);
        $scope->ensureChurchAllowed($request->user(), (int) $data['church_id']);
        $data = $this->prepareData($module, $data);
        $entry = $this->maybeCreateJournalEntry($module, $data, $request, $accounting);

        if ($entry) {
            $data['journal_entry_id'] = $entry->id;
        }

        $config['model']::create($data);

        return back()->with('success', $entry ? 'Module enregistre avec ecriture comptable.' : 'Module enregistre.');
    }

    public function payVendorBill(Request $request, VendorBill $vendorBill, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $vendorBill->church_id);

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:80'],
        ]);

        if ($vendorBill->journal_entry_id) {
            throw ValidationException::withMessages(['vendor_bill' => 'Cette facture est deja comptabilisee.']);
        }

        $vendorBill->payment_method = $data['payment_method'];
        $entry = $this->recordVendorBillEntry($vendorBill->toArray(), $request, $accounting);
        $vendorBill->update([
            'journal_entry_id' => $entry->id,
            'payment_method' => $data['payment_method'],
            'status' => 'paid',
        ]);

        Audit::record('payment.vendor_bill.paid', $vendorBill, [
            'vendor' => $vendorBill->vendor_name,
            'bill_number' => $vendorBill->bill_number,
            'amount' => (float) $vendorBill->amount,
            'currency' => $vendorBill->currency,
            'payment_method' => $data['payment_method'],
            'journal_entry_id' => $entry->id,
        ], (int) $vendorBill->church_id);

        return back()->with('success', 'Facture fournisseur payee et comptabilisee.');
    }

    public function payPayrollRun(Request $request, PayrollRun $payrollRun, AccountingService $accounting, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $payrollRun->church_id);

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:80'],
            'paid_at' => ['required', 'date'],
        ]);

        if ($payrollRun->journal_entry_id) {
            throw ValidationException::withMessages(['payroll_run' => 'Cette paie est deja comptabilisee.']);
        }

        $payrollRun->payment_method = $data['payment_method'];
        $payrollRun->paid_at = $data['paid_at'];
        $entry = $this->recordPayrollEntry($payrollRun->toArray(), $request, $accounting);
        $payrollRun->update([
            'journal_entry_id' => $entry->id,
            'payment_method' => $data['payment_method'],
            'paid_at' => $data['paid_at'],
            'status' => 'paid',
        ]);

        Audit::record('payment.payroll_run.paid', $payrollRun, [
            'period' => $payrollRun->period_label,
            'staff' => $payrollRun->staff_name,
            'net_amount' => (float) $payrollRun->net_amount,
            'currency' => $payrollRun->currency,
            'payment_method' => $data['payment_method'],
            'journal_entry_id' => $entry->id,
        ], (int) $payrollRun->church_id);

        return back()->with('success', 'Paie payee et comptabilisee.');
    }

    public function scheduleCounselingFollowUp(Request $request, CounselingCase $counselingCase, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $counselingCase->church_id);

        $data = $request->validate([
            'appointment_date' => ['required', 'date'],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:appointment_date'],
            'assigned_to' => ['required', 'string', 'max:255'],
            'last_follow_up_note' => ['nullable', 'string'],
        ]);

        if ($counselingCase->status === 'closed') {
            throw ValidationException::withMessages(['counseling_case' => 'Ce dossier est deja cloture.']);
        }

        $counselingCase->update([
            ...$data,
            'status' => 'scheduled',
        ]);

        return back()->with('success', 'Rendez-vous counseling planifie.');
    }

    public function closeCounselingCase(Request $request, CounselingCase $counselingCase, AccessScope $scope): RedirectResponse
    {
        $scope->ensureChurchAllowed($request->user(), (int) $counselingCase->church_id);

        $data = $request->validate([
            'last_follow_up_note' => ['required', 'string'],
        ]);

        $counselingCase->update([
            'status' => 'closed',
            'closed_at' => now(),
            'last_follow_up_note' => $data['last_follow_up_note'],
        ]);

        return back()->with('success', 'Dossier counseling cloture.');
    }

    public function prepareData(string $module, array $data): array
    {
        if ($module === 'boutique-ressources') {
            $data['total_amount'] = (float) $data['quantity'] * (float) $data['unit_price'];
        }

        return $data;
    }

    public function maybeCreateJournalEntry(string $module, array $data, Request $request, AccountingService $accounting): mixed
    {
        $cashAccount = $this->cashAccount($data['payment_method'] ?? 'cash');

        if ($module === 'boutique-ressources' && $data['status'] === 'paid') {
            return $accounting->recordBalancedEntry($this->entryPayload($data, $request, 'resource_sale', $data['sold_at'], "Vente boutique {$data['item_name']}", [
                ['account_code' => $cashAccount, 'label' => 'Encaissement boutique', 'debit' => $data['total_amount'], 'credit' => 0],
                ['account_code' => '704', 'label' => 'Revenus des ventes', 'debit' => 0, 'credit' => $data['total_amount']],
            ]));
        }

        if ($module === 'fournisseurs' && $data['status'] === 'paid') {
            return $this->recordVendorBillEntry($data, $request, $accounting);
        }

        if ($module === 'paie' && $data['status'] === 'paid') {
            return $this->recordPayrollEntry($data, $request, $accounting);
        }

        if ($module === 'reversements' && $data['status'] === 'paid') {
            return $accounting->recordBalancedEntry($this->entryPayload($data, $request, 'payout', $data['payout_date'], "Reversement {$data['beneficiary']}", [
                ['account_code' => '612', 'label' => $data['purpose'], 'debit' => $data['amount'], 'credit' => 0],
                ['account_code' => $cashAccount, 'label' => 'Paiement reversement', 'debit' => 0, 'credit' => $data['amount']],
            ]));
        }

        if ($module === 'mouvements-fonds' && $data['status'] === 'posted') {
            $entry = $accounting->recordBalancedEntry($this->entryPayload($data, $request, 'fund_movement', $data['movement_date'], $data['description'], [
                ['account_code' => $cashAccount, 'label' => 'Encaissement fonds dedie', 'debit' => $data['amount'], 'credit' => 0],
                ['account_code' => '703', 'label' => 'Don affecte au fonds', 'debit' => 0, 'credit' => $data['amount']],
            ]));
            Fund::whereKey($data['fund_id'])->increment('current_balance', $data['amount']);

            return $entry;
        }

        if ($module === 'inscriptions-evenements' && (float) ($data['amount_paid'] ?? 0) > 0) {
            return $accounting->recordBalancedEntry($this->entryPayload($data, $request, 'event_registration', now()->toDateString(), "Inscription evenement {$data['attendee_name']}", [
                ['account_code' => $cashAccount, 'label' => 'Encaissement inscription', 'debit' => $data['amount_paid'], 'credit' => 0],
                ['account_code' => '704', 'label' => 'Revenu evenement', 'debit' => 0, 'credit' => $data['amount_paid']],
            ]));
        }

        return null;
    }

    private function recordVendorBillEntry(array $data, Request $request, AccountingService $accounting): mixed
    {
        return $accounting->recordBalancedEntry($this->entryPayload($data, $request, 'vendor_bill', $data['bill_date'], "Paiement fournisseur {$data['vendor_name']}", [
            ['account_code' => '601', 'label' => $data['category'], 'debit' => $data['amount'], 'credit' => 0],
            ['account_code' => $this->cashAccount($data['payment_method']), 'label' => 'Paiement fournisseur', 'debit' => 0, 'credit' => $data['amount']],
        ]));
    }

    private function recordPayrollEntry(array $data, Request $request, AccountingService $accounting): mixed
    {
        $lines = [
            ['account_code' => '621', 'label' => "Salaire {$data['staff_name']}", 'debit' => $data['gross_amount'], 'credit' => 0],
            ['account_code' => $this->cashAccount($data['payment_method']), 'label' => 'Net paye', 'debit' => 0, 'credit' => $data['net_amount']],
        ];
        if ((float) ($data['social_charges'] ?? 0) > 0) {
            $lines[] = ['account_code' => '431', 'label' => 'Charges sociales a payer', 'debit' => 0, 'credit' => $data['social_charges']];
        }

        return $accounting->recordBalancedEntry($this->entryPayload($data, $request, 'payroll', $data['paid_at'] ?? now()->toDateString(), "Paie {$data['period_label']}", $lines));
    }

    private function entryPayload(array $data, Request $request, string $type, string $date, string $description, array $lines): array
    {
        return [
            'church_id' => $data['church_id'],
            'type' => $type,
            'entry_date' => $date,
            'description' => $description,
            'currency' => $data['currency'],
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'created_by' => $request->user()?->id,
            'lines' => $lines,
        ];
    }

    private function cashAccount(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bank', 'card' => '501',
            'mobile_money', 'mpesa', 'airtel_money', 'orange_money' => '515',
            default => '511',
        };
    }

    private function options(string $module, Request $request, AccessScope $scope): array
    {
        $churchIds = $scope->churchIds($request->user());

        return match ($module) {
            'mouvements-fonds' => [
                'funds' => Fund::select('id', 'code', 'name', 'currency', 'current_balance')
                    ->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Fund $fund) => [
                        'value' => $fund->id,
                        'label' => "{$fund->code} - {$fund->name} ({$fund->currency} {$fund->current_balance})",
                    ]),
            ],
            'inscriptions-evenements' => [
                'events' => ChurchEvent::select('id', 'title', 'starts_at', 'venue')
                    ->when(is_array($churchIds), fn ($query) => $query->whereIn('church_id', $churchIds))
                    ->orderByDesc('starts_at')
                    ->get()
                    ->map(fn (ChurchEvent $event) => [
                        'value' => $event->id,
                        'label' => "{$event->title} - {$event->venue}",
                    ]),
            ],
            default => [],
        };
    }

    private function config(string $module): array
    {
        abort_unless(Arr::has(self::MODULES, $module), 404);

        return self::MODULES[$module];
    }
}
