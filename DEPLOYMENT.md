# Deploiement eReve Church SaaS

## Pre-requis serveur

- PHP 8.2+ avec `mbstring`, `intl`, `pdo_mysql`, `zip`, `gd`, `bcmath`, `fileinfo`
- MySQL 8 ou MariaDB compatible
- Composer 2
- Node 20+
- HTTPS actif
- Cron Laravel

## Installation production

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
# Provisionne le RBAC + le compte SuperAdmin plateforme (le seeder de
# demonstration EreveSeeder refuse de tourner en production — SEC-26).
php artisan ereve:install --email=admin@votre-domaine.cd
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Instance de demonstration uniquement (jamais en production) :
> `php artisan migrate --seed --force` charge le jeu de donnees complet
> (`EreveSeeder`, comptes a mot de passe `password`).

## Variables minimales

Gabarit complet : [`.env.production.example`](.env.production.example).

```env
APP_NAME="eReve Church"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.cd
APP_TIMEZONE=Africa/Lubumbashi
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ereve
DB_USERNAME=ereve_user
DB_PASSWORD=mot_de_passe_fort

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
SESSION_SECURE_COOKIE=true

# Obligatoire : le code de connexion (2FA) est envoye par email.
OTP_DEMO=false
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=xxxxxxxx@smtp-brevo.com
MAIL_PASSWORD=xsmtpsib-xxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.cd
```

## Checklist avant mise en ligne

```bash
composer validate --no-check-publish
composer audit
npm audit --audit-level=moderate
php artisan migrate:fresh --seed   # environnement de demonstration uniquement
php artisan test
npm run build
php artisan route:list
```

En production, remplacer `migrate:fresh --seed` par :

```bash
php artisan migrate --force
php artisan ereve:install --email=admin@votre-domaine.cd
```

Verifier ensuite:

- Connexion avec le compte cree par `ereve:install` : mot de passe + code OTP recu par email (verifier `MAIL_*`).
- Acces au tableau de bord `/`.
- Acces au catalogue `/solutions` avec couverture 100%.
- Saisie d'une ecriture manuelle dans `/comptabilite`.
- Exports `/rapports/etats-ohada.pdf` et `/rapports/etats-ohada.xlsx`.
- Token API `POST /api/auth/token`, puis lecture `GET /api/me` avec Bearer token.
- Synchronisation offline `POST /offline/sync` ou `POST /api/offline/sync` avec un lot idempotent.
- Flux public de don `/public/eglises/1/don`.
- Flux public visiteur `/public/eglises/1/visiteur`.
- Flux public evenement `/public/evenements/1`.

## Hebergement mutualise

- Pointer le document root vers `public/`.
- Garder le reste du projet hors du dossier public si l'hebergeur le permet.
- Executer Composer, npm et Artisan localement ou via SSH avant upload.

## VPS

- Nginx ou Apache avec document root `public/`.
- HTTPS obligatoire.
- Cron:

```cron
* * * * * cd /chemin/ereve-saas && php artisan schedule:run >> /dev/null 2>&1
```

- Supervisor recommande pour les queues lorsque les jobs offline/export sont actives.

## Maintenance

```bash
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```
