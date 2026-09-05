# Rapport d'audit securite — eReve Church SaaS

Audit realise sur la branche `main` (apres Phase 1a-1e RBAC + journal d'audit).
Perimetre : authentification, autorisation, exposition des donnees, uploads,
configuration, endpoints publics. Chaque conclusion porte un identifiant `SEC-XX`
poursuivant la numerotation du projet.

Legende gravite : **Haute** (exploitation directe / impact fort) ·
**Moyenne** (exploitation conditionnee ou impact limite) ·
**Basse** (durcissement).

---

## SEC-20 — Absence totale de limitation de debit sur l'authentification · Haute

`POST /login`, `POST /otp`, `POST /api/auth/token` et les formulaires publics
(`/public/eglises/{church}/don`, `/public/evenements/{event}`) n'ont aucun
`throttle`. Consequences :

- force brute du mot de passe sans plafond ;
- force brute du code OTP a 6 chiffres (10^6 combinaisons, aucun verrouillage) ;
- pollution / DoS des ecritures comptables via les formulaires publics.

**Correctif applique** : limiteurs nommes `login`, `otp`, `api-token` et
`public-form` (cle IP + identifiant), appliques via le middleware `throttle`.

## SEC-21 — Mecanisme OTP non fiable · Haute

`AuthenticatedSessionController::store` :

- le code OTP est **affiche a l'utilisateur** (`with('success', "Code OTP ... {$otp}")`)
  — le « second facteur » est donc revele a quiconque a franchi le premier ;
- **aucune expiration** du code stocke en session ;
- **aucun plafond de tentatives** dans `OtpChallengeController::store` ;
- comparaison non constante (`!==`) ;
- le statut `actif` du compte n'est pas re-verifie a l'etape 2.

**Correctif applique** : le code n'est plus jamais affiche a l'ecran (livraison
email fiable, cf. B2) ; expiration configurable (`auth.otp_ttl`, 5 min par
defaut) ; compteur de tentatives (5 max puis invalidation et retour au login) ;
`hash_equals` ; re-verification du statut. Livraison du code par email
(`EmailOtpCodeNotification`, transport `MAIL_*`) — un envoi impossible
interrompt la connexion ; `config('auth.otp_demo')` ne fait plus que tolerer un
echec d'envoi (utile en local/dev sans SMTP configure), il ne revele jamais le
code.
Option renforcee : 2FA par application d'authentification (TOTP, RFC 6238,
`App\Support\Totp` + `users.otp_secret` chiffre). Quand elle est activee par
l'utilisateur (`/securite/authentification`), elle remplace le code email a la
connexion ; activation confirmee par un code valide, desactivation protegee par
le mot de passe.

## SEC-22 — Upload media : aucun controle de type de fichier · Haute

`MediaUploadController::complete` reassemble les morceaux et ecrit
`church-media/<slug>-<uuid>.<extension>` sur le disque `public`, ou `extension`
provient de `original_filename` sans validation. Un utilisateur `services.manage`
(Secretaire) peut deposer `.php`, `.phtml`, `.svg`, `.html` — XSS stocke servi
depuis `/storage/...`, voire execution si le disque public sert du PHP.
Aucun plafond de taille par morceau (`content_base64` non borne, assemblage
integral en memoire).

**Correctif applique** : liste blanche d'extensions et de `media_type` a
`initiate` ; rejet des extensions dangereuses ; plafond de taille par morceau ;
extension normalisee au moment de l'ecriture.

## SEC-23 — Jetons Sanctum sans expiration · Moyenne

`config/sanctum.php` n'est pas publie → `expiration = null` : les jetons API
n'expirent jamais. Combine a SEC-20, un jeton vole reste valide indefiniment.

**Correctif applique** : `config/sanctum.php` publie avec
`expiration = env('SANCTUM_TOKEN_EXPIRATION', 20160)` (14 jours) et commande
`sanctum:prune-expired` documentee.

## SEC-24 — En-tetes de securite HTTP absents · Moyenne

Aucun `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
`Permissions-Policy`, ni HSTS.

**Correctif applique** : middleware `SecurityHeaders` ajoute a la pile `web` et
`api` ; HSTS emis uniquement sur requete HTTPS.

## SEC-25 — Politique de mot de passe faible · Moyenne

Seule regle : `min:8` (`UserManagementController::store`). Pas de longueur
renforcee, pas de verification de compromission.

**Correctif applique** : `Password::defaults()` centralise dans
`AppServiceProvider` (min 10, mixte, non compromis hors environnement local) et
consomme par la validation de creation d'utilisateur.

## SEC-26 — Durcissement exploitation · Moyenne / Basse

- `EreveSeeder` cree des comptes dont le mot de passe est `password` ; rien
  n'empeche son execution en production (`db:seed`).
- `.env.example` : `APP_DEBUG=true`, pas de `SESSION_SECURE_COOKIE`, pas de
  `SANCTUM_TOKEN_EXPIRATION`.

**Correctif applique** : garde d'environnement dans `EreveSeeder`
(refus en production sauf `EREVE_ALLOW_DEMO_SEED=true`) ; cles de securite
ajoutees et commentees dans `.env.example`.

## SEC-27 — Endpoints publics a impact financier direct · Moyenne

`storeDonation` et `storeEvent` (non authentifies) creaient des ecritures
comptables immediates avec `amount`, `exchange_rate` et `payment_method` fournis
par l'appelant. Un `exchange_rate` arbitraire fausse la comptabilite multidevise,
un `amount` non borne pollue le grand livre, et un flux non authentifie ne
devrait pas ecrire directement au grand livre.

**Correctifs appliques** :

- limitation de debit (SEC-20) ;
- `exchange_rate` n'est plus accepte des formulaires publics : resolu cote
  serveur depuis `exchange_rates` (`PublicFlowController::serverExchangeRate`),
  champ retire des pages Vue publiques ;
- plafond de montant par devise (`config/contributions.php`,
  `PublicFlowController::guardPublicAmount`) ;
- **file d'attente de validation** : les soumissions publiques creent une ligne
  `public_contributions` a l'etat `pending` — aucune ecriture comptable. Un agent
  porteur de `contributions.record` valide (`PublicContributionController::approve`,
  qui passe alors l'ecriture) ou rejette (`::reject`, avec motif) depuis
  `/contributions-publiques`. Pour une inscription payante, le billet est emis
  immediatement mais `event_registrations.journal_entry_id` reste nul jusqu'a la
  validation. Chaque transition est tracee au journal d'audit
  (`contribution.public.submitted` / `.validated` / `.rejected`).

**Reste en suivi produit** : ajout d'un captcha sur les formulaires publics.

## SEC-28 — Escalade via offline-sync : ecriture comptable sans `accounting.post` · Haute

L'endpoint `POST /offline/sync` (web et API) est garde par `permission:offline.sync`,
detenue par Caissier et Secretaire. Or il accepte des enregistrements de type
`manual_journal_entry` transmis tels quels a `AccountingService::recordBalancedEntry`.
Un Caissier (« ne passe pas d'ecriture au grand livre », cf. `RbacEnforcementTest`)
ou un Secretaire (aucune permission financiere) pouvait ainsi passer des ecritures
comptables arbitraires, contournant la garde `accounting.post` de la voie web.

**Correctif applique** : `OfflineSyncService::processRecord` exige la permission
equivalente a la voie en ligne selon le type d'enregistrement
(`manual_journal_entry` -> `accounting.post`) ; a defaut, l'enregistrement est
refuse et remonte dans `conflicts` sans interrompre le lot.

---

## Points verifies sans anomalie

- Pas de SQL brut (`DB::raw`, `whereRaw`, `unprepared`) dans `app/`.
- Pas d'appel dangereux (`eval`, `exec`, `unserialize`, `system`).
- `HandleInertiaRequests::share` n'expose que des champs non sensibles.
- RBAC route + `Gate::before` plateforme coherents (Phase 1b/1c) ;
  scoping `AccessScope` applique sur les actions secondaires (tests dedies).
- API CRUD (`ChurchCentralCrudApiController`) : chaque `update*` verifie
  `ensureChurchAllowed` sur le modele lie ET sur le `church_id` entrant
  (pas d'IDOR, pas de deplacement hors perimetre).
- `OfflineSyncService` : perimetre eglise verifie et impose a chaque
  enregistrement ; la permission par type est desormais controlee (SEC-28).
- Chemins d'upload : `Str::slug` + UUID neutralisent la traversee de repertoire
  (le probleme reste le type de fichier — SEC-22).
