# Git / Versioning (CEP Claroline)

## Scope
This repository covers CEP Claroline only.

- Active branches: `main`, `cep/val`, `cep/prod`
- Release flow here: CEP Claroline only

Out of scope for this repo:
- `evaluations` deployments
- `reception` deployments

Those are managed in their own dedicated repositories.

## Rules
- Do not commit directly to `cep/val` or `cep/prod`.
- Work from short-lived branches (`feat/...`, `fix/...`, `hotfix/...`).
- Merge via PR.
- Tags are immutable once published.

## Release flow
1. Branch from `cep/prod`.
2. PR to `cep/val`.
3. Validation in Jelastic VAL.
4. RC tag on `cep/val`: `claroline-cep-vX.Y.Z-rc.N`.
   Example: `claroline-cep-v1.2.4-rc.1`
5. If approved, merge `cep/val` -> `cep/prod`.
6. Final tag on `cep/prod`: `claroline-cep-vX.Y.Z`.

## Current operating rule
- No branch deletion for now.
- Keep old branches untouched until global migration is fully validated.
