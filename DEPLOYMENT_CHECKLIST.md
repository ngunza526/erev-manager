# Checklist de livraison

## Technique

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci`
- [ ] `composer validate --no-check-publish`
- [ ] `composer audit`
- [ ] `npm audit --audit-level=moderate`
- [ ] `npm run build`
- [ ] `php artisan migrate --force`
- [ ] `php artisan ereve:install --email=...` (RBAC + SuperAdmin plateforme ; **pas** `db:seed` en production)
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`

## Fonctionnel

- [ ] Connexion administrateur.
- [ ] Catalogue `/solutions` affiche 100% de couverture.
- [ ] Saisie ecriture manuelle debit/credit.
- [ ] Don public comptabilise.
- [ ] Inscription evenement payee comptabilisee.
- [ ] Check-in/check-out enfant.
- [ ] Paiement fournisseur.
- [ ] Paiement paie.
- [ ] Planification et cloture counseling.

## Securite exploitation

- [ ] `APP_DEBUG=false`, `APP_ENV=production`.
- [ ] `APP_KEY` generee.
- [ ] `SESSION_SECURE_COOKIE=true`, HTTPS actif.
- [ ] `OTP_DEMO=false` et SMTP (`MAIL_*`) fonctionnel — sinon connexion impossible.
- [ ] Compte SuperAdmin cree via `ereve:install` (aucun compte a mot de passe `password`).
- [ ] `SANCTUM_TOKEN_EXPIRATION` defini ; cron `schedule:run` actif (purge des jetons).
- [ ] `composer audit` et `npm audit --audit-level=moderate` au vert.
- [ ] Sauvegardes base configurees.
- [ ] Dossier `public/` seul expose au web.
