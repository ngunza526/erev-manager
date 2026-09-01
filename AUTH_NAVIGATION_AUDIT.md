# Audit login, logout et navigation

Date: 2026-07-04

## Login

- La page `/login` est une page Inertia publique sous middleware `guest`.
- Le formulaire n'est plus pre-rempli avec les identifiants de demonstration.
- Les champs utilisent `name`, `autocomplete`, erreurs serveur et etat `processing`.
- Le mot de passe est valide cote serveur, le compte doit etre `actif`, puis l'utilisateur passe par l'ecran OTP.

## OTP

- `/otp` redirige vers `/login` si aucune tentative de connexion n'est en cours.
- Le champ OTP utilise `autocomplete="one-time-code"`, `inputmode="numeric"` et une limite de 6 caracteres.
- Apres validation OTP, la session est regeneree et l'utilisateur est envoye vers le dashboard.

## Logout

- Le bouton de deconnexion du shell appelle `POST /logout`.
- Le serveur appelle `Auth::logout()`, invalide la session, regenere le token CSRF et renvoie vers `/login`.
- Verification navigateur: login demo -> OTP -> dashboard -> logout -> retour `/login`.

## Liens et menus

- Le menu principal est groupe par usage: `Dashboard`, `Membres`, `Cultes`, `Budget`, `Messages`.
- Les autres routes sont rangees par thematiques: `Organisation`, `Pastorale`, `Finance`, `Engagement`, `Digital`.
- `NavigationIntegrityTest` lit les liens declares dans `resources/js/Layouts/AppLayout.vue`, verifie qu'ils correspondent a une route `GET/HEAD`, puis appelle chaque page en utilisateur authentifie.
- Les routes publiques seed (`don`, `visiteur`, `evenement`) sont aussi appelees pour verifier l'absence de lien mort.

## Preuves

- `AuthenticationOtpTest`: login + OTP, rejet compte inactif, protection OTP sans session, logout.
- `NavigationIntegrityTest`: groupement du menu, liens internes vivants, routes publiques vivantes.
- Verification navigateur locale sur `http://127.0.0.1:8088/login`.
