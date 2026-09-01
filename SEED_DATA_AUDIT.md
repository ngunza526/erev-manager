# Audit des donnees de demarrage eReve

## Etat attendu apres `php artisan migrate:fresh --seed`

- Aucun membre d'eglise n'est cree par le seeder.
- Aucune ecriture comptable n'est creee par le seeder.
- L'utilisateur administrateur est rattache a la coordination, sans `member_id`.
- Les referentiels necessaires restent disponibles : communaute, eglise, adresses RDC completes, devises USD/CDF, taux CDF/USD du jour, plan comptable, moyens de paiement, roles, modules, budgets et catalogues de base.

## Logique metier RDC conservee

- Les flux financiers respectent la realite USD/CDF avec taux de change explicite, partage depuis la table `exchange_rates`.
- Les moyens de paiement couvrent caisse, banque, carte bancaire et Mobile Money.
- Les depenses restent en brouillon ou approuvees sans decaissement tant qu'elles ne sont pas marquees `paid`.
- Les ventes boutique, mouvements de fonds, reversements, factures fournisseurs et paies ne creent une ecriture comptable que lorsqu'un paiement ou une validation finale est effectif.
- Les donnees de demonstration operationnelles sont volontairement non comptabilisees afin d'eviter des soldes fictifs dans une base de production.

## Preuves automatisees

- `tests/Feature/CleanSeedStateTest.php` verifie que le seeder ne cree ni membre ni ecriture comptable.
- Les tests des modules avances verifient que les lignes semees restent en `draft` ou `pending` avec `journal_entry_id` nul.
- Les tests finance verifient qu'une depense approuvee ne sort pas de caisse, et qu'une depense payee cree bien son ecriture.
