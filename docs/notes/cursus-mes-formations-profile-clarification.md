# Clarification: "Mes formations" and profile tabs

Date: 2026-06-19

## Purpose

This note clarifies the meaning of the message:

> "Ce n'est que de l'affichage visuelle. Ajout par Konsept: Il faut aller dans le profil utilisateur, on peut voir l'historique des cours qu'il a suivi..."

The request is not only about translation or visual text.
It describes a functional gap between:

- the existing Cursus entry named `Mes formations`
- the user profile tab system
- the data source that lists the current user's trainings

## What "Mes formations" is today

`Mes formations` already exists in the Cursus plugin as a menu entry and as a dedicated page.

Current entry points:

- user header menu: [`src/main/app/Resources/modules/layout/header/components/user.jsx`](../../src/main/app/Resources/modules/layout/header/components/user.jsx)
- trainings menu: [`src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx)
- trainings session page: [`src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx)

The page is split into three views:

- `Actives`
- `Terminees`
- `En attente`

These views are backed by API routes:

- `apiv2_cursus_my_sessions_active`
- `apiv2_cursus_my_sessions_ended`
- `apiv2_cursus_my_sessions_pending`

## How it works technically

The session page does not use a hardcoded list.
It calls the backend with hidden filters for the current user:

- current user UUID
- terminated flag for active/ended sessions
- pending membership flag for the waiting list

Relevant backend file:

- [`src/plugin/cursus/Controller/User/SessionController.php`](../../src/plugin/cursus/Controller/User/SessionController.php)

So the source of truth is the logged-in user session data, not the profile UI itself.

## What the profile tab system does

The user profile is driven by a separate "facet" system.
That system lets the platform administrator add tabs and sections to the profile.

Relevant files:

- [`src/main/core/Resources/modules/administration/community/profile/components/profile-tab.jsx`](../../src/main/core/Resources/modules/administration/community/profile/components/profile-tab.jsx)
- [`src/main/core/Resources/modules/user/profile/components/main.jsx`](../../src/main/core/Resources/modules/user/profile/components/main.jsx)
- [`src/main/core/Resources/modules/user/profile/components/facets.jsx`](../../src/main/core/Resources/modules/user/profile/components/facets.jsx)

Important point:

- the profile system is generic
- `Mes formations` is a business view from Cursus
- the two are not automatically connected

## What the request means

The requested behavior is:

1. add a profile tab named `Mes formations` for all users
2. show the list of trainings followed by the current user inside that tab
3. keep the data scoped to the user profile, not only in the main Cursus menu

This means the work is not only cosmetic.
It requires wiring an existing Cursus view, or a dedicated variant of it, into the profile facet system.

## Recommended interpretation

If the objective is strictly to expose the same information in the profile:

- reuse the existing `my sessions` data source
- mount the view as a profile facet or profile section
- keep the existing labels and filters

If the objective is to create a different profile experience:

- define a dedicated profile tab
- use a specific component for the profile context
- avoid coupling the profile tab to the menu entry implementation

## Practical conclusion

The screenshot message should be read as:

- "There is already a trainings history page"
- "We now want the same history accessible from the user profile"
- "The profile tab must be visible to all users"

That is a functional addition, not just a label change.

## Files to keep in sync

- [`src/main/app/Resources/modules/layout/header/components/user.jsx`](../../src/main/app/Resources/modules/layout/header/components/user.jsx)
- [`src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/components/menu.jsx)
- [`src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx`](../../src/plugin/cursus/Resources/modules/tools/trainings/session/components/main.jsx)
- [`src/plugin/cursus/Controller/User/SessionController.php`](../../src/plugin/cursus/Controller/User/SessionController.php)
- [`src/main/core/Resources/modules/administration/community/profile/components/profile-tab.jsx`](../../src/main/core/Resources/modules/administration/community/profile/components/profile-tab.jsx)
- [`src/main/core/Resources/modules/user/profile/components/main.jsx`](../../src/main/core/Resources/modules/user/profile/components/main.jsx)
- [`src/main/core/Resources/modules/user/profile/components/facets.jsx`](../../src/main/core/Resources/modules/user/profile/components/facets.jsx)

