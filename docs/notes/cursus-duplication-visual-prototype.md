# Cursus Duplication Prototype

Goal: show a client-facing visual prototype for duplicating a training course and selecting what to duplicate, without wiring the backend yet.

## What was added

- A new visual modal: [`DuplicateCourseModal`](../../src/plugin/cursus/Resources/modules/modals/duplicate/components/modal.jsx)
- A new modal registration: [`MODAL_DUPLICATE_COURSE`](../../src/plugin/cursus/Resources/modules/modals/duplicate/index.js)
- A `Dupliquer` action in the training list: [`course/components/list.jsx`](../../src/plugin/cursus/Resources/modules/course/components/list.jsx)

## Current visual scope

- Source course summary
- Editable-looking fields for:
  - name
  - description
- Summary blocks for:
  - base characteristics
  - sessions
  - included contents
- A sessions table without selection controls
- Explicit warning that registrations, participants, presences, dates, and history are excluded

## Deliberate limits

- No backend API call
- No persistence
- No data mutation
- No real session selection logic yet

## Next implementation steps

1. Add a real API endpoint for the duplication workflow.
2. Replace static mock sessions with the selected course sessions.
3. Persist the user's choices from the modal.
4. Define the duplication contract for:
   - course fields
   - session fields
   - resources
   - workspace
5. Decide the default copy rules for special cases like default session, creator, and linked resources.

## Notes

- The UI is intentionally optimistic: it is designed to demonstrate the intended user flow to the client.
- The final backend implementation should treat this modal as the source of truth for product scope.
