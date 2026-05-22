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

Conclusion technique: pour qu'un visiteur sans compte entre sans login, il faut plus qu'un code. Il faut une autorisation limitee au workspace, pas un role global qui ouvre toute l'application.

## Ce que le client semble confondre
Le mot "public" semble etre utilise dans deux sens differents:
- sens metier: visible ou accessible sans compte
- sens Claroline: workspace configure pour autoriser des acces anonymes via des droits, souvent avec des restrictions supplementaires

Si le workspace reste personnel, on est dans un modele pense pour un compte proprietaire, pas pour un point d'entree anonyme. Le probleme n'est donc pas seulement "mettre public" ou "ajouter un code".

## Point important sur l historique Git
Un commit d'Anthony (`7588a6e978`, "implement temp user for public workspace") a bien introduit une logique de user temporaire pour workspace public. Mais ce commit n'est actuellement present sur aucune branche locale/verifiable de cette base. Autrement dit, ce comportement a existe dans l'historique, mais il n'est pas dans l'etat courant du code.

## Hypothese la plus probable
Le besoin client est reel, mais la formulation est incomplete:
- soit le client veut un vrai acces anonyme par code, mais limite au parcours concerne
- soit il attend encore une logique de permission isolee au workspace qui n'est plus presente dans la version actuelle

## Test de validation recommande
Tester un workspace non personnel, avec:
- droits accordes au role de workspace prevu pour le parcours
- code d'acces active
- ouverture en navigation privee, sans session ni compte

Si ce test echoue, alors la version actuelle ne couvre pas le cas d'usage attendu sans evolutions fonctionnelles supplementaires.

## Conclusion
Le client ne semble pas seulement "mal comprendre". Il y a aussi un ecart technique entre:
- ce que Claroline appelle "public"/"anonyme"
- et un acces "sans login, uniquement avec code"

Sur cette version, le code d'acces seul ne garantit pas l'entree. Le workspace doit recevoir une autorisation specifique et isolee, pas un role public global.

## Solution retenue
La solution retenue est d'utiliser un code de deverrouillage en session, puis de reconstruire un token anonyme scope au workspace seulement quand la requete vise ce workspace.

Principe cible:
- le visiteur reste non connecte
- quand le code du workspace est valide, la session marque ce workspace comme deverrouille
- sur les routes du workspace et de ses ressources, un listener reconstruit un token anonyme qui porte uniquement:
  - un marqueur technique `ROLE_WORKSPACE_ACCESS`
  - le role de workspace qui porte les droits du parcours vise
- `ROLE_ANONYMOUS` ne doit pas etre ajoute dans ce flux, sinon on retombe sur les droits publics globaux de l'application

Pourquoi c'est plus juste:
- le visiteur reste anonyme, donc il ne devient pas un compte utilisateur
- le token ne contient pas `ROLE_ANONYMOUS`, donc il ne gagne pas les acces publics globaux de l'application
- l'acces est limite aux droits explicitement accordes au role du workspace concerne

Effet attendu:
- l'utilisateur peut entrer dans le workspace vise apres saisie du code
- il n'a pas besoin de login interactif
- il ne voit pas les autres zones publiques qui reposent sur `ROLE_ANONYMOUS`

Validation attendue:
- test manuel sur le workspace cible en navigation privee
- verification que la page ne bascule plus vers la modale de login apres saisie du code
- verification que les routes ou ressources hors workspace ne deviennent pas visibles

## Etat implemente
- `WorkspaceController::unlockAction()` ne cree plus de compte temporaire et ne modifie plus la session de login.
- `WorkspaceAnonymousAccessListener` rehydrate un token anonyme scope au workspace quand la requete vise un workspace ou une ressource de ce workspace deja deverrouille.
- `ToolRightsRepository` et `ResourceRightsRepository` n'ajoutent plus automatiquement `ROLE_ANONYMOUS` quand le token porte le marqueur `ROLE_WORKSPACE_ACCESS`.
- `WorkspaceController::openAction()` et `ResourceController::openAction()` s'appuient donc sur un token anonyme limite au parcours, pas sur un role public global.

## Solution plus precise a mettre en place
Pour que le besoin reste strictement "un parcours, un code, sans login", il faut:
1. utiliser un role de workspace dedie aux droits du parcours,
2. donner les droits de consultation de ce parcours a ce role uniquement,
3. conserver la validation par session du code pour ce workspace uniquement,
4. reconstruire le token anonyme uniquement sur les routes du workspace ou de ses ressources,
5. neutraliser l'ajout automatique de `ROLE_ANONYMOUS` dans les repositories de droits quand le marqueur `ROLE_WORKSPACE_ACCESS` est present.

Ce que cela evite:
- aucune elevation vers un compte utilisateur global
- aucun acces large a des zones publiques annexes de l'application
- aucun couplage avec les droits globaux de `ROLE_ANONYMOUS`

En pratique, l'autorisation doit se faire dans le controleur/manager de workspace via le role de workspace cible, pas via le role public global.
