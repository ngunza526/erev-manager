# eReve Church SaaS

SaaS Laravel + Vue 3/Inertia pour la gestion complete d'une communaute evangelique en RDC: multi-eglises, USD/CDF, Mobile Money, offline/PWA, gouvernance pastorale et comptabilite en partie double.

## Stack

- Laravel 12, PHP 8.2+
- Vue 3, Inertia.js, Vite
- Tailwind CSS 4 et Bootstrap 5 integres au bundle Vite
- MySQL/MariaDB en production, SQLite possible en local
- Sanctum, roles et permissions
- Exports PDF/Excel
- PWA manifest + service worker
- IndexedDB pour file offline et synchronisation back-office/mobile

## Couverture fonctionnelle

Le catalogue `/solutions` cartographie 39 modules fonctionnels vers leurs routes internes ou flux publics: membres, familles, eglises, cultes, groupes, evenements, dons, visiteurs, convertis, discipolat, enfants/check-in, media, sermons, live studio, communications, budgets, depenses, fournisseurs, paie, fonds dedies, rapprochements, reversements, counseling, patrimoine, conseils, reservations, boutique, evangelisation, formations, securite, promesses, sondages, temoignages, demandes de service, QR publics, roles/permissions et outils IA.

Les operations financieres importantes generent des ecritures comptables: dimes, offrandes, dons, inscriptions payees, ventes boutique, depenses, factures fournisseurs, paie, reversements et mouvements de fonds. Les rapports incluent balance PDF/Excel, bilan synthetique OHADA/SYCEBNL, tableau de formation du resultat et annexes tresorerie/fonds/creances/dettes.

## API REST Sanctum

Une API REST authentifiee par token Sanctum est disponible pour les clients mobiles ou integrations:

- Reference endpoint par endpoint: `API_REFERENCE.md`.
- Specification OpenAPI consommable: `OPENAPI.json`.
- Audit de perimetre route par route: `ROUTE_SCOPE_AUDIT.md`.
- `POST /api/auth/token`: cree un token Bearer avec `email`, `password`, `device_name`.
- `GET /api/me`: utilisateur courant.
- `GET /api/churches`: eglises visibles selon le perimetre utilisateur.
- `GET /api/members`, `POST /api/members`, `PUT /api/members/{id}`: membres scopes au perimetre eglise/coordination.
- `POST /api/services`, `PUT /api/services/{id}`: cultes et services.
- `POST /api/groups`, `PUT /api/groups/{id}`: cellules et groupes.
- `POST /api/events`, `PUT /api/events/{id}`: evenements.
- `POST /api/budgets`, `PUT /api/budgets/{id}`: budgets.
- `POST /api/expenses`, `PUT /api/expenses/{id}`: depenses en brouillon/approuvees sans decaissement, avec ecriture comptable automatique seulement lorsque la depense est `paid`.
- `GET|POST /api/pastoral/{module}` et `PUT /api/pastoral/{module}/{id}`: visiteurs, convertis, enfants, volontaires, formations, sermons/media et incidents.
- `GET|POST /api/administration/{module}` et `PUT /api/administration/{module}/{id}`: communications, demandes, reservations, patrimoine, conseils, promesses, sondages et temoignages.
- `GET|POST /api/advanced/{module}` et `PUT /api/advanced/{module}/{id}`: boutique, fournisseurs, paie, rapprochements, reversements, counseling, evangelisation, QR, live studio, outils IA, familles, discipolat, mediatheque, fonds, mouvements de fonds et inscriptions evenements. Les modules financiers declenchent les memes ecritures comptables que le back-office.
- `GET /api/accounting/entries`: journal comptable scope au perimetre.
- `GET /api/solutions`: catalogue de modules.
- `GET /api/media/offline-manifest`: manifeste des medias publies et autorises a precacher hors-ligne par eglise.
- `POST /api/media/uploads`, `GET /api/media/uploads/{id}`, `POST /api/media/uploads/{id}/chunks`, `POST /api/media/uploads/{id}/complete`: upload media reprenable par morceaux avec assemblage serveur, creation du media publie et scoping par eglise.
- `POST /api/auth/logout`: revoke le token courant.

## Offline-first

Le front expose `window.ereveOffline` pour mettre en file IndexedDB des actions terrain lorsque la connexion est instable. La synchronisation poste ensuite vers `/offline/sync` en session web ou `/api/offline/sync` avec Bearer token. Les ecrans financiers utilisent le taux USD/CDF du jour stocke dans `exchange_rates`.

Types de donnees synchronises et traites cote serveur:

- `member`: creation de membre avec statut RDC `sympathisant`.
- `visitor`: enregistrement visiteur et suivi `a_relancer`.
- `donation`: comptabilisation automatique dime/offrande/don.
- `event_registration`: ticket evenement et ecriture comptable si paiement.
- `manual_journal_entry`: saisie debit/credit hors-ligne avec controle d'equilibre.

Chaque lot offline est idempotent par couple `device_id` + `client_batch_id`, afin d'eviter les doublons apres coupure reseau.
Cette idempotence est scopee par eglise: deux eglises peuvent utiliser le meme identifiant de lot sans collision, et un utilisateur d'eglise ne peut ni creer ni rejouer un lot hors de son perimetre.
La mediatheque expose aussi `window.ereveOfflineMedia.cacheAvailable()` pour recuperer `/api/media/offline-manifest` et demander au service worker de precacher les fichiers publies, marques disponibles hors-ligne et autorises dans le perimetre utilisateur.
Les clients mobiles peuvent envoyer les fichiers audio/video/documents par morceaux via `/api/media/uploads`: une session garde les morceaux deja recus, autorise la reprise apres coupure reseau et publie automatiquement le media final comme disponible hors-ligne.

## Installation locale

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8088
```

Compte de demonstration:

- Email: `proispos2@gmail.com`
- Mot de passe: `password`

## Verification avant livraison

```bash
composer validate --no-check-publish
composer audit
npm audit --audit-level=moderate
npm run build
php artisan migrate:fresh --seed
php artisan test
```

## Flux publics

Apres seed, des exemples de flux publics sont disponibles:

- Don public: `/public/eglises/1/don`
- Enregistrement visiteur: `/public/eglises/1/visiteur`
- Inscription evenement: `/public/evenements/1`

## Note stack

La version locale utilise Laravel 12 afin de conserver une base securisee et maintenable avec Vue 3, Inertia, MySQL, Sanctum, roles et permissions.
Tailwind CSS et Bootstrap sont installes via npm, importes dans `resources/css/app.css`, et Bootstrap JS est charge dans `resources/js/app.js`.
