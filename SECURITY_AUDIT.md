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

**Correctif applique** : code affiche uniquement en mode demo
(`config('auth.otp_demo')`, faux en production) ; expiration configurable
(`auth.otp_ttl`, 5 min par defaut) ; compteur de tentatives (5 max puis
invalidation et retour au login) ; `hash_equals` ; re-verification du statut.

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

## SEC-27 — Endpoints publics a impact financier direct · Moyenne (suivi produit)

`storeDonation` et `storeEvent` (non authentifies) creent des ecritures
comptables avec `amount`, `exchange_rate` et `payment_method` fournis par
l'appelant. Meme avec SEC-20, un flux non authentifie ecrivant directement au
grand livre est un choix a arbitrer.

**Correctif applique** : limitation de debit (SEC-20).
**Recommandation non tranchee** : faire transiter ces contributions par un etat
« a valider » plutot que par une ecriture immediate, et/ou ajouter un captcha.

---

## Points verifies sans anomalie

- Pas de SQL brut (`DB::raw`, `whereRaw`, `unprepared`) dans `app/`.
- Pas d'appel dangereux (`eval`, `exec`, `unserialize`, `system`).
- `HandleInertiaRequests::share` n'expose que des champs non sensibles.
- RBAC route + `Gate::before` plateforme coherents (Phase 1b/1c) ;
  scoping `AccessScope` applique sur les actions secondaires (tests dedies).
- Chemins d'upload : `Str::slug` + UUID neutralisent la traversee de repertoire
  (le probleme reste le type de fichier — SEC-22).
