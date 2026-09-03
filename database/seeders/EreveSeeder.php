<?php

namespace Database\Seeders;

use App\Models\AiToolRequest;
use App\Models\BankReconciliation;
use App\Models\BoardMeeting;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\Child;
use App\Models\Church;
use App\Models\ChurchAsset;
use App\Models\ChurchEvent;
use App\Models\ChurchMediaItem;
use App\Models\ChurchPayout;
use App\Models\ChurchService;
use App\Models\Communication;
use App\Models\Community;
use App\Models\CounselingCase;
use App\Models\Currency;
use App\Models\DiscipleshipPath;
use App\Models\EventRegistration;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\FacilityBooking;
use App\Models\Family;
use App\Models\Fund;
use App\Models\FundMovement;
use App\Models\LiveStreamSession;
use App\Models\MinistryGroup;
use App\Models\NewConvert;
use App\Models\OutreachCampaign;
use App\Models\PaymentMethod;
use App\Models\PayrollRun;
use App\Models\Pledge;
use App\Models\PublicQrCode;
use App\Models\ResourceSale;
use App\Models\SecurityIncident;
use App\Models\SermonMedia;
use App\Models\ServiceRequest;
use App\Models\SolutionModule;
use App\Models\Survey;
use App\Models\Testimony;
use App\Models\TrainingCourse;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\Visitor;
use App\Models\VolunteerAssignment;
use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EreveSeeder extends Seeder
{
    public function run(): void
    {
        // SEC-26 : ce seeder cree des comptes de demonstration (mot de passe
        // "password"). Interdit en production sauf autorisation explicite.
        if (app()->environment('production') && ! filter_var(env('EREVE_ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            throw new \RuntimeException(
                'EreveSeeder est un jeu de demonstration : execution refusee en production. '
                .'Definir EREVE_ALLOW_DEMO_SEED=true pour forcer.'
            );
        }

        foreach ([['USD', 'Dollar americain', true], ['CDF', 'Franc congolais', false]] as [$code, $name, $base]) {
            Currency::updateOrCreate(['code' => $code], ['name' => $name, 'is_base' => $base]);
        }

        ExchangeRate::updateOrCreate(
            ['from_currency' => 'USD', 'to_currency' => 'CDF', 'rated_at' => now()->toDateString()],
            ['rate' => 2850, 'source' => 'manuel']
        );

        $accounts = [
            ['101', 'Fonds de dotation', 1, 'credit'], ['106', 'Reserves affectees', 1, 'credit'], ['110', 'Resultat net de l exercice', 1, 'credit'],
            ['211', 'Terrains de l eglise', 2, 'debit'], ['213', 'Constructions', 2, 'debit'], ['215', 'Materiel audio-visuel', 2, 'debit'], ['218', 'Mobilier et materiel de bureau', 2, 'debit'],
            ['311', 'Stocks de brochures et bibles', 3, 'debit'], ['312', 'Articles pour vente', 3, 'debit'], ['341', 'Produits alimentaires actions sociales', 3, 'debit'],
            ['401', 'Fournisseurs', 4, 'credit'], ['411', 'Membres debiteurs', 4, 'debit'], ['421', 'Personnel a payer', 4, 'credit'], ['431', 'CNSS INSS', 4, 'credit'], ['445', 'Etat impots et taxes', 4, 'credit'], ['465', 'Bailleurs de fonds partenaires', 4, 'credit'],
            ['501', 'Banque principale', 5, 'debit'], ['502', 'Banque projet', 5, 'debit'], ['511', 'Caisse', 5, 'debit'], ['515', 'Monnaie mobile', 5, 'debit'],
            ['601', 'Achats activites spirituelles', 6, 'debit'], ['611', 'Loyers et charges locatives', 6, 'debit'], ['612', 'Transport et deplacements', 6, 'debit'], ['621', 'Remunerations du personnel', 6, 'debit'], ['631', 'Charges sociales', 6, 'debit'], ['641', 'Dotations amortissements', 6, 'debit'], ['651', 'Charges exceptionnelles', 6, 'debit'],
            ['701', 'Dimes', 7, 'credit'], ['702', 'Offrandes', 7, 'credit'], ['703', 'Dons recus', 7, 'credit'], ['704', 'Revenus des ventes', 7, 'credit'], ['705', 'Subventions', 7, 'credit'], ['771', 'Produits exceptionnels', 7, 'credit'],
        ];

        foreach ($accounts as [$code, $label, $class, $side]) {
            ChartOfAccount::updateOrCreate(['code' => $code], ['label' => $label, 'class' => $class, 'normal_side' => $side, 'is_system' => true, 'is_active' => true]);
        }

        foreach ([['cash', 'Caisse', null], ['bank', 'Banque', null], ['card', 'Carte bancaire', null], ['mobile_money', 'Mobile Money', null], ['mpesa', 'M-Pesa', 'Vodacom'], ['orange_money', 'Orange Money', 'Orange'], ['airtel_money', 'Airtel Money', 'Airtel']] as [$code, $label, $provider]) {
            PaymentMethod::updateOrCreate(['code' => $code], ['label' => $label, 'provider' => $provider, 'is_active' => true]);
        }

        $this->call(RolePermissionSeeder::class);

        $community = Community::firstOrCreate([
            'authorization_number' => 'AUT-RDC-EREVE-001',
        ], [
            'designation' => 'Communaute Evangelique Lumiere',
            'headquarters_number' => '12',
            'headquarters_avenue' => 'Avenue Kasavubu',
            'headquarters_district' => 'Golf',
            'headquarters_city' => 'Lubumbashi',
            'headquarters_province' => 'Haut-Katanga',
            'headquarters_country' => 'RDC',
            'email' => 'coordination@ereve.cd',
            'phone' => '+243990000001',
        ]);

        $church = Church::firstOrCreate([
            'community_id' => $community->id,
            'designation' => 'Eglise Mont Sion',
        ], [
            'address_number' => '45',
            'address_avenue' => 'Avenue de la Paix',
            'address_district' => 'Golf',
            'address_city' => 'Lubumbashi',
            'address_province' => 'Haut-Katanga',
            'address_country' => 'RDC',
            'email' => 'montsion@ereve.cd',
            'phone' => '+243990000101',
        ]);

        User::firstOrCreate(['email' => 'proispos1@egmail.com'], [
            'name' => 'Administrateur eReve',
            'password' => Hash::make('password'),
            'member_id' => null,
            'church_id' => null,
            'community_id' => $community->id,
            'level' => Rbac::LEVEL_COORDINATION,
            'status' => 'actif',
        ])->syncRoles([Rbac::ADMINISTRATEUR]);

        User::firstOrCreate(['email' => 'plateforme@ereve.cd'], [
            'name' => 'SuperAdmin plateforme',
            'password' => Hash::make('password'),
            'member_id' => null,
            'church_id' => null,
            'community_id' => null,
            'level' => Rbac::LEVEL_PLATFORM,
            'status' => 'actif',
        ])->syncRoles([Rbac::SUPERADMIN_PLATEFORME]);

        foreach ([
            'adminfin@ereve.cd' => ['AdminFin Mont Sion', Rbac::ADMIN_FIN],
            'caissier@ereve.cd' => ['Caissier Mont Sion', Rbac::CAISSIER],
            'auditeur@ereve.cd' => ['Auditeur Mont Sion', Rbac::AUDITEUR],
            'secretaire@ereve.cd' => ['Secretaire Mont Sion', Rbac::SECRETAIRE],
        ] as $email => [$name, $role]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => Hash::make('password'),
                'member_id' => null,
                'church_id' => $church->id,
                'community_id' => $community->id,
                'level' => Rbac::LEVEL_EGLISE,
                'status' => 'actif',
            ])->syncRoles([$role]);
        }

        // Compte operations retro-compatible (utilise par la suite de tests) :
        // cumule les capacites financieres, secretariat et audit d'une eglise.
        User::firstOrCreate(['email' => 'eglise.admin@ereve.cd'], [
            'name' => 'Operations Mont Sion',
            'password' => Hash::make('password'),
            'member_id' => null,
            'church_id' => $church->id,
            'community_id' => $community->id,
            'level' => Rbac::LEVEL_EGLISE,
            'status' => 'actif',
        ])->syncRoles([Rbac::ADMIN_FIN, Rbac::SECRETAIRE, Rbac::AUDITEUR]);

        ChurchService::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Culte dominical principal',
        ], [
            'service_type' => 'culte',
            'starts_at' => now()->next('Sunday')->setTime(9, 0),
            'ends_at' => now()->next('Sunday')->setTime(12, 0),
            'preacher' => 'Pasteur titulaire',
            'worship_leader' => 'Responsable louange',
            'attendance_count' => 250,
            'notes' => 'Service initial de demonstration.',
        ]);

        MinistryGroup::firstOrCreate([
            'church_id' => $church->id,
            'name' => 'Cellule Golf Lumiere',
        ], [
            'group_type' => 'cellule',
            'leader_name' => 'Sarah Mbuyi',
            'meeting_day' => 'Mercredi',
            'district' => 'Golf',
            'city' => 'Lubumbashi',
            'members_count' => 18,
        ]);

        ChurchEvent::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Conference de reveil',
        ], [
            'event_type' => 'conference',
            'starts_at' => now()->addMonth()->setTime(17, 0),
            'ends_at' => now()->addMonth()->setTime(20, 0),
            'venue' => 'Temple Mont Sion',
            'currency' => 'CDF',
            'ticket_price' => 0,
            'capacity' => 1000,
            'registrations_count' => 120,
            'is_public' => true,
        ]);

        $budget = Budget::firstOrCreate([
            'church_id' => $church->id,
            'name' => 'Budget fonctionnement annuel',
        ], [
            'department' => 'Administration',
            'currency' => 'USD',
            'amount' => 12000,
            'period_starts_at' => now()->startOfYear()->toDateString(),
            'period_ends_at' => now()->endOfYear()->toDateString(),
            'status' => 'active',
        ]);

        Expense::firstOrCreate([
            'church_id' => $church->id,
            'description' => 'Achat brochures evangelisation',
        ], [
            'budget_id' => $budget->id,
            'journal_entry_id' => null,
            'vendor' => 'Librairie locale',
            'category' => 'evangelisation',
            'currency' => 'USD',
            'amount' => 120,
            'exchange_rate' => 2850,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'draft',
        ]);

        Visitor::firstOrCreate([
            'church_id' => $church->id,
            'full_name' => 'Jean Visiteur',
        ], [
            'phone' => '+243990000202',
            'email' => 'jean.visiteur@example.cd',
            'visit_source' => 'culte dominical',
            'visited_at' => now()->toDateString(),
            'follow_up_status' => 'a_relancer',
            'notes' => 'A invite a rejoindre une cellule de maison.',
        ]);

        NewConvert::firstOrCreate([
            'church_id' => $church->id,
            'full_name' => 'Marie Nouvelle',
        ], [
            'conversion_date' => now()->subWeek()->toDateString(),
            'discipleship_stage' => 'classe accueil',
            'mentor_name' => 'Sarah Mbuyi',
            'baptism_target_date' => now()->addMonths(2)->toDateString(),
            'status' => 'en_suivi',
            'notes' => 'Suivi pastoral et integration cellule.',
        ]);

        Child::firstOrCreate([
            'church_id' => $church->id,
            'full_name' => 'Enfant ecole du dimanche',
        ], [
            'guardian_member_id' => null,
            'birth_date' => '2018-05-10',
            'guardian_name' => 'Parent responsable',
            'guardian_phone' => '+243990000101',
            'classroom' => '6-8 ans',
            'check_in_code' => 'ENF-001',
            'checked_in' => true,
        ]);

        VolunteerAssignment::firstOrCreate([
            'church_id' => $church->id,
            'volunteer_name' => 'Sarah Mbuyi',
            'service_date' => now()->next('Sunday')->toDateString(),
        ], [
            'team' => 'Accueil',
            'role' => 'Chef equipe',
            'availability_status' => 'confirmed',
            'notes' => 'Service culte principal.',
        ]);

        TrainingCourse::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Formation responsables de cellules',
        ], [
            'category' => 'leadership',
            'instructor_name' => 'Pasteur titulaire',
            'starts_at' => now()->addWeek()->toDateString(),
            'ends_at' => now()->addWeeks(4)->toDateString(),
            'enrollments_count' => 25,
            'certificate_enabled' => true,
        ]);

        SermonMedia::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'La foi qui agit',
        ], [
            'preacher' => 'Pasteur titulaire',
            'preached_at' => now()->subWeek()->toDateString(),
            'bible_reference' => 'Jacques 2:17',
            'media_type' => 'audio',
            'public_url' => 'https://example.com/sermons/la-foi-qui-agit',
            'is_public' => true,
            'notes' => 'Sermon de demonstration.',
        ]);

        SecurityIncident::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Controle sortie enfant',
        ], [
            'incident_type' => 'children',
            'severity' => 'low',
            'occurred_at' => now()->subDay(),
            'reported_by' => 'Responsable enfants',
            'status' => 'closed',
            'description' => 'Verification du tuteur avant sortie, aucun dommage.',
        ]);

        Communication::firstOrCreate([
            'church_id' => $church->id,
            'subject' => 'Annonce culte special',
        ], [
            'channel' => 'whatsapp',
            'audience' => 'membres actifs',
            'body' => 'Invitation au culte special de priere et information aux responsables de cellules.',
            'scheduled_at' => now()->addDays(2)->setTime(8, 0),
            'status' => 'scheduled',
        ]);

        ServiceRequest::firstOrCreate([
            'church_id' => $church->id,
            'requester_name' => 'Famille Ilunga',
            'request_type' => 'assistance sociale',
        ], [
            'priority' => 'haute',
            'assigned_to' => 'Diaconie',
            'due_at' => now()->addDays(5)->toDateString(),
            'status' => 'open',
            'description' => 'Demande de visite pastorale et appui alimentaire temporaire.',
        ]);

        FacilityBooking::firstOrCreate([
            'church_id' => $church->id,
            'facility_name' => 'Salle polyvalente',
            'starts_at' => now()->addWeeks(2)->setTime(14, 0),
        ], [
            'requester_name' => 'Departement Jeunesse',
            'ends_at' => now()->addWeeks(2)->setTime(18, 0),
            'fee_currency' => 'CDF',
            'fee_amount' => 0,
            'payment_status' => 'internal',
            'notes' => 'Repetition conference jeunesse.',
        ]);

        ChurchAsset::firstOrCreate([
            'asset_code' => 'ACT-AUDIO-001',
        ], [
            'church_id' => $church->id,
            'name' => 'Console audio principale',
            'category' => 'audio-visuel',
            'location' => 'Temple Mont Sion',
            'purchase_date' => now()->subYear()->toDateString(),
            'value_currency' => 'USD',
            'value_amount' => 1200,
            'condition_status' => 'bon',
            'custodian' => 'Equipe media',
        ]);

        BoardMeeting::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Conseil paroissial mensuel',
        ], [
            'meeting_date' => now()->addWeek()->toDateString(),
            'chairperson' => 'Pasteur titulaire',
            'quorum_count' => 8,
            'decisions' => 'Valider le calendrier evangelisation et le budget entretien du temple.',
            'status' => 'draft',
        ]);

        Pledge::firstOrCreate([
            'church_id' => $church->id,
            'donor_name' => 'Donateur construction',
            'campaign' => 'Construction annexe enfants',
        ], [
            'currency' => 'USD',
            'pledged_amount' => 500,
            'received_amount' => 150,
            'due_date' => now()->addMonths(3)->toDateString(),
            'status' => 'active',
        ]);

        Survey::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Feedback culte dominical',
        ], [
            'audience' => 'membres et visiteurs',
            'opens_at' => now()->toDateString(),
            'closes_at' => now()->addWeek()->toDateString(),
            'responses_count' => 12,
            'status' => 'open',
        ]);

        Testimony::firstOrCreate([
            'church_id' => $church->id,
            'author_name' => 'Membre temoignant',
            'testimony_date' => now()->toDateString(),
        ], [
            'category' => 'guerison',
            'moderation_status' => 'approved',
            'is_public' => true,
            'content' => 'Temoignage valide par le responsable pastoral avant publication.',
        ]);

        ResourceSale::firstOrCreate([
            'church_id' => $church->id,
            'item_name' => 'Bible francais courant',
        ], [
            'journal_entry_id' => null,
            'buyer_name' => 'Jean Visiteur',
            'quantity' => 3,
            'currency' => 'CDF',
            'unit_price' => 15000,
            'total_amount' => 45000,
            'exchange_rate' => 2850,
            'payment_method' => 'cash',
            'status' => 'draft',
            'sold_at' => now()->toDateString(),
        ]);

        VendorBill::firstOrCreate([
            'church_id' => $church->id,
            'vendor_name' => 'Sono Katanga Services',
            'bill_number' => 'FAC-SONO-001',
        ], [
            'journal_entry_id' => null,
            'category' => 'maintenance',
            'currency' => 'USD',
            'amount' => 180,
            'exchange_rate' => 2850,
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'payment_method' => 'bank',
            'status' => 'pending',
        ]);

        PayrollRun::firstOrCreate([
            'church_id' => $church->id,
            'period_label' => 'Juin 2026',
            'staff_name' => 'Secretaire paroissiale',
        ], [
            'journal_entry_id' => null,
            'role' => 'Administration',
            'currency' => 'USD',
            'gross_amount' => 600,
            'social_charges' => 50,
            'net_amount' => 550,
            'exchange_rate' => 2850,
            'payment_method' => 'bank',
            'status' => 'draft',
            'paid_at' => null,
        ]);

        BankReconciliation::firstOrCreate([
            'church_id' => $church->id,
            'account_name' => 'Banque principale USD',
            'statement_date' => now()->toDateString(),
        ], [
            'currency' => 'USD',
            'book_balance' => 0,
            'statement_balance' => 0,
            'difference_amount' => 0,
            'status' => 'open',
            'notes' => 'Rapprochement vierge a completer apres import du releve bancaire.',
        ]);

        ChurchPayout::firstOrCreate([
            'church_id' => $church->id,
            'beneficiary' => 'Coordination provinciale',
            'purpose' => 'Contribution mensuelle',
        ], [
            'journal_entry_id' => null,
            'currency' => 'USD',
            'amount' => 250,
            'exchange_rate' => 2850,
            'payout_date' => now()->toDateString(),
            'payment_method' => 'bank',
            'status' => 'pending',
        ]);

        CounselingCase::firstOrCreate([
            'case_code' => 'CARE-001',
        ], [
            'church_id' => $church->id,
            'requester_name' => 'Couple pastoral accompagne',
            'care_type' => 'familial',
            'assigned_to' => 'Pasteur titulaire',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'confidentiality_level' => 'confidentiel',
            'status' => 'open',
            'summary' => 'Dossier de demonstration avec acces restreint au ministere pastoral.',
        ]);

        OutreachCampaign::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Evangelisation quartier Golf',
        ], [
            'location' => 'Golf, Lubumbashi',
            'starts_at' => now()->addWeek()->toDateString(),
            'ends_at' => now()->addWeek()->addDays(2)->toDateString(),
            'volunteers_count' => 30,
            'contacts_count' => 120,
            'conversions_count' => 18,
            'status' => 'planned',
            'notes' => 'Campagne liee aux cellules de maison.',
        ]);

        PublicQrCode::firstOrCreate([
            'short_code' => 'DON-MTS-001',
        ], [
            'church_id' => $church->id,
            'label' => 'Don Mobile Money Mont Sion',
            'target_type' => 'don',
            'target_url' => 'https://ereve.cd/don/mont-sion',
            'scan_count' => 42,
            'is_active' => true,
        ]);

        LiveStreamSession::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Live culte dominical',
        ], [
            'starts_at' => now()->next('Sunday')->setTime(9, 0),
            'platform' => 'facebook',
            'stream_url' => 'https://facebook.com/ereve/live',
            'fallback_mode' => 'audio',
            'status' => 'scheduled',
            'notes' => 'Fallback audio prevu pour faible debit.',
        ]);

        AiToolRequest::firstOrCreate([
            'church_id' => $church->id,
            'prompt_title' => 'Annonce campagne evangelisation',
        ], [
            'tool_type' => 'redaction',
            'requested_by' => 'Secretariat',
            'prompt_context' => 'Preparer une annonce pastorale courte pour inviter les membres a la campagne.',
            'human_review_status' => 'pending',
            'output_summary' => 'Brouillon a valider avant publication.',
        ]);

        Family::firstOrCreate([
            'church_id' => $church->id,
            'household_name' => 'Foyer de demonstration',
        ], [
            'primary_contact_name' => 'Contact familial',
            'phone' => '+243990000101',
            'district' => 'Golf',
            'city' => 'Lubumbashi',
            'members_count' => 5,
            'status' => 'active',
            'notes' => 'Foyer rattache a la cellule Golf Lumiere.',
        ]);

        DiscipleshipPath::firstOrCreate([
            'church_id' => $church->id,
            'participant_name' => 'Marie Nouvelle',
        ], [
            'track_name' => 'Fondements de la foi',
            'current_step' => 'bapteme',
            'progress_percent' => 45,
            'mentor_name' => 'Sarah Mbuyi',
            'next_meeting_at' => now()->addWeek()->toDateString(),
            'status' => 'active',
        ]);

        ChurchMediaItem::firstOrCreate([
            'church_id' => $church->id,
            'title' => 'Affiche conference de reveil',
        ], [
            'media_type' => 'image',
            'category' => 'evenement',
            'storage_url' => 'https://ereve.cd/media/conference-reveil.jpg',
            'copyright_status' => 'interne',
            'offline_available' => true,
            'status' => 'published',
            'notes' => 'Asset de communication disponible hors-ligne.',
        ]);

        $fund = Fund::firstOrCreate([
            'code' => 'FND-CONSTRUCTION',
        ], [
            'church_id' => $church->id,
            'name' => 'Fonds construction annexe enfants',
            'restriction_type' => 'affecte',
            'currency' => 'USD',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
            'notes' => 'Fonds dedie suivi separement du fonctionnement.',
        ]);

        FundMovement::firstOrCreate([
            'church_id' => $church->id,
            'fund_id' => $fund->id,
            'description' => 'Promesse affectee fonds construction',
        ], [
            'journal_entry_id' => null,
            'movement_type' => 'receipt',
            'source_name' => 'Donateur affecte',
            'currency' => 'USD',
            'amount' => 300,
            'exchange_rate' => 2850,
            'movement_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'status' => 'draft',
        ]);

        EventRegistration::firstOrCreate([
            'ticket_code' => 'EVT-REV-001',
        ], [
            'church_id' => $church->id,
            'church_event_id' => ChurchEvent::where('title', 'Conference de reveil')->value('id'),
            'journal_entry_id' => null,
            'attendee_name' => 'Jean Visiteur',
            'phone' => '+243990000202',
            'currency' => 'CDF',
            'amount_paid' => 0,
            'exchange_rate' => 2850,
            'payment_method' => 'mobile_money',
            'check_in_status' => 'registered',
        ]);

        $this->seedSolutionModules();
    }

    private function seedSolutionModules(): void
    {
        $modules = [
            ['members', 'Membres', 'People', 'Registre des membres, profils, statuts, historique et recherche.', 'Members', 'Statuts sympathisant, adherant, actif, effectif; piece RDC et rattachement obligatoire a une eglise.', true],
            ['families', 'Familles', 'People', 'Menages, liens familiaux, responsables et contacts du foyer.', 'Families', 'Gestion des foyers congolais, tuteurs, conjoints et dependants.', true],
            ['branches', 'Branches / eglises', 'Organisation', 'Gestion multi-sites et succursales ecclesiales.', 'Branches', 'Communautes et eglises rattachees avec adresse RDC complete et autorisation de fonctionnement.', true],
            ['services', 'Cultes et services', 'Ministry', 'Planification des cultes, conducteurs, chants, predications et presences.', 'Services', 'Feuilles de culte, equipes liturgiques et suivi par eglise locale.', true],
            ['giving', 'Dons et collectes', 'Finance', 'Dimes, offrandes, dons, pledges et paiements publics.', 'Giving', 'USD/CDF, Mobile Money, caisse, banque et generation automatique des ecritures SYCEBNL.', true],
            ['groups', 'Groupes', 'Ministry', 'Groupes, cellules, departements et inscriptions.', 'Groups', 'Cellules de maison, chorales, mamans, jeunesse, hommes et departements locaux.', true],
            ['house_fellowships', 'Cellules de maison', 'Ministry', 'Fellowships par quartier, responsables et membres.', 'House Fellowships', 'Suivi des cellules par quartier, avenue et ville.', false],
            ['events', 'Evenements', 'Engagement', 'Evenements, inscriptions, tickets, QR codes et scanners.', 'Events', 'Conventions, croisades, retraites, tickets CDF/USD et controle QR.', true],
            ['communications', 'Communications', 'Engagement', 'Messages, annonces, planification et fils de discussion.', 'Communications', 'Canaux de communication internes.', true],
            ['visitors', 'Visiteurs', 'Engagement', 'Enregistrement visiteurs, suivi et relance.', 'Visitors', 'Accueil des visiteurs de culte et conversion en sympathisants.', false],
            ['new_converts', 'Nouveaux convertis', 'Discipleship', 'Suivi des nouveaux convertis, jalons et graduation.', 'New Converts', 'Parcours de consolidation, bapteme et integration dans cellules.', false],
            ['discipleship', 'Discipolat', 'Discipleship', 'Ressources, parcours, milestones et tableaux de suivi.', 'Discipleship Dashboard', 'Classes bibliques, niveaux de formation et certificats.', false],
            ['media', 'Media eglise', 'Media', 'Bibliotheque media, images, fichiers audio/video et diffusion.', 'Church Media', 'Archivage sermons, photos, videos et affiches.', false],
            ['sermons', 'Sermons', 'Media', 'Predications, notes, preparation, Bible lookup et diffusion publique.', 'Sermons', 'Bibliotheque de predications, notes et diffusion offline possible.', false],
            ['live_stream', 'Live stream / studio', 'Media', 'Streaming, studio, sorties video et affichages.', 'Live Stream / Church Studio', 'Diffusion culte live avec faibles debits et fallback audio.', false],
            ['expenses', 'Depenses', 'Finance', 'Demandes de depenses, approbations et categories.', 'Expenses', 'Circuit de requisition, validation tresorier/pasteur et pieces justificatives.', true],
            ['budgets', 'Budgets', 'Finance', 'Budgets par departement et suivi des consommations.', 'Budgets', 'Budgets annuels par eglise, departement et projet.', true],
            ['fund_accounting', 'Comptabilite par fonds', 'Finance', 'Fonds dedies, restrictions et affectations.', 'Fund Accounting', 'Fonds construction, action sociale, missions et jeunesse.', true],
            ['general_ledger', 'Grand livre', 'Finance', 'Journal, grand livre, balance et exports.', 'General Ledger', 'Plan SYCEBNL/SYSCOHADA, PDF/Excel, clotures et contrepassations.', true],
            ['accounts_payable', 'Fournisseurs', 'Finance', 'Comptes fournisseurs, factures et paiements.', 'Accounts Payable', 'Fournisseurs locaux, devis, factures et paiements caisse/banque/mobile money.', false],
            ['bank_reconciliation', 'Rapprochement bancaire', 'Finance', 'Rapprochement banque, caisse et paiements.', 'Bank Reconciliation', 'Rapprochement banque, caisse physique et Mobile Money.', true],
            ['payroll', 'Paie', 'Finance', 'Personnel, remuneration et charges sociales.', 'Payroll', 'Paie staff, CNSS/INSS et obligations locales.', false],
            ['payouts', 'Reversements', 'Finance', 'Payouts, retraits et transferts.', 'Church Payouts', 'Reversements coordination-eglises et suivi multi-devises.', false],
            ['counseling', 'Counseling', 'Care', 'Demandes de counseling, rendez-vous et reponses.', 'Counseling', 'Accompagnement pastoral confidentiel avec acces restreint.', false],
            ['assets', 'Patrimoine', 'Administration', 'Actifs, inventaire et sorties de biens.', 'Assets', 'Temples, terrains, mobilier, audio-visuel et contrats.', false],
            ['board_meetings', 'Conseils et reunions', 'Administration', 'Reunions, decisions, documents et signatures.', 'Board Meetings', 'Proces-verbaux, conseils paroissiaux et coordination.', false],
            ['facility_booking', 'Reservation locaux', 'Administration', 'Reservations de salles, contrats et signatures.', 'Facility Booking', 'Location temple/salles, contrats et paiements.', false],
            ['resource_store', 'Boutique ressources', 'Commerce', 'Vente de livres, CD, gadgets et marketplace.', 'Resource Store', 'Stocks brochures/bibles, ventes USD/CDF et ecritures de revenus.', false],
            ['volunteering', 'Volontariat', 'Ministry', 'Inscriptions volontaires, equipes et disponibilites.', 'Volunteering', 'Equipes protocole, chorale, media, accueil et securite.', false],
            ['outreach', 'Evangelisation', 'Mission', 'Initiatives missionnaires, campagnes et suivi.', 'Outreach', 'Croisades, visites, campagnes dans quartiers et villages.', false],
            ['training', 'Formations', 'Education', 'Cours, inscriptions, lecons, grading et certificats.', 'Training', 'Institut biblique, formations responsables et certificats verifiables.', false],
            ['childrens_church', 'Eglise des enfants', 'Children', 'Enfants, check-in, parent portal et securite.', 'Childrens Church', 'Enregistrement enfants, responsables, sortie securisee.', false],
            ['security', 'Securite', 'Administration', 'Incidents, rapports et gestion de risques.', 'Security / Incident Management', 'Incidents de culte, securite enfant et journal confidentiel.', false],
            ['pledges', 'Promesses de dons', 'Finance', 'Campagnes, engagements et rapports.', 'Pledges', 'Promesses construction/projets, suivi en USD/CDF et relances.', false],
            ['surveys', 'Sondages', 'Engagement', 'Sondages publics et analyses.', 'Surveys', 'Consultations des membres et feedback de culte.', false],
            ['testimonies', 'Temoignages', 'Engagement', 'Soumission, moderation et publication de temoignages.', 'Testimonies', 'Temoignages publics avec validation pastorale.', false],
            ['service_requests', 'Demandes de service', 'Care', 'Demandes membres, suivi et affectation.', 'Service Requests', 'Demandes pastorales, assistance sociale et suivi SLA interne.', false],
            ['qr_codes', 'QR codes publics', 'Engagement', 'QR inscriptions, dons, sermons et formulaires.', 'QR Codes', 'QR pour dons Mobile Money, visiteurs, evenements et enfants.', false],
            ['ai_tools', 'Outils IA', 'Productivity', 'Assistant, redaction, design et automatisations.', 'AI Tools', 'Assistance redactionnelle pastorale avec controle humain.', false],
        ];

        foreach ($modules as [$code, $name, $category, $description, $reference, $rdc, $core]) {
            SolutionModule::updateOrCreate(['code' => $code], [
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'church_central_reference' => $reference,
                'rdc_adaptation' => $rdc,
                'status' => 'active',
                'is_core' => $core,
            ]);
        }
    }
}
