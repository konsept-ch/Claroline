# Implementation plan: add `Mes formations` to the user profile

Date: 2026-06-19

## Goal

Add a visible profile tab for `Mes formations` so the current user can see their training history from the user profile, not only from the Cursus menu.

This is meant to support conditional learning paths:

- the user can review what has already been followed
- the user can understand what may unlock the next training
- the profile becomes a reference point for prerequisite-driven progression

## Current state

The data already exists.

Today, `Mes formations` is exposed by the Cursus plugin through:

- the header menu entry
- the trainings tool menu
- the trainings session page
- the current-user session API

Relevant files:

- [`src/main/app/Resources/modules/layout/header/components/user.jsx`](../../src/main/app/Resources/modules/layout/header/components/user.jsx)
- [`src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx)
- [`src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx)
- [`src/plugin/cursus/Controller/User/SessionController.php`](../../src/plugin/cursus/Controller/User/SessionController.php)

The profile system is separate and facet-driven.

Relevant files:

- [`src/main/community/Controller/ProfileController.php`](../../src/main/community/Controller/ProfileController.php)
- [`src/main/core/API/Serializer/User/ProfileSerializer.php`](../../src/main/core/API/Serializer/User/ProfileSerializer.php)
- [`src/main/core/Resources/modules/user/profile/components/main.jsx`](../../src/main/core/Resources/modules/user/profile/components/main.jsx)
- [`src/main/core/Resources/modules/user/profile/components/facets.jsx`](../../src/main/core/Resources/modules/user/profile/components/facets.jsx)
- [`src/main/core/Resources/modules/user/profile/player/components/main.jsx`](../../src/main/core/Resources/modules/user/profile/player/components/main.jsx)
- [`src/main/core/Resources/modules/user/profile/prop-types.js`](../../src/main/core/Resources/modules/user/profile/prop-types.js)

## Key constraint

The current profile facet system is generic and field-based.

That means a training-history tab cannot be added by translation alone.
One of these two approaches is needed:

1. reuse the existing `Mes formations` list inside the profile page
2. introduce a dedicated profile facet/component for Cursus data

## Recommended implementation path

Use a dedicated profile component for the training history, but reuse the existing Cursus API.

This keeps the logic separated:

- profile shell stays in the core profile system
- Cursus remains responsible for session data
- the tab becomes a presentation layer over existing user-session data

## Files likely to change

### 1. Profile shell / routing

If the new tab must appear in the profile page itself, the profile rendering flow may need a small extension.

Likely files:

- [`src/main/core/Resources/modules/user/profile/components/main.jsx`](../../src/main/core/Resources/modules/user/profile/components/main.jsx)
- [`src/main/core/Resources/modules/user/profile/components/facets.jsx`](../../src/main/core/Resources/modules/user/profile/components/facets.jsx)
- [`src/main/core/Resources/modules/user/profile/player/components/main.jsx`](../../src/main/core/Resources/modules/user/profile/player/components/main.jsx)

Reason:

- these components decide how facets are listed and rendered
- a custom `Mes formations` facet needs a place in that flow

### 2. Profile serialization

If the tab must be configurable through the existing profile configuration, the backend serializer path must remain aligned.

Likely files:

- [`src/main/community/Controller/ProfileController.php`](../../src/main/community/Controller/ProfileController.php)
- [`src/main/core/API/Serializer/User/ProfileSerializer.php`](../../src/main/core/API/Serializer/User/ProfileSerializer.php)
- [`src/main/core/API/Serializer/Facet/FacetSerializer.php`](../../src/main/core/API/Serializer/Facet/FacetSerializer.php)

Reason:

- the profile page loads facet definitions from the backend
- if a new facet type or flag is needed, it must be serialized here

### 3. Cursus data source

If the existing session listing is reused as-is, no new endpoint is required.

If the profile tab needs a consolidated or simplified view, add a dedicated endpoint under the current-user session controller.

Likely files:

- [`src/plugin/cursus/Controller/User/SessionController.php`](../../src/plugin/cursus/Controller/User/SessionController.php)
- [`src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx)

Reason:

- these files already own the current-user training lists
- they are the natural place to reuse or reshape the data

### 4. Cursus front-end component

If the profile tab should show the same lists as the trainings tool, extract or reuse a shared component.

Likely files:

- [`src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx)
- [`src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx)
- new component under `src/plugin/cursus/Resources/modules/...` for the profile variant

Reason:

- the menu item and the profile tab should not duplicate the full rendering logic
- the shared part should be the session listing component, not the shell

### 5. Plugin registration

If the feature is exposed as a new plugin section, the plugin entry point must be updated.

Likely file:

- [`src/plugin/cursus/Resources/modules/plugin.js`](../../src/plugin/cursus/Resources/modules/plugin.js)

Reason:

- this is the Cursus plugin registration file
- it is where new account-level or tool-level front-end modules are exposed

### 6. Translations

The label should be added or reused consistently in both the core profile context and the Cursus context.

Likely files:

- [`src/plugin/cursus/Resources/translations/cursus.fr.json`](../../src/plugin/cursus/Resources/translations/cursus.fr.json)
- [`src/plugin/cursus/Resources/translations/cursus.en.json`](../../src/plugin/cursus/Resources/translations/cursus.en.json)
- possibly [`src/main/core/Resources/translations/platform.fr.json`](../../src/main/core/Resources/translations/platform.fr.json) if the profile shell needs a new core label

## Suggested implementation split

### Option A: reuse existing Cursus page

Best if the profile tab only needs to open the same `Mes formations` content.

Changes:

- add a profile entry point that renders the existing `SessionMain`
- reuse `apiv2_cursus_my_sessions_*`
- add only the profile shell integration needed to expose the tab

This is the best fit if the requirement is:

- "show me my training history in a clearer place"
- "help me see what I have already completed before accessing the next step"

### Option B: dedicated profile facet

Best if the tab must behave like a first-class profile facet.

Changes:

- add a custom facet or custom facet renderer
- serialize that facet from the profile backend
- render the Cursus training history inside the facet

This is more work, but it is cleaner if the tab is meant to be part of the profile model rather than just a shortcut.

## Verification checklist

The implementation is correct only if:

- `Mes formations` appears in the profile for authenticated users
- the tab shows the current user's trainings
- the active/ended/pending split still works
- the page still works when a user has no sessions
- the UI does not duplicate the menu-only behavior

## Open decision

Before coding, choose one:

1. reuse the existing Cursus session page inside the profile
2. build a dedicated profile facet for training history

That choice determines whether the main work stays in the Cursus plugin or requires a profile serializer extension in the core.

For the request you described, Option A is usually the right first move because the business need is visibility into progression, not a new data model.

### Actual chosen path

The implementation now follows Option A inside the profile page itself:

- the profile view gets a synthetic `Mes formations` facet for the current user
- the facet reuses the existing Cursus session history component
- the standard profile editor stays untouched

That keeps the feature visible where the user expects it, without changing the profile admin configuration model.

## Option A: concrete file-by-file plan

### 1. Reuse the existing Cursus session page

Target file:

- [`src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx)

What to do:

- keep the current `SessionList` queries and tabs
- avoid duplicating the active/ended/pending logic
- expose the same component from a profile entry point

Reason:

- the page already answers the business question "what has the user followed?"
- it already knows how to show the relevant history split

### 2. Add a profile-facing wrapper

Likely new file:

- `src/plugin/cursus/Resources/modules/account/profile/components/mes-formations.jsx`

What to do:

- create a small wrapper that renders the existing trainings history component
- keep the wrapper minimal
- pass the current profile context only if needed for routing or breadcrumb consistency

Reason:

- this separates the profile shell from the Cursus view
- it keeps the implementation SOLID and avoids mixing concerns

### 3. Register the new account section in the plugin

Target file:

- [`src/plugin/cursus/Resources/modules/plugin.js`](../../src/plugin/cursus/Resources/modules/plugin.js)

What to do:

- add an `account` entry for the new profile section
- point it to the wrapper from step 2

Reason:

- the plugin registry is how Claroline exposes new account sections
- this is the cleanest way to make the tab available in the profile area

### 4. Hook the account profile tool if needed

Likely file:

- [`src/main/core/Resources/modules/account/profile/components/main.jsx`](../../src/main/core/Resources/modules/account/profile/components/main.jsx)

What to do:

- confirm the profile account page renders plugin-provided account sections
- if the section list is already dynamic, no change is needed
- if not, add the minimum integration to display the new `Mes formations` section

Reason:

- this is the place where account-level tabs are assembled
- the profile should show the new section automatically, not through a separate menu

### 5. Reuse the current-user session API as-is

Target file:

- [`src/plugin/cursus/Controller/User/SessionController.php`](../../src/plugin/cursus/Controller/User/SessionController.php)

What to do:

- do not change the API unless the profile wrapper needs a different payload
- keep the three existing endpoints for active, ended, and pending sessions

Reason:

- the API already represents the user’s training history
- the first version should not expand the backend surface unnecessarily

### 6. Reuse existing labels

Target files:

- [`src/plugin/cursus/Resources/translations/cursus.fr.json`](../../src/plugin/cursus/Resources/translations/cursus.fr.json)
- [`src/plugin/cursus/Resources/translations/cursus.en.json`](../../src/plugin/cursus/Resources/translations/cursus.en.json)

What to do:

- keep `Mes formations` as the main label
- keep the existing `Actives`, `Terminées`, and `En attente` labels
- add a profile-specific label only if the account shell needs one

Reason:

- the request is about making the history easier to find
- it is not about redefining the terminology

## Minimal execution order

1. create the account/profile wrapper for `Mes formations`
2. register it in the Cursus plugin
3. verify the account profile page renders it
4. only then decide whether any backend change is needed

## Expected result

After Option A, the user should be able to:

- open their profile
- find `Mes formations`
- review what they already followed
- understand whether they are ready for the next step in a conditional path
