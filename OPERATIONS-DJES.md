DJES Operations Notes (Factual)
===============================

Purpose
-------

This document records DJES deployment/process facts and separates them from assumptions.
Update it after each infra or release process change.


Facts Confirmed
---------------

- Repository and branch model:
  - Default branch: `main`
  - DJES branches: `djes/dev`, `djes/prod`
  - Legacy backup branch: `djes-root`
- DJES release tag format:
  - `claroline-djes-vX.Y.Z-rc.N`
- Release trigger process:
  - Tags are pushed first.
  - Release is created in GitHub from that tag.
  - Existing workflow `.github/workflows/docker-publish.yml` is triggered on `release: released`.
- Temporary workflow cleanup:
  - `.github/workflows/djes-prod-tag.yml` was intentionally removed.
- App/API behavior observed on 2026-03-06:
  - Main page and static assets were served in `200`.
  - Endpoint `/apiv2/info` returned `500` before fix.
  - Guard fix for anonymous token handling was added in:
    - `src/plugin/analytics/Manager/AnalyticsManager.php`

Observed Production Topology
----------------------------

- There are at least two Claroline-facing public domains in production:
  - `https://www.sscm-formation.ch/`
  - `https://espaces.sscm-formation.ch/`
- The `espaces` instance is configured as a Claroline app with:
  - `serverUrl = https://espaces.sscm-formation.ch`
  - platform name `SSCM Formation`
  - theme `dgjes`
- The login redirect configured on the `espaces` instance points back to:
  - `https://www.sscm-formation.ch/#/home/accueil`
- The repo-level Docker/compose files model a single Claroline stack with one app service and one MySQL service per deployment; they do not encode the relationship between `www` and `espaces`.

What This Suggests
------------------

- The two domains are most likely two front doors for the same Claroline product line, not a single built-in Claroline "workspace" feature.
- `espaces` may be:
  - a separate Claroline deployment using the same brand/data model, or
  - a portal-style deployment that redirects users back to the main site after authentication.
- The repository does not currently document whether the two instances share:
  - the same database,
  - a replicated database,
  - or only the same code/base image.

Open Questions
--------------

- Is `espaces.sscm-formation.ch` reading from the same MySQL instance as `www.sscm-formation.ch`?
- Is the redirect from `espaces` to `www` intentional for all users, or only after login/registration?
- Is there an infra diagram, runbook, or host configuration that defines the relationship between the two vhosts?
- If not, this should be added to the operations documentation before changing application code.
