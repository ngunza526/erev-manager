# Guide utilisateur eReve Church

## Connexion

Compte de demonstration apres seed:

- Email: `proispos1@egmail.com`
- Mot de passe: `password`

Le login utilise un challenge OTP local de demonstration. En production, configurez le transport email/SMS approprie.

## Pilotage

Le tableau de bord donne une vue de coordination. Le menu lateral donne acces aux communautes, eglises, membres, services, groupes, evenements, comptabilite, budgets, depenses, modules pastoraux, finances avancees, flux publics et catalogue Solutions.

## Catalogue Solutions

La page `/solutions` montre la couverture des modules fonctionnels. Chaque module affiche:

- le module reference,
- son adaptation RDC,
- son niveau d'implementation,
- le lien interne,
- le flux public lorsqu'il existe.

## Comptabilite

La page `/comptabilite` permet:

- d'enregistrer dimes, offrandes et dons,
- de saisir une ecriture manuelle debit/credit,
- de consulter les journaux,
- d'exporter la balance en PDF/Excel,
- de generer les etats OHADA/SYCEBNL en PDF/Excel: bilan synthetique, produits, charges, resultat net et annexes tresorerie, fonds dedies, creances, dettes et controle actif/passif.

Les ecritures automatiques couvrent aussi: ventes boutique, depenses payees, factures fournisseurs payees, paie, reversements, mouvements de fonds et inscriptions evenements payees.

## Modules sensibles

- Roles et permissions: creation de permissions, roles coordination/eglise et rattachement des permissions.
- Enfants: check-in/check-out avec code securite, horodatage et personne de sortie.
- Counseling: planification, suivi confidentiel et cloture.
- Fournisseurs: facture en attente puis paiement comptabilise.
- Paie: brouillon puis paiement avec charges sociales.
- Fonds dedies: fonds affectes et mouvements avec impact comptable.

## Flux publics

Les QR publics peuvent pointer vers:

- `/public/eglises/{id}/don`
- `/public/eglises/{id}/visiteur`
- `/public/evenements/{id}`

Ces flux fonctionnent sans compte back-office.

## API et mobile

Les applications mobiles ou integrations externes recuperent un token via `POST /api/auth/token`, puis appellent les routes `GET /api/me`, `/api/churches`, `/api/members`, `/api/accounting/entries` et `/api/solutions` avec l'en-tete `Authorization: Bearer ...`.

Les donnees renvoyees et modifiees respectent le perimetre de l'utilisateur connecte: un utilisateur d'eglise ne voit pas et ne modifie pas les membres, cultes, groupes, evenements, budgets ou depenses d'une autre eglise.

Les clients mobiles peuvent aussi creer et modifier les modules coeur: membres, services/cultes, groupes, evenements, budgets et depenses. Une depense payee cree automatiquement l'ecriture comptable correspondante.

Les familles pastorales et administratives sont exposees par API generique: `/api/pastoral/{module}` pour visiteurs, convertis, enfants, volontaires, formations, sermons/media et incidents; `/api/administration/{module}` pour communications, demandes de service, reservations, patrimoine, conseils, promesses, sondages et temoignages.

Les modules avances sont aussi disponibles via `/api/advanced/{module}`. Les ventes boutique, mouvements de fonds, fournisseurs, paie, reversements et inscriptions payees conservent les effets comptables automatiques.

Les medias lourds peuvent etre envoyes par API mobile avec reprise: `POST /api/media/uploads` cree une session, `POST /api/media/uploads/{upload}/chunks` envoie chaque morceau, `GET /api/media/uploads/{upload}` indique les morceaux deja recus, puis `POST /api/media/uploads/{upload}/complete` assemble le fichier et cree le media publie disponible hors-ligne.

## Travail hors connexion

Le back-office affiche `Sync offline (n)` dans le menu lateral. Les actions terrain peuvent etre mises en file locale IndexedDB via `window.ereveOffline.enqueue(...)`, puis synchronisees automatiquement au retour du reseau ou manuellement avec ce bouton.

La synchronisation accepte membres, visiteurs, dons/offrandes/dimes, inscriptions evenement et ecritures debit-credit. Les dons, tickets payes et ecritures manuelles generent les memes journaux comptables que les formulaires en ligne.
La mediatheque combine deux usages terrain: precache des medias publies via le manifeste offline, et upload reprenable par morceaux pour les predications, supports ou fichiers pastoraux captures avec une connexion instable.
