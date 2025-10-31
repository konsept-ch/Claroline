# CEP Release Baseline (2025-10-31)

## Repository Overview

### Release Matrix (2025-10-31)

| Component | VAL tag | PROD tag | ARCHIVE tag | Notes |
|-----------|---------|----------|-------------|-------|
| Claroline (`claro`) | `claro-val-1.1.6` | `cep-1.1.6` | `cep-archive-0.0.1` | `val` branch protected upstream; `val-env` mirrors VAL deployment. |
| Admin UI (`adm`)    | `adm-val-0.0.3`   | `adm-prod-1.2.3.1` | `adm-archive-0.0.2`   | Archive build predates VAL; both run 0.0.2 app version. |
| Former22 (`former`) | `former-val-0.0.4`| `cep-2.0.0` | `archive-0.0.4` | PROD and VAL diverge from ARCHIVE as expected. |

### Claroline (`claro`)
- Base branch `main` now tracks `origin/main` (`37fb9d536e`); upstream default is still `origin/val`.
- Next step: flip the GitHub default branch to `main` and adjust branch protection so `val` (or the interim `val-env`) can be fast-forwarded per the release checklist.
- Release branches aligned to the deployed snapshots:
  - `val-env` -> `c1f77a1ab1` (mirrors Jelastic VAL; direct update of protected `val` branch blocked)
  - `prod` -> `c1f77a1ab1` (fast-forwarded to `cep-1.1.6`)
  - `archive` -> `b64a07c347` (tag `cep-archive-0.0.1`)
- Divergence snapshot: `prod` now matches `val-env`; `archive` stays two commits ahead with the CEP archive packaging work (`b64a07c347`, `2ed08aef75`).
- Tagged environments:

| Environment | Branch source | Tag | Commit | Version (`VERSION.txt`) |
|-------------|---------------|-----|--------|--------------------------|
| Validation  | `val-env`     | `claro-val-1.1.6`      | `c1f77a1ab1` | 13.5.16 |
| Production  | `prod`        | `cep-1.1.6`            | `c1f77a1ab1` | 13.5.16 |
| Archive     | `archive`     | `cep-archive-0.0.1`    | `b64a07c347` | 13.5.16 (`ARCHIVE_MODE` via env) |

### Admin UI (`adm`)
- Base branch `main` continues at `148bc2f`.
- Release branches now aligned to the hosted builds:
  - `val` -> `6b1754b96a`
  - `prod` -> `60d40ffc58`
  - `archive` -> `534fc06d9d`
- Divergence snapshot: `prod` contains the large RTK/Saga rewrite not yet in `val`/`archive`; `val` alone adds downloadable content over the archive baseline.
- Tagged environments:

| Environment | Branch source | Tag | Commit | App version (`package.json`) |
|-------------|---------------|-----|--------|-------------------------------|
| Validation  | `val`         | `adm-val-0.0.3`        | `6b1754b96a` | 0.0.2 |
| Production  | `prod`        | `adm-prod-1.2.3.1`     | `60d40ffc58` | 1.0.0 |
| Archive     | `archive`     | `adm-archive-0.0.2`    | `534fc06d9d` | 0.0.2 |

### Former22 (`former`)
- Base branch `main` remains at `de81539`.
- Release branches established for deployment parity:
  - `val` -> `e90e19999f` (tag `former-val-0.0.4`)
  - `prod` -> `09e4633f59` (tag `cep-2.0.0`)
  - `archive` -> `e90e19999f` (tag `archive-0.0.4`)
- Divergence snapshot: `val`/`archive` include the archive-specific `yearMinusOne` filtering and placeholder tweaks; `prod` sticks to the `cep-2.0.0` line that relies on `ARCHIVE_MODE` flags instead.
- Tagged environments:

| Environment | Branch source | Tag | Commit | App version (`package.json`) |
|-------------|---------------|-----|--------|-------------------------------|
| Validation  | `val`         | `former-val-0.0.4`     | `e90e19999f` | 0.0.3 |
| Production  | `prod`        | `cep-2.0.0`            | `09e4633f59`| 1.0.0 |
| Archive     | `archive`     | `archive-0.0.4`        | `e90e19999f`| 0.0.3 |

Validation and archive now diverge for both Admin UI and Former22, matching the environment split baked into Jelastic.

## Next Steps
- Claroline: keep `prod` and `val-env` aligned at `c1f77a1ab1` (`cep-1.1.6`) and only advance `archive` when the `cep-archive-0.0.x` packaging is refreshed.
- Admin UI: pick between backporting the `prod` rewrite into `val`/`archive` or recutting those branches from `60d40ffc58` so validation covers the production build.
- Former22: reconcile the split histories by promoting the `cep-2.0.0` line (`09e4633f59`) into the shared branch structure, or scope the archive-only commits behind the environment flag before re-cutting VAL/ARCHIVE.
- After the targets are chosen, fast-forward each environment branch (`git branch -f val|archive|prod <commit>`), push, and retag. Claroline continues to use `val-env` for the VAL deployment until `val` protection is updated.

## Unified Branch Strategy
1. **Trunk:** `main` is the sole integration branch across Claroline, Admin UI and Former22. All feature work merges there through PRs.
2. **Validation cut:** When code needs staging, create or fast-forward the `val` branch from `main` at the chosen commit (`git checkout main && git pull && git branch -f val HEAD`). Build and deploy the VAL environment from that branch and tag it (`component-val-x.y.z`). *(For Claroline use `val-env` until the protected `val` branch can be repointed.)*
3. **Archive cut:** When an archival deployment is required, fast-forward `archive` from `val` and redeploy. Retag with the archive marker (`component-archive-x.y.z`).
4. **Production cut:** When production is ready, fast-forward `prod` directly from `val` (or the promotion commit agreed on) and tag it (`component-prod-x.y.z`). Production hotfixes travel `prod` -> `main` -> `val`/`archive` to keep branches aligned. Archive and production promotions are independent; take whichever path is relevant for the release.

## Tagging Convention
- Format: `<component>-<environment>-<semantic version>`.
  - Components: `claro`, `adm`, `former`.
  - Environments: `val`, `archive`, `prod`.
- Tags are annotated and point to the exact commits deployed in each environment. The semantic version mirrors the application's own versioning (`VERSION.txt` for Claroline, `package.json` for the Node services).

## Promotion Workflow
1. **Prep on `main`:** Stabilise the code, ensure CI is green.
2. **Cut VAL release:** Fast-forward `val` to the release commit, push branch + tag, deploy VAL.
3. **Validate:** Run regression tests, user acceptance, data checks. Fixes merge back to `main` and the `val` branch is reset as needed.
4. **Archive promotion:** When VAL is cleared for archival usage, fast-forward `archive` from `val`, push branch + tag, deploy ARCHIVE.
5. **Production promotion:** Repeat for `prod` once production approval is granted. Use the tagged commit so the deployment is reproducible.
6. **Post-release hygiene:** Merge promotion branches back into `main` (fast-forward), close or retarget open PRs, and update changelog entries referencing the new tags.

## Release Checklist
- Confirm `main` is up to date: `git checkout main && git pull origin main`.
- Pick the release commit (usually `main` HEAD) and fast-forward the environment branch: `git branch -f val <commit>`.
- Push branch updates (`git push origin val`) and create the tag: `git tag -a component-val-x.y.z <commit> -m "Component VAL x.y.z"`; push with `git push origin component-val-x.y.z`.
- Deploy from the environment branch and record the tag in Jelastic.
- Promote to ARCHIVE or PROD by repeating the fast-forward/tag steps off `val` as needed (no dependency between `archive` and `prod` cuts).
- Update `Claroline/docs/cep-release-workflow.md` with the new tag names and deployment notes.

## Maintenance Notes
- Keep GitHub defaults on `main` for every repo and protect the environment branches (`val`, `archive`, `prod`) with “fast-forward or merge only” rules so history stays linear.
- Run a quick status check before each promotion: `git show <tag> --no-patch` to verify the commit and `git status` to ensure a clean working tree.
- When legacy services (Evaluations, Postal, Reception) receive updates, adopt the same branch/tag pattern (`eval-prod-...`, etc.) and extend the release matrix so all environments share a single reference page.
- Once the protected `val` branch can be repointed, delete the temporary `val-env` alias to avoid confusion.

## Pending Actions
- Flip each GitHub remote default to `main` instead of `val`/`archive` so the release checklist mirrors the trunk-first flow.
- Update branch protection to allow maintainers to fast-forward the `val` (or temporary `val-env`) release line while keeping `archive` and `prod` fast-forward-only for deployment parity.
- Adjust CI/CD pipelines to build and deploy from `main` for integration, and from the `val`/`archive`/`prod` branches for environment jobs.
- Communicate the tagging scheme to the team so downstream tooling (release notes, deployment dashboards) can consume the new identifiers.
