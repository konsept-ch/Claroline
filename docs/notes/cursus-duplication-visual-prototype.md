# Cursus Duplication — Spécification fonctionnelle

Cette note décrit le comportement attendu de la duplication d'une formation. Elle sert de référence pour l'interface et l'implémentation backend.

## What was added

- A new visual modal: [`DuplicateCourseModal`](../../src/plugin/cursus/Resources/modules/modals/duplicate/components/modal.jsx)
- A new modal registration: [`MODAL_DUPLICATE_COURSE`](../../src/plugin/cursus/Resources/modules/modals/duplicate/index.js)
- A `Dupliquer` action in the training list: [`course/components/list.jsx`](../../src/plugin/cursus/Resources/modules/course/components/list.jsx)

## Current visual scope

- Source course summary
- Editable-looking fields for:
  - name
  - description
- Editable organizations with access to the duplicated formation
- Summary blocks for:
  - base characteristics
  - sessions
  - included contents
- A sessions table with editable dates and a delete button for each session
- The copied location is displayed as read-only
- Explicit warning that registrations, participants, presences, results, and history are excluded

## Workflow attendu

1. L'utilisateur clique sur **Dupliquer** depuis la liste des formations.
2. L'écran de duplication est prérempli avec les données de la formation source.
3. L'utilisateur peut modifier le titre et la description.
4. Les sessions sont affichées avec leurs dates d'origine. L'utilisateur peut modifier les dates ou supprimer une session avec **X**.
5. Le lieu de chaque session est copié automatiquement et n'est pas modifiable dans cet écran.
6. L'utilisateur peut modifier l'organisation responsable ainsi que les organisations ayant accès à la formation.
7. L'utilisateur clique sur **OK / Dupliquer** pour créer la nouvelle formation.

## Règles de duplication

| Élément | Comportement |
| --- | --- |
| Titre | Copié puis modifiable avant validation |
| Description | Copiée puis modifiable avant validation |
| Code de formation | Nouveau code généré automatiquement et unique |
| Contenu pédagogique | Copié |
| Sessions | Copiées avec leurs dates initiales ; dates modifiables ou session supprimable |
| Lieu | Copié pour chaque session ; non modifiable dans l'écran de duplication |
| Organisation responsable | Copiée puis modifiable |
| Organisations avec accès | Copiées puis modifiables |
| Règles d'inscription | Copiées telles quelles et non modifiables dans cet écran |
| Inscriptions et participants | Jamais copiés |
| Présences, résultats et historiques | Jamais copiés |

La duplication doit créer une nouvelle formation indépendante. Les modifications réalisées dans l'écran de duplication ne doivent pas modifier la formation source.

## État réel de l'implémentation backend

Cette section précise la différence entre le comportement attendu ci-dessus et le comportement actuellement implémenté dans `CourseManager::duplicate()`.

### Éléments actuellement copiés

- Le nom, la description et la description texte brut.
- Un nouveau code de formation unique, sauf si un code libre est fourni dans la requête.
- Les paramètres d'inscription, le tarif et les paramètres de session.
- L'organisation responsable et les organisations associées, sauf si une nouvelle liste d'organisations est fournie dans la requête.
- Les sessions sélectionnées, avec leur nom, description, dates, lieu, ressources associées et type d'inscription aux événements.
- Une nouvelle formation indépendante, sans copie des inscriptions ou des participants.

### Éléments prévus mais non copiés actuellement

- Les tags de la formation.
- Le contenu pédagogique global de la formation, c'est-à-dire les ressources et activités rattachées à l'espace de formation. Les ressources associées à une session sont copiées comme associations de session, mais cela ne constitue pas une copie complète du contenu pédagogique global.
- Les éventuels droits ou accès spécifiques qui ne sont pas représentés par la liste des organisations copiée.

### Éléments volontairement exclus

- Les inscriptions et participants.
- Les présences, résultats et historiques.

Les tags et le contenu pédagogique global doivent donc être ajoutés à l'implémentation backend pour respecter les règles de duplication décrites dans cette documentation. La copie des organisations est implémentée dans le manager, mais doit être vérifiée dans l'interface et couverte par un test automatisé.

## Limites actuelles du prototype

- Aucun appel API backend
- Aucune persistance
- Aucune mutation de données
- Les sessions affichées sont encore des données de démonstration

## Next implementation steps

1. Ajouter un endpoint API dédié à la duplication.
2. Remplacer les sessions simulées par les sessions de la formation sélectionnée.
3. Envoyer au backend les valeurs modifiées et les sessions conservées.
4. Implémenter la création d'une nouvelle formation avec un nouveau code unique.
5. Copier les tags et le contenu pédagogique global, sans copier les données historiques.
6. Vérifier les droits de l'utilisateur sur la formation source et sur les organisations sélectionnées.
7. Ajouter des tests couvrant les tags, le contenu pédagogique, les organisations, les sessions supprimées, les dates modifiées et l'absence d'inscriptions copiées.

## Notes

- The UI is intentionally optimistic: it is designed to demonstrate the intended user flow to the client.
- The final backend implementation should treat this modal as the source of truth for product scope.
