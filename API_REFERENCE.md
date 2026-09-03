# Reference API eReve Church

Cette API REST est destinee aux applications mobiles, synchronisations terrain et integrations internes du SaaS eReve Church. Toutes les routes, sauf `POST /api/auth/token`, exigent un token Bearer Laravel Sanctum.

Specification machine-readable: `OPENAPI.json`.

## Authentification

`POST /api/auth/token`

- Corps: `email`, `password`, `device_name`.
- Reponse: `token`, `token_type`, `user`.
- Usage: creer un token mobile ou integration.

Exemple:

```json
{
  "email": "proispos1@egmail.com",
  "password": "password",
  "device_name": "mobile-lubumbashi-001"
}
```

`POST /api/auth/logout`

- En-tete: `Authorization: Bearer <token>`.
- Effet: revoque le token courant.

## Profil et perimetre

`GET /api/me`

- Retourne l'utilisateur connecte, son niveau (`coordination` ou `eglise`) et son perimetre.

`GET /api/churches`

- Retourne les eglises visibles selon `AccessScope`.
- Un utilisateur d'eglise ne voit que son eglise.
- Un utilisateur coordination voit les eglises de sa communaute.

`GET /api/solutions`

- Retourne le catalogue fonctionnel interne expose dans le SaaS.

`GET /api/media/offline-manifest`

- Retourne les medias publies, marques `offline_available`, avec `storage_url`, visibles dans le perimetre utilisateur.
- Usage PWA/mobile: precacher les fichiers autorises via le service worker.
- Securite: le manifeste est scope par eglise ou communaute selon `AccessScope`.

`POST /api/media/uploads`

- Cree une session d'upload reprenable pour un fichier lourd ou une connexion instable.
- Champs requis: `church_id`, `title`, `media_type`, `category`, `original_filename`, `total_chunks`.
- Securite: `church_id` est controle par `AccessScope`.

Exemple:

```json
{
  "church_id": 1,
  "title": "Predication offline",
  "media_type": "audio",
  "category": "sermon",
  "original_filename": "predication.mp3",
  "total_chunks": 24
}
```

`GET /api/media/uploads/{upload}`

- Retourne l'etat de reprise: `received_chunks`, `status`, `storage_url`.
- Permet au mobile de reprendre seulement les morceaux manquants.

`POST /api/media/uploads/{upload}/chunks`

- Enregistre ou remplace un morceau d'upload.
- Champs requis: `chunk_index` commence a 0, `content_base64`.
- Les morceaux peuvent arriver dans le desordre; le serveur dedoublonne les index recus.

Exemple:

```json
{
  "chunk_index": 0,
  "content_base64": "Qm9uam91ciBtb25kZQ=="
}
```

`POST /api/media/uploads/{upload}/complete`

- Assemble tous les morceaux, publie le fichier sur le disque public, cree un element `church_media_items` marque `offline_available`, puis supprime les morceaux temporaires.
- Refuse la finalisation tant que tous les morceaux ne sont pas recus.

## Membres

`GET /api/members`

- Liste les membres visibles dans le perimetre utilisateur.

`POST /api/members`

- Cree un membre rattache a une eglise autorisee.
- Champs requis: `church_id`, `last_name`, `middle_name`, `first_name`, `sex`, `birth_date`, `birth_place`, `profession`, `marital_status`, `status`.

Exemple:

```json
{
  "church_id": 1,
  "last_name": "Kabila",
  "middle_name": "Ilunga",
  "first_name": "Grace",
  "sex": "Feminin",
  "birth_date": "1996-04-12",
  "birth_place": "Lubumbashi",
  "profession": "Enseignante",
  "marital_status": "Celibataire",
  "spouse": null,
  "status": "sympathisant"
}
```

`PUT /api/members/{member}`

- Met a jour un membre deja dans le perimetre utilisateur.
- Le nouveau `church_id` doit aussi rester dans le perimetre.

## Cultes, groupes et evenements

`POST /api/services`

- Cree un culte/service.
- Champs cles: `church_id`, `title`, `service_type`, `starts_at`.

Exemple:

```json
{
  "church_id": 1,
  "title": "Culte dominical",
  "service_type": "culte",
  "starts_at": "2026-07-05 09:00:00",
  "ends_at": "2026-07-05 12:00:00",
  "preacher": "Pasteur Mwamba",
  "worship_leader": "Equipe louange",
  "attendance_count": 180,
  "notes": "Culte principal"
}
```

`PUT /api/services/{service}`

- Met a jour un culte/service autorise.

`POST /api/groups`

- Cree une cellule, groupe ou ministere.
- Champs cles: `church_id`, `name`, `group_type`, `leader_name`.

`PUT /api/groups/{group}`

- Met a jour un groupe autorise.

`POST /api/events`

- Cree un evenement.
- Champs cles: `church_id`, `title`, `event_type`, `starts_at`, `venue`, `currency`.

`PUT /api/events/{event}`

- Met a jour un evenement autorise.

## Budgets et depenses

`POST /api/budgets`

- Cree un budget par eglise, departement et periode.
- Champs cles: `church_id`, `name`, `currency`, `amount`, `period_starts_at`, `period_ends_at`, `status`.

`PUT /api/budgets/{budget}`

- Met a jour un budget autorise.

`POST /api/expenses`

- Cree une depense.
- Si `status` vaut `draft` ou `approved`, aucune sortie de tresorerie n'est comptabilisee.
- Si `status` vaut `paid`, une ecriture comptable double est creee automatiquement.
- Comptes utilises: charge `601`, tresorerie `511`, `501` ou `515` selon le moyen de paiement; `card` est rattache a `501` Banque principale.

Exemple avec ecriture comptable automatique:

```json
{
  "church_id": 1,
  "budget_id": null,
  "description": "Achat brochures evangelisation",
  "vendor": "Imprimerie locale",
  "category": "activites spirituelles",
  "currency": "USD",
  "amount": 120,
  "exchange_rate": 2850,
  "expense_date": "2026-07-04",
  "payment_method": "card",
  "status": "paid"
}
```

`PUT /api/expenses/{expense}`

- Met a jour une depense autorisee.

## Modules pastoraux et administratifs generiques

`GET /api/{family}/{module}`

- `family`: `pastoral` ou `administration`.
- Liste jusqu'a 100 elements scopes par eglise.
- Modules pastoraux: `visiteurs`, `convertis`, `enfants`, `volontaires`, `formations`, `sermons-media`, `incidents`.
- Modules administration: `communications`, `demandes-service`, `reservations-locaux`, `patrimoine`, `conseils-reunions`, `promesses-dons`, `sondages`, `temoignages`.

`POST /api/{family}/{module}`

- Cree une entree du module generique.
- Le `church_id` est obligatoire et controle par `AccessScope`.

Exemple pastoral `POST /api/pastoral/visiteurs`:

```json
{
  "church_id": 1,
  "full_name": "Visiteur Mapendo",
  "phone": "+243990001001",
  "email": "visiteur@example.cd",
  "visit_source": "invitation cellule",
  "visited_at": "2026-07-04",
  "follow_up_status": "a_relancer",
  "notes": "A rencontre l'equipe accueil"
}
```

Exemple administration `POST /api/administration/communications`:

```json
{
  "church_id": 1,
  "channel": "whatsapp",
  "audience": "membres",
  "subject": "Programme de la semaine",
  "body": "Repetition chorale mercredi et culte dimanche.",
  "scheduled_at": "2026-07-04 18:00:00",
  "status": "scheduled"
}
```

`PUT /api/{family}/{module}/{id}`

- Met a jour une entree existante.
- L'element courant et le nouveau `church_id` doivent appartenir au perimetre utilisateur.

## Modules avances

`GET /api/advanced/{module}`

- Liste jusqu'a 100 elements du module avance, scopes par eglise.
- Modules: `familles`, `discipolat`, `mediatheque`, `live-studio`, `fournisseurs`, `paie`, `rapprochements`, `reversements`, `counseling`, `evangelisation`, `qr-publics`, `outils-ia`, `fonds-dedies`, `mouvements-fonds`, `inscriptions-evenements`, `boutique-ressources`.

`POST /api/advanced/{module}`

- Cree une entree du module avance.
- Les modules financiers declenchent les memes effets comptables que le back-office lorsque la regle metier le prevoit:
  ventes boutique, factures fournisseurs payees, paie payee, reversements, mouvements de fonds et inscriptions evenements payees.
- Les liens sensibles sont controles: un `fund_id` ou un `church_event_id` doit appartenir a la meme eglise que `church_id`.

Exemple boutique avec ecriture de revenu:

```json
{
  "church_id": 1,
  "item_name": "Bible d'etude",
  "buyer_name": "Membre acheteur",
  "quantity": 2,
  "currency": "CDF",
  "unit_price": 24000,
  "exchange_rate": 2850,
  "payment_method": "card",
  "status": "paid",
  "sold_at": "2026-07-04"
}
```

Exemple mouvement de fonds:

```json
{
  "church_id": 1,
  "fund_id": 1,
  "movement_type": "receipt",
  "source_name": "Donateur affecte",
  "currency": "USD",
  "amount": 75,
  "exchange_rate": 2850,
  "movement_date": "2026-07-04",
  "payment_method": "mobile_money",
  "status": "posted",
  "description": "Don affecte au fonds construction"
}
```

`PUT /api/advanced/{module}/{id}`

- Met a jour une entree avancee existante.
- L'element existant, le nouveau `church_id` et les ressources liees restent controles par perimetre.

## Comptabilite

`GET /api/accounting/entries`

- Liste les ecritures comptables visibles.
- Inclut les lignes debit/credit et les comptes du plan SYCEBNL/SYSCOHADA.

## Synchronisation offline

`POST /api/offline/sync`

- Synchronise une file mobile/offline.
- Corps: `device_id`, `client_batch_id`, `church_id`, `records`.
- Types supportes: `member`, `visitor`, `donation`, `event_registration`, `manual_journal_entry`.
- Idempotence: `church_id` + `device_id` + `client_batch_id`.
- Effets comptables: dons, inscriptions payees et ecritures manuelles creent des journaux equilibres.
- Securite: un utilisateur d'eglise ne peut pas creer, rejouer ou recuperer un lot hors de son perimetre.

Exemple de lot terrain:

```json
{
  "device_id": "rdc-tablette-001",
  "client_batch_id": "batch-lubumbashi-20260704-001",
  "church_id": 1,
  "records": [
    {
      "client_id": "donation-001",
      "type": "donation",
      "payload": {
        "giver_name": "Donateur Offline",
        "type": "don",
        "amount": 25,
        "currency": "USD",
        "exchange_rate": 2850,
        "payment_method": "mobile_money"
      }
    },
    {
      "client_id": "manual-001",
      "type": "manual_journal_entry",
      "payload": {
        "entry_date": "2026-07-04",
        "description": "Ecriture terrain offline",
        "currency": "USD",
        "exchange_rate": 2850,
        "lines": [
          {
            "account_code": "511",
            "label": "Caisse terrain",
            "debit": 40,
            "credit": 0
          },
          {
            "account_code": "703",
            "label": "Don terrain",
            "debit": 0,
            "credit": 40
          }
        ]
      }
    }
  ]
}
```

## Erreurs standard

- `401`: token manquant ou invalide.
- `404`: module ou ressource introuvable.
- `422`: validation metier, perimetre eglise interdit, ecriture desequilibree ou ressource liee hors eglise.
