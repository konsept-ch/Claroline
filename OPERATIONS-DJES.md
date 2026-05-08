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

