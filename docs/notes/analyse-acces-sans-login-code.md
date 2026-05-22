# Analyse - acces sans login avec code

## Contexte
Le client demande un acces a un parcours via un code, sans login, pour des personnes qui n'ont pas de compte. Il signale aussi que l'espace "gestion-de-crise" est actuellement un espace personnel, alors qu'il attend un comportement de type "public".

## Ce qui est certain
- Le dump SQL confirme que l'espace "Gestion de crise - communes vaudoises" est bien marque `is_personal = 1`.
- Le code courant accepte l'ouverture de la route workspace en mode anonyme (`allowAnonymous`), mais cela ne suffit pas a autoriser l'acces.
- Dans la logique actuelle, le code d'acces sert a deverrouiller temporairement l'espace dans la session, il ne donne pas de droits a lui seul.
- L'UI Claroline distingue clairement:
  - l'acces anonyme a un workspace
  - l'acces avec code
  - la regle de self-registration
- Le formulaire workspace expose bien la configuration des restrictions par code et par inscription automatique.

## Lecture technique de la version actuelle
1. `WorkspaceController::openAction()` laisse passer un utilisateur anonyme, puis verifie les erreurs d'acces avant de renvoyer le contenu.
2. `WorkspaceRestrictionsManager::getErrors()` ajoute l'erreur `locked` si un code est defini mais pas encore deverrouille.
3. `WorkspaceRestrictionsManager::isUnlocked()` ne fait qu'interroger la session; le code n'ajoute aucun role ni droit.
4. `WorkspaceManager::hasAccess()` s'appuie sur les roles presents dans le token. Si le token n'a pas de role, l'acces echoue.
5. `WorkspaceRepository::checkAccess()` valide les droits de consultation sur les outils du workspace, pas seulement l'existence d'un code.

Conclusion technique: pour qu'un visiteur sans compte entre sans login, il faut plus qu'un code. Il faut aussi une configuration de droits adaptee pour le role anonyme, ou un mecanisme equivalent qui simule un utilisateur temporaire.

## Ce que le client semble confondre
Le mot "public" semble etre utilise dans deux sens differents:
- sens metier: visible ou accessible sans compte
- sens Claroline: workspace configure pour autoriser des acces anonymes via des droits, souvent avec des restrictions supplementaires

Si le workspace reste personnel, on est dans un modele pense pour un compte proprietaire, pas pour un point d'entree anonyme. Le probleme n'est donc pas seulement "mettre public" ou "ajouter un code".

## Point important sur l historique Git
Un commit d'Anthony (`7588a6e978`, "implement temp user for public workspace") a bien introduit une logique de user temporaire pour workspace public. Mais ce commit n'est actuellement present sur aucune branche locale/verifiable de cette base. Autrement dit, ce comportement a existe dans l'historique, mais il n'est pas dans l'etat courant du code.

## Hypothese la plus probable
Le besoin client est reel, mais la formulation est probablement incomplete:
- soit le client veut un vrai acces anonyme par code, avec droits anonymes sur un workspace non personnel
- soit il attend encore une logique de user temporaire qui n'est plus presente dans la version actuelle

## Test de validation recommande
Tester un workspace non personnel, avec:
- droits accordes au role anonyme sur au moins un outil d'entree
- code d'acces active
- ouverture en navigation privee, sans session ni compte

Si ce test echoue, alors la version actuelle ne couvre pas le cas d'usage attendu sans evolutions fonctionnelles supplementaires.

## Conclusion
Le client ne semble pas seulement "mal comprendre". Il y a aussi un ecart technique entre:
- ce que Claroline appelle "public"/"anonyme"
- et un acces "sans login, uniquement avec code"

Sur cette version, le code d'acces seul ne garantit pas l'entree. Le workspace ne doit pas etre seulement rendu public: il doit aussi etre configure pour un acces anonyme ou pour un user temporaire, selon le comportement voulu.

## Solution retenue
La solution retenue est de remettre la logique de user temporaire pour l'ouverture d'un workspace accessible sans compte.

Principe:
- si le visiteur n'est pas authentifie, on cree un user temporaire `tmp.*`
- on lui attribue les roles necessaires a l'ouverture, dont `ROLE_USER` et `ROLE_ANONYMOUS`
- on garde le role par defaut du workspace si celui-ci est defini
- on purge les users temporaires expires avant l'ouverture

Validation:
- un test de repository couvre la suppression des users temporaires expires
- la configuration PHPUnit de test a ete corrigee pour utiliser les parametres `test_database_*`
- le persister de test cree automatiquement `ROLE_USER` s'il manque, pour que les fixtures fonctionnent correctement
