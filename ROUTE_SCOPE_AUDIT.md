# Audit de perimetre des routes eReve

Date: 2026-07-04

Cet audit liste les surfaces authentifiees ou publiques qui manipulent des donnees d'eglise et indique la preuve de perimetre appliquee.

## Regle generale

- Utilisateur `eglise`: acces limite a `church_id` de son compte.
- Utilisateur `coordination`: acces limite aux eglises de sa `community_id`.
- Utilisateur global sans restriction de coordination: acces transverse reserve aux cas d'administration centrale.

La classe centrale est `App\Services\AccessScope`.

## Routes web principales

| Routes | Controle applique | Preuve |
| --- | --- | --- |
| `/`, dashboard | `AccessScope::churchIds` et `communityIds` sur les compteurs | `AccessScopeTest` |
| `/communautes`, `/eglises` | scope communaute/eglise dans index et store | `AccessScopeTest` |
| `/membres` | liste et creation limitees par `church_id` | `AccessScopeTest`, `EreveBusinessRulesTest` |
| `/membres/{member}/statut` | `ensureChurchAllowed` sur le membre avant promotion | `SecondaryRouteScopeTest` |
| `/comptabilite`, `/comptabilite/collectes`, `/comptabilite/ecritures` | journaux et creation scopes par eglise | `EreveBusinessRulesTest`, `FinanceOperationsTest` |
| `/services`, `/groupes`, `/evenements`, `/budgets`, `/depenses` | listes et stores controles par `AccessScope` | `MinistryOperationsTest`, `ApiCrudManagementTest` |

## Routes pastorales et operations

| Routes | Controle applique | Preuve |
| --- | --- | --- |
| `/visiteurs`, `/convertis`, `/enfants`, `/volontaires`, `/formations`, `/sermons-media`, `/incidents` | index/store scopes par eglise | `PastoralCareModulesTest`, `ApiGenericModuleManagementTest` |
| `/enfants/{child}/check-in` | `ensureChurchAllowed` sur l'enfant avant check-in | `SecondaryRouteScopeTest` |
| `/enfants/{child}/check-out` | `ensureChurchAllowed` sur l'enfant avant check-out | `SecondaryRouteScopeTest` |
| `/communications`, `/demandes-service`, `/reservations-locaux`, `/patrimoine`, `/conseils-reunions`, `/promesses-dons`, `/sondages`, `/temoignages` | index/store scopes par eglise | `EngagementAdministrationModulesTest`, `ApiGenericModuleManagementTest` |

## Routes avancees

| Routes | Controle applique | Preuve |
| --- | --- | --- |
| `/boutique-ressources`, `/fournisseurs`, `/paie`, `/rapprochements`, `/reversements`, `/counseling`, `/evangelisation`, `/qr-publics`, `/live-studio`, `/outils-ia`, `/familles`, `/discipolat`, `/mediatheque`, `/fonds-dedies`, `/mouvements-fonds`, `/inscriptions-evenements` | index/store scopes par eglise | `AdvancedChurchModulesTest`, `CompletionGapModulesTest`, `ApiAdvancedModuleManagementTest` |
| `/fournisseurs/{vendorBill}/payer` | `ensureChurchAllowed` avant ecriture fournisseur | `SecondaryRouteScopeTest` |
| `/paie/{payrollRun}/payer` | `ensureChurchAllowed` avant ecriture paie | `SecondaryRouteScopeTest` |
| `/counseling/{counselingCase}/planifier` | `ensureChurchAllowed` avant planification | `SecondaryRouteScopeTest` |
| `/counseling/{counselingCase}/cloturer` | `ensureChurchAllowed` avant cloture | `SecondaryRouteScopeTest` |

## API Sanctum

| Routes | Controle applique | Preuve |
| --- | --- | --- |
| `/api/churches`, `/api/members`, `/api/accounting/entries` | listes scopees par `AccessScope` | `ApiAccessTest` |
| `/api/members`, `/api/services`, `/api/groups`, `/api/events`, `/api/budgets`, `/api/expenses` | store/update avec `ensureChurchAllowed` | `ApiCrudManagementTest` |
| `/api/{family}/{module}` | modules pastoraux/administratifs avec `ensureChurchAllowed` | `ApiGenericModuleManagementTest` |
| `/api/advanced/{module}` | modules avances avec `ensureChurchAllowed` et ressources liees controlees | `ApiAdvancedModuleManagementTest` |
| `/api/media/offline-manifest` | medias offline scopes par eglise | `ApiAccessTest` |
| `/api/media/uploads`, `/api/media/uploads/{upload}`, `/api/media/uploads/{upload}/chunks`, `/api/media/uploads/{upload}/complete` | sessions d'upload media, morceaux et finalisation controles par `ensureChurchAllowed` | `MediaUploadApiTest` |
| `/api/offline/sync` | lot offline scope par eglise et idempotence `church_id + device_id + client_batch_id` | `OfflineSyncTest` |

## Flux publics

Les flux publics sont volontairement accessibles sans authentification. Ils n'exposent pas les listes internes; ils creent uniquement des dons, visiteurs ou inscriptions sur la ressource publique cible.

| Routes | Controle applique | Preuve |
| --- | --- | --- |
| `/public/eglises/{church}/don` | creation don sur l'eglise cible avec ecriture automatique | `PublicFlowTest` |
| `/public/eglises/{church}/visiteur` | creation visiteur sur l'eglise cible | `PublicFlowTest` |
| `/public/evenements/{event}` | inscription sur l'evenement cible avec ecriture si paiement | `PublicFlowTest` |

## Routes globales assumees

Ces routes ne sont pas scopees par eglise parce qu'elles representent des referentiels ou une gouvernance centrale:

- `/plan-comptable`: plan comptable SYCEBNL/SYSCOHADA commun au SaaS.
- `/roles-permissions`: gouvernance des roles et permissions.
- `/solutions`: catalogue fonctionnel interne.
- `/rapports/*`: les donnees financieres incluses sont scopees par `AccessScope`.

Toute nouvelle route qui modifie une ressource rattachee a `church_id` doit soit appeler `ensureChurchAllowed`, soit etre ajoutee explicitement dans cette matrice avec sa justification.
