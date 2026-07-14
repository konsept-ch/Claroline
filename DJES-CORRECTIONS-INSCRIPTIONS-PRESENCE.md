# DJES - Corrections inscriptions, e-mails et présence

Date de référence: 2026-06-30

## Objectif

Documenter les anomalies constatées sur les inscriptions à un espace/session, les e-mails automatiques, la validation de présence et l’export de la liste de classe.

Ce document sépare:
- ce qui est observé aujourd’hui dans le code,
- ce qui doit être corrigé,
- où intervenir,
- et quoi vérifier après correction.

## Résumé exécutif

Le problème principal est que le flux d’inscription n’est pas cohérent entre:
- la création de la demande,
- sa validation,
- son refus/annulation,
- et la mise à jour de l’état côté interface.

Le code montre aussi que certains mails existent en template, mais ne sont pas reliés au flux métier:
- `workspace_registration`
- `platform_role_registration`

Autre point important: la validation de demande de registration appelle actuellement la validation puis la suppression, ce qui mélange les intentions métier.

## Lecture de l’estimation précédente

L’estimation précédente disait que certains besoins étaient déjà présents.

Lecture correcte:
- `Le responsable hiérarchique et le responsable des formations doivent pouvoir inscrire directement du personnel` est partiellement présent dans le noyau pour une logique de rôles, mais pas encore prouvé pour vos rôles métier.
- `Les formateurs doivent pouvoir confirmer leur participation...` est à confirmer dans le module métier formation, pas dans le noyau Claroline visible ici.
- `Il doit être possible d’inscrire plusieurs formateurs par session` est à vérifier au niveau du modèle de session et de ses relations, pas au niveau du simple workspace.

Donc, pour la mise à jour réelle, il faut partir d’un principe simple:
- le noyau Claroline apporte des briques génériques,
- le comportement demandé doit être validé et probablement complété dans le module métier qui gère les sessions/formations.

## Flux actuel observé

### Côté backend

- La demande d’inscription à un workspace est créée dans `WorkspaceUserQueueManager::addUserQueue()`.
- La validation d’une demande ajoute le rôle à l’utilisateur dans `WorkspaceUserQueueManager::validateRegistration()`.
- Le refus / suppression de la demande passe par `WorkspaceUserQueueManager::removeRegistration()`.
- Le contrôleur API de validation appelle les deux actions, validation puis suppression.

### Côté mail

- `MailManager` sait envoyer:
  - `user_registration`
  - `user_email_validation`
  - `workspace_registration` est défini dans la config, mais marqué `# not sent`
  - `platform_role_registration` est aussi `# not sent`
- Aucun envoi de mail n’est déclenché dans le flux de registration workspace actuel.

### Côté interface

- L’état `waitingForRegistration` est sérialisé dans `WorkspaceSerializer`.
- Le message de liste d’attente est affiché dans `workspace/components/restrictions.jsx`.
- Le menu “pending” dans le tool community dépend de `registration.selfRegistration` et `registration.validation`.
- La liste des demandes en attente invalide bien certains stores, mais pas forcément tous les écrans qui affichent le statut du workspace.

## Points à corriger

### 3.1 Auto-inscription avec l’option “envoyer invitation”

#### Problème

Quand l’utilisateur s’auto-inscrit avec invitation, il reçoit un mail d’invitation, mais pas de confirmation d’inscription.

#### Cause probable

Le flux de self-registration n’appelle pas explicitement l’envoi du mail de confirmation de registration.

#### Où corriger

- `Claroline/src/main/core/Controller/APINew/Workspace/RegistrationController.php`
- `Claroline/src/main/core/Manager/Workspace/WorkspaceUserQueueManager.php`
- `Claroline/src/main/core/Manager/MailManager.php`
- `Claroline/src/main/core/Resources/config/config.yml`

#### Correction attendue

- Distinguer clairement:
  - invitation,
  - confirmation d’inscription,
  - demande en attente de validation.
- Déclencher le mail de confirmation au bon moment, même quand une invitation est envoyée.

#### Vérification

- Une self-registration avec invitation doit envoyer:
  - le mail d’invitation,
  - et le mail de confirmation d’inscription.

### 3.2 Auto-inscription sans l’option “envoyer invitation”

#### Problème

Aucun e-mail n’est envoyé.

#### Cause probable

Le flux ne déclenche aucun mail quand la demande est créée.

#### Où corriger

- `Claroline/src/main/core/Manager/Workspace/WorkspaceUserQueueManager.php`
- `Claroline/src/main/core/Manager/MailManager.php`
- éventuellement un subscriber dédié au workflow d’inscription

#### Correction attendue

- Envoyer au minimum le mail de confirmation d’inscription.
- Si le workspace est en validation, envoyer aussi le mail adapté à l’état “demande en attente”.

#### Vérification

- Une self-registration sans invitation doit envoyer un mail de confirmation.

### 3.3 Validation d’une inscription

#### Problème

La validation d’inscription ne déclenche pas le mail de validation.

#### Cause probable

`validateRegistrationAction()` valide la queue, mais aucun mail n’est envoyé après l’activation du rôle.

#### Où corriger

- `Claroline/src/main/core/Controller/APINew/Workspace/RegistrationController.php`
- `Claroline/src/main/core/Manager/Workspace/WorkspaceUserQueueManager.php`
- `Claroline/src/main/core/Manager/MailManager.php`
- éventuellement les événements de log si la logique doit passer par un subscriber

#### Point d’attention important

Le contrôleur appelle actuellement:
- `validateRegistration()`
- puis `removeRegistration()`

Cela mélange validation et refus dans la même action et peut produire des effets de bord.

#### Correction attendue

- Séparer validation et refus.
- Déclencher le mail de validation au moment où l’utilisateur est réellement inscrit au workspace.

#### Vérification

- Quand une inscription est validée, l’utilisateur doit recevoir le mail de validation.

### 3.4 Validation de la présence

#### Problème

L’utilisateur reçoit le mail “Présence validée” avec attestation, mais:
- il voit encore le message de liste d’attente,
- il n’a toujours pas accès au contenu de la session.

#### Cause probable

Le statut métier n’est pas complètement mis à jour après la validation de présence, ou l’interface consomme encore un état obsolète.

#### Où corriger

- le module métier qui gère la présence, probablement hors du noyau `core` si c’est un plugin métier “formation”
- `Claroline/src/main/core/API/Serializer/Workspace/WorkspaceSerializer.php` si le statut affiché vient du serializer workspace
- `Claroline/src/main/core/Resources/modules/workspace/components/restrictions.jsx` si le problème est seulement d’affichage
- le mécanisme d’attribution des rôles / droits d’accès lié à la session

#### Correction attendue

- Après validation de présence:
  - mettre à jour le statut de l’inscription,
  - retirer l’état “en attente”,
  - accorder les droits d’accès au contenu de la session.

#### Vérification

- L’utilisateur ne doit plus voir “liste d’attente”.
- L’accès au contenu doit être ouvert sans rechargement manuel complet.

### 3.5 Mise à jour des statuts

#### Problème

Le statut ne se met pas toujours à jour après modification.

#### Hypothèses à tester

- cache front non invalidé,
- serializer pas recalculé,
- écriture incomplète en base,
- droits recalculés mais non renvoyés dans la réponse API.

#### Où corriger

- `Claroline/src/main/core/Resources/modules/tools/community/pending/store/actions.js`
- `Claroline/src/main/core/API/Serializer/Workspace/WorkspaceSerializer.php`
- le composant qui charge le workspace ou la session après mutation

#### Correction attendue

- Invalider les stores nécessaires après mutation.
- Forcer le refresh du workspace/session concerné si le statut affiché dépend d’un serializer.

#### Vérification

- Après une action de validation/refus/presence, le statut affiché doit être celui de la base.

### 3.6 Refus / annulation d’inscription

#### Problème

L’action “refuser l’inscription” ne fonctionne pas correctement.
L’annulation, elle, déclenche le mail de refus.

#### Cause probable

La logique backend ne sépare pas correctement:
- le refus d’une demande,
- l’annulation d’une inscription,
- la suppression d’une inscription en attente.

Le point critique est ici:
- `WorkspaceUserQueueManager::validateRegistration()`
- `WorkspaceUserQueueManager::removeRegistration()`
- `RegistrationController::validateRegistrationAction()`

#### Où corriger

- `Claroline/src/main/core/Manager/Workspace/WorkspaceUserQueueManager.php`
- `Claroline/src/main/core/Controller/APINew/Workspace/RegistrationController.php`
- la partie UI du tool community pending

#### Correction attendue

- “Refuser” doit déclencher uniquement le flux de refus.
- “Annuler” doit correspondre à l’action métier voulue, sans réutiliser le même mail si ce n’est pas le bon cas.
- Ne pas appeler le refus au sein du flux de validation.

#### Vérification

- Refuser une demande envoie le bon mail.
- Annuler une inscription ne déclenche pas le mauvais template.

### 3.7 Option “absent”

#### Problème

Il manque une option pour marquer un participant comme absent.

#### Où corriger

- le module de présence / validation de présence
- l’énumération des statuts côté backend
- le formulaire ou menu d’action côté frontend

#### Correction attendue

- Ajouter un statut ou une action `absent`.
- Le rendre disponible dans le workflow de présence.

#### Vérification

- Un administrateur doit pouvoir marquer explicitement un participant comme absent.

### 3.8 Téléchargement de la liste de classe

#### État actuel observé

Il existe déjà une exportation CSV côté workspace:
- `workspace/list_users`
- `workspace/list_managers`

Le système d’export passe par le tool de transfert et le routeur d’export:
- `apiv2_workspace_transfer_export_list`

#### Où regarder

- `Claroline/src/main/core/Transfer/Exporter/Workspace/ListUsersExporter.php`
- `Claroline/src/main/core/Transfer/Exporter/Workspace/ListManagersExporter.php`
- `Claroline/src/main/transfer/Resources/modules/tools/transfer/export/components/list.jsx`

#### Conclusion

La “liste de classe” semble déjà exportable via le tool d’export, mais:
- ce n’est pas forcément exposé comme un bouton direct dans l’UI de la session,
- et il faut clarifier l’UX pour l’utilisateur final.

#### Correction possible

- soit documenter le chemin d’accès à l’export existant,
- soit ajouter un bouton dédié “Télécharger la liste de classe” dans l’espace concerné.

## Cible fonctionnelle

Le comportement final attendu doit être modélisé avec ces états:
- `self_submitted`: le participant s’inscrit lui-même.
- `hierarchical_pending`: attente de validation par le responsable hiérarchique.
- `training_pending`: attente de validation finale par le responsable des formations.
- `validated`: inscription active.
- `declined`: demande refusée.
- `cancelled`: inscription annulée.
- `present`: présence validée.
- `absent`: absence déclarée.
- `trainer_confirmed`: formateur confirmé.
- `trainer_change_requested`: changement de formateur proposé.

Si ces états n’existent pas aujourd’hui dans le modèle métier, ils doivent être ajoutés avant d’attaquer l’UI.

## Plan d’implémentation

### Étape 1: localiser le module métier formation

À faire en premier:
- identifier le bundle ou plugin qui gère les sessions, les participants et les formateurs,
- retrouver les entités de session, d’inscription, de validation et de présence,
- vérifier si ce module réutilise le noyau `WorkspaceRegistrationQueue` ou s’il possède son propre workflow.

Livrable attendu:
- une carte claire des classes et des contrôleurs concernés.

### Étape 2: figer le workflow d’inscription

À implémenter:
- inscription autonome du participant,
- validation hiérarchique,
- validation finale des formations,
- inscription directe par les responsables autorisés,
- confirmation formateur,
- changement de formateur.

Règle à appliquer:
- une action métier = un statut métier,
- un statut métier = un seul événement de transition clair.

### Étape 3: séparer les transitions

À corriger:
- ne plus appeler validation et suppression dans la même route,
- séparer `validate`, `decline`, `cancel`, `confirm`, `mark-present`, `mark-absent`.

Cela évite:
- les mails envoyés au mauvais moment,
- les états incohérents en base,
- les vues qui affichent encore l’attente.

### Étape 4: brancher les mails au bon moment

À faire:
- confirmation de demande,
- notification de validation hiérarchique,
- notification de validation finale,
- notification de refus,
- notification de présence,
- notification de changement de formateur.

Règle:
- l’envoi doit partir de la transition métier, pas de l’UI.

### Étape 5: mettre à jour le serializer et le refresh UI

À faire:
- exposer les bons statuts dans les serializers,
- invalider les stores après mutation,
- recharger les données affichées après validation ou refus.

### Étape 6: exposer le cas `absent`

À faire:
- ajouter le statut ou l’action,
- l’afficher dans la boîte d’action de validation de présence,
- documenter le comportement avec et sans attestation.

### Étape 7: clarifier l’export liste de classe

À faire:
- documenter l’export déjà disponible si c’est suffisant,
- sinon ajouter un bouton direct dans l’outil de session/formation,
- s’assurer que l’export contient le niveau voulu: participants, formateurs, statuts, présence.

## Ordre recommandé de modification du code

1. Identifier le module formation et ses entités.
2. Corriger le modèle de statuts et les transitions.
3. Brancher les mails sur les transitions.
4. Corriger le refresh et les statuts affichés.
5. Ajouter `absent`.
6. Ajouter ou exposer l’export de liste de classe.

## Critères d’acceptation

- Un participant peut s’inscrire lui-même.
- L’inscription suit une double validation.
- Les responsables autorisés peuvent inscrire directement.
- Un formateur peut confirmer sa participation.
- Un changement de formateur peut être proposé et validé.
- Plusieurs formateurs peuvent être rattachés à une session.
- Les mails correspondent exactement à l’action réalisée.
- Les statuts affichés correspondent à l’état réel en base.
- L’état `absent` est disponible.
- La liste de classe est téléchargeable ou documentée de manière explicite.

## Fichiers prioritaires à ouvrir en premier

- `Claroline/src/main/core/Controller/APINew/Workspace/RegistrationController.php`
- `Claroline/src/main/core/Manager/Workspace/WorkspaceUserQueueManager.php`
- `Claroline/src/main/core/Manager/MailManager.php`
- `Claroline/src/main/core/Resources/config/config.yml`
- `Claroline/src/main/core/API/Serializer/Workspace/WorkspaceSerializer.php`
- `Claroline/src/main/core/Resources/modules/workspace/components/restrictions.jsx`
- `Claroline/src/main/core/Resources/modules/tools/community/components/tool.jsx`
- `Claroline/src/main/core/Resources/modules/tools/community/pending/components/tab.jsx`
- `Claroline/src/main/core/Resources/modules/tools/community/pending/store/actions.js`
- `Claroline/src/main/core/Transfer/Exporter/Workspace/ListUsersExporter.php`
- `Claroline/src/main/transfer/Resources/modules/tools/transfer/export/components/list.jsx`

## Plan de correction recommandé

1. Séparer clairement les actions métier:
   - inscription,
   - validation,
   - refus,
   - annulation,
   - présence,
   - absent.
2. Brancher les bons mails sur chaque événement métier.
3. Recalculer et renvoyer le statut immédiatement après mutation.
4. Invalider les stores frontend qui affichent les données d’état.
5. Ajouter ou exposer l’export CSV de liste de classe si l’UX actuelle n’est pas suffisante.

## Tests de validation

- Self-registration avec invitation:
  - mail d’invitation envoyé,
  - confirmation d’inscription envoyée.
- Self-registration sans invitation:
  - confirmation d’inscription envoyée.
- Validation d’inscription:
  - mail de validation envoyé.
  - statut utilisateur mis à jour.
- Refus d’inscription:
  - mail de refus envoyé.
  - pas d’effet de bord sur le statut validé.
- Validation de présence:
  - plus de message de liste d’attente.
  - accès au contenu autorisé.
- Option absent:
  - action disponible.
- Export liste de classe:
  - export CSV accessible et documenté.
