<?php

namespace App\Support;

class SolutionImplementationMap
{
    public static function for(string $code): array
    {
        return self::all()[$code] ?? [
            'level' => 'missing',
            'label' => 'Non cartographie',
            'path' => null,
            'public_path' => null,
            'proof' => 'Aucune route associee.',
        ];
    }

    public static function all(): array
    {
        return [
            'members' => ['level' => 'implemented', 'label' => 'Registre membres', 'path' => '/membres', 'public_path' => null, 'proof' => 'CRUD membre, statuts RDC et rattachement eglise.'],
            'families' => ['level' => 'implemented', 'label' => 'Familles', 'path' => '/familles', 'public_path' => null, 'proof' => 'Foyers, contact principal, quartier et composition.'],
            'branches' => ['level' => 'implemented', 'label' => 'Eglises', 'path' => '/eglises', 'public_path' => null, 'proof' => 'Communautes et eglises multi-sites.'],
            'services' => ['level' => 'implemented', 'label' => 'Services', 'path' => '/services', 'public_path' => null, 'proof' => 'Cultes, horaires, predicateur et presences.'],
            'giving' => ['level' => 'implemented', 'label' => 'Collectes', 'path' => '/comptabilite', 'public_path' => '/public/eglises/1/don', 'proof' => 'Dimes/offrandes/dons avec ecritures automatiques.'],
            'groups' => ['level' => 'implemented', 'label' => 'Groupes', 'path' => '/groupes', 'public_path' => null, 'proof' => 'Cellules, ministeres et departements.'],
            'house_fellowships' => ['level' => 'covered', 'label' => 'Cellules dans Groupes', 'path' => '/groupes', 'public_path' => null, 'proof' => 'Groupes type cellule avec quartier et responsable.'],
            'events' => ['level' => 'implemented', 'label' => 'Evenements', 'path' => '/evenements', 'public_path' => '/public/evenements/1', 'proof' => 'Evenements + inscriptions publiques et tickets.'],
            'communications' => ['level' => 'implemented', 'label' => 'Communications', 'path' => '/communications', 'public_path' => null, 'proof' => 'Canal, audience, planification et statut.'],
            'visitors' => ['level' => 'implemented', 'label' => 'Visiteurs', 'path' => '/visiteurs', 'public_path' => '/public/eglises/1/visiteur', 'proof' => 'Accueil et suivi public/interne.'],
            'new_converts' => ['level' => 'implemented', 'label' => 'Convertis', 'path' => '/convertis', 'public_path' => null, 'proof' => 'Suivi conversion, mentor et bapteme cible.'],
            'discipleship' => ['level' => 'implemented', 'label' => 'Discipolat', 'path' => '/discipolat', 'public_path' => null, 'proof' => 'Parcours, etapes, mentor et progression.'],
            'media' => ['level' => 'implemented', 'label' => 'Mediatheque', 'path' => '/mediatheque', 'public_path' => null, 'proof' => 'Bibliotheque media et disponibilite hors-ligne.'],
            'sermons' => ['level' => 'implemented', 'label' => 'Sermons', 'path' => '/sermons-media', 'public_path' => null, 'proof' => 'Predications, reference biblique, URL publique.'],
            'live_stream' => ['level' => 'implemented', 'label' => 'Live studio', 'path' => '/live-studio', 'public_path' => null, 'proof' => 'Streaming et fallback audio faible debit.'],
            'expenses' => ['level' => 'implemented', 'label' => 'Depenses', 'path' => '/depenses', 'public_path' => null, 'proof' => 'Depense brouillon/approuvee sans decaissement, puis ecriture au paiement.'],
            'budgets' => ['level' => 'implemented', 'label' => 'Budgets', 'path' => '/budgets', 'public_path' => null, 'proof' => 'Budget par departement et periode.'],
            'fund_accounting' => ['level' => 'implemented', 'label' => 'Fonds dedies', 'path' => '/fonds-dedies', 'public_path' => null, 'proof' => 'Fonds + mouvements avec journal comptable.'],
            'general_ledger' => ['level' => 'implemented', 'label' => 'Grand livre', 'path' => '/comptabilite', 'public_path' => null, 'proof' => 'Journal, lignes debit/credit et exports balance.'],
            'accounts_payable' => ['level' => 'implemented', 'label' => 'Fournisseurs', 'path' => '/fournisseurs', 'public_path' => null, 'proof' => 'Factures fournisseurs et paiements comptabilises.'],
            'bank_reconciliation' => ['level' => 'implemented', 'label' => 'Rapprochements', 'path' => '/rapprochements', 'public_path' => null, 'proof' => 'Livre vs releve banque/caisse/mobile money.'],
            'payroll' => ['level' => 'implemented', 'label' => 'Paie', 'path' => '/paie', 'public_path' => null, 'proof' => 'Paie, charges sociales et ecriture.'],
            'payouts' => ['level' => 'implemented', 'label' => 'Reversements', 'path' => '/reversements', 'public_path' => null, 'proof' => 'Reversements coordination-eglises comptabilises.'],
            'counseling' => ['level' => 'implemented', 'label' => 'Counseling', 'path' => '/counseling', 'public_path' => null, 'proof' => 'Dossiers confidentiels et rendez-vous.'],
            'assets' => ['level' => 'implemented', 'label' => 'Patrimoine', 'path' => '/patrimoine', 'public_path' => null, 'proof' => 'Actifs, localisation, valeur et responsable.'],
            'board_meetings' => ['level' => 'implemented', 'label' => 'Conseils', 'path' => '/conseils-reunions', 'public_path' => null, 'proof' => 'PV, decisions, quorum et statut.'],
            'facility_booking' => ['level' => 'implemented', 'label' => 'Reservations', 'path' => '/reservations-locaux', 'public_path' => null, 'proof' => 'Locaux, horaires, frais et paiement.'],
            'resource_store' => ['level' => 'implemented', 'label' => 'Boutique', 'path' => '/boutique-ressources', 'public_path' => null, 'proof' => 'Vente ressources avec revenus comptables.'],
            'volunteering' => ['level' => 'implemented', 'label' => 'Volontaires', 'path' => '/volontaires', 'public_path' => null, 'proof' => 'Equipe, role, date service et disponibilite.'],
            'outreach' => ['level' => 'implemented', 'label' => 'Evangelisation', 'path' => '/evangelisation', 'public_path' => null, 'proof' => 'Campagnes, contacts, conversions.'],
            'training' => ['level' => 'implemented', 'label' => 'Formations', 'path' => '/formations', 'public_path' => null, 'proof' => 'Cours, inscriptions, certificats.'],
            'childrens_church' => ['level' => 'implemented', 'label' => 'Enfants', 'path' => '/enfants', 'public_path' => null, 'proof' => 'Check-in enfant et tuteur.'],
            'security' => ['level' => 'implemented', 'label' => 'Incidents', 'path' => '/incidents', 'public_path' => null, 'proof' => 'Incidents, gravite, statut et rapport.'],
            'pledges' => ['level' => 'implemented', 'label' => 'Promesses', 'path' => '/promesses-dons', 'public_path' => null, 'proof' => 'Campagnes, promis, recu et echeance.'],
            'surveys' => ['level' => 'implemented', 'label' => 'Sondages', 'path' => '/sondages', 'public_path' => null, 'proof' => 'Audience, dates et reponses.'],
            'testimonies' => ['level' => 'implemented', 'label' => 'Temoignages', 'path' => '/temoignages', 'public_path' => null, 'proof' => 'Moderation et publication.'],
            'service_requests' => ['level' => 'implemented', 'label' => 'Demandes', 'path' => '/demandes-service', 'public_path' => null, 'proof' => 'Demandeur, priorite, affectation et SLA.'],
            'qr_codes' => ['level' => 'implemented', 'label' => 'QR publics', 'path' => '/qr-publics', 'public_path' => '/public/eglises/1/don', 'proof' => 'QR cible + flux publics dons/visiteurs/evenements.'],
            'ai_tools' => ['level' => 'implemented', 'label' => 'Outils IA', 'path' => '/outils-ia', 'public_path' => null, 'proof' => 'Demandes IA avec revue humaine.'],
        ];
    }
}
