# Cursus registration and presence tabs debug plan

Date: 2026-06-16

## Goal

Fix the two session tabs so they show the right data and the right status labels:

- `Inscriptions`
- `Validation des presences`

The current problem is not just a translation issue. It is a mix of:

- wrong source field for the displayed status
- wrong filter for the presence tab
- stale mental model between registration validation and participation validation

## What was already checked

An older note already exists and is still useful:

- [`docs/notes/cursus-validation-tabs-analysis.md`](./cursus-validation-tabs-analysis.md)

That note established the main split:

- `Inscriptions` should describe registration validation
- `Validation des presences` should describe participation

I also verified the current database state for session `45`:

- `lucas.perez@vd.ch`
  - `state = 4`
  - `validated = 0`
  - `confirmed = 0`
- `test1police@police.ch`
  - `state = 1`
  - `validated = 0`
  - `confirmed = 0`

So:

- `test1police` is still waiting for registration validation
- `lucas` is already in the participation state

## What is still wrong in the UI

### 1. `Inscriptions`

Observed result after refresh:

- `test1police` is displayed as `validé`

Expected result:

- `test1police` must be displayed as `En attente`

Interpretation:

- the tab is still using the raw `state` value as if `state = 1` meant validated registration
- that is not enough for this use case
- the display must reflect registration validation, not only the raw state integer

### 2. `Validation des presences`

Observed result after refresh:

- `test1police` is still visible
- its presence label is `en attente`
- `lucas` is also not behaving consistently after validation

Expected result:

- `test1police` must not appear in this tab until the registration is validated
- `lucas` must appear here
- once participation is validated, `lucas` must become `Present`

Interpretation:

- the presence tab is not filtered strictly enough
- it should only receive registrations that are already validated
- then it should show participation status, not registration status

## Relevant code facts

### Backend

- `src/plugin/cursus/Serializer/Registration/AbstractUserSerializer.php`
  - exposes a computed `validated` field
  - this field is the correct signal for registration validation
- `src/plugin/cursus/Finder/Registration/SessionUserFinder.php`
  - already supports a `validated` filter
  - already supports a `state` filter

### Frontend

- `src/plugin/cursus/Resources/modules/course/components/pendings.jsx`
  - currently drives the `Inscriptions` tab
- `src/plugin/cursus/Resources/modules/course/components/presences.jsx`
  - currently drives the `Validation des presences` tab

## Working hypothesis

The current implementation is mixing two different concepts:

1. registration validation
2. participation validation

The symptoms match this pattern:

- `Inscriptions` still reads the raw state too literally
- `Validation des presences` still shows users who are not validated yet
- the presence tab likely needs a hard `validated = true` filter

## Proposed fix

### `Inscriptions`

Use the registration validation signal explicitly:

- show `En attente` when registration is not validated
- show `Validé` when registration is validated

Prefer the computed serializer field `validated` over a raw state label.

### `Validation des presences`

Restrict the data set before rendering:

- keep only users with validated registration
- then display the participation status

Participation display rules:

- `state = 1` -> waiting for presence validation
- `state = 4` -> `Present`
- session ended without validation -> `Absent`

### Refresh behavior

After validating a presence, the list must be refetched or invalidated.

If the backend updates correctly but the UI does not move, the issue is the cache/update path, not the data model.

## Validation checklist

The fix is correct only if all of these are true:

- `test1police` appears in `Inscriptions` as `En attente`
- `test1police` does not appear in `Validation des presences`
- `lucas` appears in `Inscriptions` as `Validé`
- `lucas` appears in `Validation des presences`
- after validating participation, `lucas` becomes `Present`

## Files to keep aligned

- `src/plugin/cursus/Resources/modules/course/components/details.jsx`
- `src/plugin/cursus/Resources/modules/course/components/pendings.jsx`
- `src/plugin/cursus/Resources/modules/course/components/presences.jsx`
- `src/plugin/cursus/Resources/modules/course/components/participants.jsx`
- `src/plugin/cursus/Controller/SessionController.php`
- `src/plugin/cursus/Finder/Registration/SessionUserFinder.php`
- `src/plugin/cursus/Serializer/Registration/AbstractUserSerializer.php`

## Update rule

If the UI is still wrong after a change, update this document with:

- the exact screenshot result
- the exact DB state
- the exact endpoint used
- the exact file changed

That keeps the diagnosis stable across attempts.
