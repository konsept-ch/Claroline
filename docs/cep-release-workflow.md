# CEP Release Baseline (2025-10-31)

## Repository Overview

### Release Matrix (2025-10-31)

| Component | VAL tag | PROD tag | ARCHIVE tag | Notes |
|-----------|---------|----------|-------------|-------|
| Claroline (`claro`) | `claro-val-1.1.6` | `claro-prod-1.1.4` | `claro-archive-2.0.6` | `val` branch protected upstream; `val-env` mirrors VAL deployment. |
| Admin UI (`adm`)    | `adm-val-0.0.3`   | `adm-prod-1.2.3.1` | `adm-archive-0.0.2`   | Archive build predates VAL; both run 0.0.2 app version. |
| Former22 (`former`) | `former-val-0.0.4`| `former-prod-1.4.6.2` | `former-archive-0.0.3` | PROD and VAL diverge from ARCHIVE as expected. |

### Claroline (`claro`)
- Base branch `main` now tracks `origin/main` (`37fb9d536e`); upstream default is still `origin/val`.
- Next step: flip the GitHub default branch to `main` and adjust branch protection so `val` (or the interim `val-env`) can be fast-forwarded per the release checklist.
- Release branches aligned to the deployed snapshots:
  - `val-env` -> `c1f77a1ab1` (mirrors Jelastic VAL; direct update of protected `val` branch blocked)
  - `prod` -> `45db36fc73` (new remote branch)
  - `archive` -> `d231112573` (new remote branch)
- Divergence snapshot: `val-env` is four commits ahead of `prod` (event filters/sorting work); `archive` also includes the infrastructure/doc/docker refresh applied for the archive release.
- Tagged environments:

| Environment | Branch source | Tag | Commit | Version (`VERSION.txt`) |
|-------------|---------------|-----|--------|--------------------------|
| Validation  | `val-env`     | `claro-val-1.1.6`      | `c1f77a1ab1` | 13.5.16 |
| Production  | `prod`        | `claro-prod-1.1.4`     | `45db36fc73` | 13.5.16 |
| Archive     | `archive`     | `claro-archive-2.0.6`  | `d231112573` | 13.5.16 (release `cep_claroline_val_archive_v2_0_6`) |

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
  - `val` -> `e90e19999f`
  - `prod` -> `26cfa0dc67`
  - `archive` -> `78b549217c`
- Divergence snapshot: `val` and `prod` each hold unique attestation/scheduling changes; `archive` remains on the older `78b549217c` state without either set of updates.
- Tagged environments:

| Environment | Branch source | Tag | Commit | App version (`package.json`) |
|-------------|---------------|-----|--------|-------------------------------|
| Validation  | `val`         | `former-val-0.0.4`     | `e90e19999f` | 0.0.3 |
| Production  | `prod`        | `former-prod-1.4.6.2`  | `26cfa0dc67`| 1.0.0 |
| Archive     | `archive`     | `former-archive-0.0.3` | `78b549217c`| 0.0.3 |

Validation and archive now diverge for both Admin UI and Former22, matching the environment split baked into Jelastic.

## Next Steps
- Claroline: decide whether to fast-forward `prod` (and optionally `archive`) to `c1f77a1ab1` or to cherry-pick the four VAL commits back to `main` before cutting new branches.
- Admin UI: pick between backporting the `prod` rewrite into `val`/`archive` or recutting those branches from `60d40ffc58` so validation covers the production build.
- Former22: merge or cherry-pick between `e90e19999f` and `26cfa0dc67` to get a single release head, then fast-forward `archive` from that commit.
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
