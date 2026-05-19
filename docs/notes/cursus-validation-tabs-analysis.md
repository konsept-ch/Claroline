# Analyse des onglets de validation cursus

Date: 2026-05-19

## Contexte

Le besoin concerne les onglets visibles dans la fiche d'une session de formation:

- `Inscriptions à valider`
- `Liste de classe à valider`
- `Annulations`

Le retour utilisateur indique un manque de clarté entre:

- le statut d'inscription
- le statut de participation
- la présence ou non des inscriptions refusées dans la vue de validation

## Constat technique

### 1. L'onglet `Inscriptions à valider`

Le titre de l'onglet est défini dans `src/plugin/cursus/Resources/modules/course/components/details.jsx`.

Son contenu est rendu par `CoursePendings` dans `src/plugin/cursus/Resources/modules/course/components/pendings.jsx`.

Points observés:

- la liste est alimentée par l'endpoint `apiv2_cursus_session_list_pending`
- le backend filtre explicitement les enregistrements avec `pending = true`
- ce filtre ne renvoie que les inscriptions qui sont encore dans l'état d'attente, ou en attente de confirmation
- les inscriptions refusées sont donc exclues de cette vue par construction

Conséquence:

- la vue est cohérente avec son nom métier si l'objectif est de traiter uniquement les demandes en attente
- en revanche, elle ne permet pas de consulter l'historique des refus

### 2. L'onglet `Liste de classe à valider`

Le titre actuel est `Liste de classe à valider`, défini aussi dans `src/plugin/cursus/Resources/modules/course/components/details.jsx`.

Son contenu est rendu par `CoursePresences` dans `src/plugin/cursus/Resources/modules/course/components/presences.jsx`.

Points observés:

- la liste est alimentée par `apiv2_cursus_session_list_users`
- cet endpoint renvoie les inscriptions de la session avec un filtre sur `state IN (validated, participated)`
- la colonne de statut affichée dans la grille utilise `row.state`
- le libellé affiché est issu de `REGISTRATION_STATES`

Conséquence:

- cette vue affiche bien un état métier, mais il s'agit du cycle d'inscription/validation, pas d'un état de présence distinct
- le libellé `Liste de classe à valider` peut induire en erreur, car la grille montre un statut de registration au sens large

### 3. Deux notions différentes coexistent dans le modèle

Dans `SessionUser`, on trouve:

- `state` dans `src/plugin/cursus/Entity/Registration/AbstractUserRegistration.php`
- `status` dans `src/plugin/cursus/Entity/Registration/SessionUser.php`

Le serializer `src/plugin/cursus/Serializer/Registration/SessionUserSerializer.php` expose les deux champs.

Mais dans les écrans concernés:

- `CoursePendings` n'affiche pas de colonne dédiée au statut métier
- `CoursePresences` affiche `state`, pas `status`

Conclusion:

- l'interface mélange des concepts métier proches mais non identiques
- le terme `validation` est utilisé à la fois pour l'inscription et pour la participation, ce qui brouille la lecture

## Hypothèse sur le besoin métier

Le besoin semble être le suivant:

- `Inscriptions à valider` doit servir au traitement des demandes d'inscription, avec visibilité sur leur état
- `Liste de classe à valider` doit servir au suivi de la participation effective, pas au statut d'inscription
- les refus devraient probablement rester consultables, soit dans la même vue avec un filtre dédié, soit dans un onglet d'archivage

## Recommandation

Avant de modifier l'UI, il faut trancher entre deux approches.

### Option A. Clarifier sans changer le modèle

Cette option limite l'impact technique.

Actions possibles:

- renommer `Liste de classe à valider` en un terme qui parle explicitement de `participation` ou `présences`
- ajouter une colonne explicite `statut d'inscription` dans l'onglet des pendings
- afficher un filtre ou un sous-onglet pour les inscriptions refusées si le besoin est de les conserver visibles

Avantage:

- faible risque fonctionnel
- rapide à mettre en place

Limite:

- le modèle métier reste ambigu pour l'utilisateur

### Option B. Séparer clairement inscription et participation

Cette option est plus saine à long terme.

Actions possibles:

- garder `Inscriptions à valider` pour `pending / validated / refused`
- créer une vraie vue `Participation / Présences à valider`
- utiliser un champ ou une source de données dédiée pour la participation, au lieu de réutiliser le même libellé de statut d'inscription

Avantage:

- lisibilité métier supérieure
- cohérence entre nom d'onglet, données affichées et action disponible

Limite:

- nécessite potentiellement une évolution backend et pas seulement un ajustement UI

## Fichiers utiles pour la suite

- `src/plugin/cursus/Resources/modules/course/components/details.jsx`
- `src/plugin/cursus/Resources/modules/course/components/pendings.jsx`
- `src/plugin/cursus/Resources/modules/course/components/presences.jsx`
- `src/plugin/cursus/Resources/modules/course/components/participants.jsx`
- `src/plugin/cursus/Controller/SessionController.php`
- `src/plugin/cursus/Finder/Registration/SessionUserFinder.php`
- `src/plugin/cursus/Entity/Registration/AbstractUserRegistration.php`
- `src/plugin/cursus/Entity/Registration/SessionUser.php`

## Proposition de décision

Si le besoin client est surtout de compréhension, je recommande de:

1. conserver la logique backend actuelle
2. clarifier les libellés UI
3. décider explicitement si les refus doivent être visibles comme historique ou non

Si le besoin client est de distinguer vraiment inscription et participation, il faudra probablement une petite refonte du modèle d'affichage, pas seulement des traductions.
