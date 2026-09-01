# Auto-critique UI eReve Church

Date: 2026-07-04

Reference observee: https://churchmanager.fr/

## Constats avant adaptation

- Le menu lateral exposait 45 liens au meme niveau. Pour un utilisateur d'eglise, cela donnait une impression de back-office technique plutot que de SaaS simple.
- Les actions principales etaient noyees parmi les modules avances. Les parcours attendus d'une solution type Church Manager, comme Dashboard, Membres, Cultes, Budget et Messages, n'etaient pas prioritaires.
- La palette vert/or etait adaptee au contexte ecclesial, mais elle rendait l'interface plus institutionnelle que produit SaaS moderne.
- La page gardait beaucoup de surfaces bordees, sans topbar claire pour situer l'utilisateur, son niveau et le contexte courant.
- Sur les petits ecrans, le menu devenait une grille tres longue et peu scannable.

## Direction retenue

- Reprendre la logique de navigation courte de Church Manager: Dashboard, Membres, Cultes, Budget, Messages.
- Garder toute la richesse eReve Church, mais la ranger en familles: Organisation, Pastorale, Finance, Engagement, Digital.
- Passer a une interface plus claire: fond gris bleute, sidebar blanche, bleu principal, cartes blanches et hierarchie visuelle plus nette.
- Conserver le rayon de 8px impose par le design system local.
- Eviter les faux modules: toutes les entrees du menu restent branchees aux routes existantes.

## Adaptations realisees

- `resources/js/Layouts/AppLayout.vue`: navigation principale courte, groupes metier, contexte de page, badge utilisateur et actions offline/deconnexion compactes.
- `resources/css/app.css`: nouvelle palette, topbar, cartes, boutons, champs, et responsive mobile/tablette.
- Les pages existantes restent compatibles car les classes globales historiques (`panel`, `metric`, `item`, `hero`, `btn`, `tag`) ont ete conservees.

## Resultat attendu

L'application reste plus complete que Church Manager sur la logique RDC, comptable et offline, mais son premier contact visuel devient plus proche d'un SaaS simple et lisible: les menus critiques sont visibles, les modules avances sont ranges, et l'utilisateur comprend plus vite ou cliquer.
