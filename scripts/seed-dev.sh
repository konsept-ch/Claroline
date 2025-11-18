#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

APP_ENV="${APP_ENV:-dev}"

cd "$PROJECT_ROOT"

echo ">>> Seeding Claroline demo data (env: ${APP_ENV})"
php scripts/seed-cursus.php --env="${APP_ENV}" "$@"
