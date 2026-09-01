# Checklist de livraison

## Technique

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci`
- [ ] `composer validate --no-check-publish`
- [ ] `composer audit`
- [ ] `npm audit --audit-level=moderate`
- [ ] `npm run build`
- [ ] `php artisan migrate --force`
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

- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` generee.
- [ ] Mot de passe demo change.
- [ ] HTTPS actif.
- [ ] Sauvegardes base configurees.
- [ ] Cron Laravel configure.
- [ ] Dossier `public/` seul expose au web.
