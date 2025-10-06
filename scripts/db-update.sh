#!/usr/bin/env bash

# Helper to ensure the Claroline database is fully installed/updated in Docker dev.
# - Runs claroline:install on first run (if files/installed is absent)
# - Otherwise runs claroline:update and forces Cursus bundle migrations up to farthest
# - Verifies key tables and prints a short status
#
# Usage (from repo root):
#   bash Claroline/scripts/db-update.sh
#
# Optional flags:
#   --only-cursus           Only upgrade the Cursus bundle
#   --ensure-session-years  Also apply the SQL helper for session_years (idempotent)
#   --no-assets             Pass --no_asset to claroline:update to speed up
#
# Note: If the helper table 'claro_cursusbundle_session_years' is missing, the SQL helper
#       is applied automatically even without --ensure-session-years.

set -euo pipefail

CFILE="Claroline/docker-compose.dev.yml"
COMPOSE=""

if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker-compose"
else
  echo "Docker Compose is required (either 'docker compose' or 'docker-compose')." >&2
  exit 1
fi

exec_web() {
  ${COMPOSE} -f "$CFILE" exec -T web "$@"
}

wait_db() {
  echo "Waiting for claroline-db (no TLS in dev)..."
  while ! ${COMPOSE} -f "$CFILE" exec -T web sh -lc 'mysqladmin ping -h "$DB_HOST" --protocol=tcp --skip-ssl --connect-timeout=2 --silent' 2>/dev/null; do
    echo "MySQL is down"
    sleep 1
  done
  echo "MySQL is up"
}

ONLY_CURSUS=0
ENSURE_SESSION_YEARS=0
NO_ASSETS=0

for arg in "$@"; do
  case "$arg" in
    --only-cursus) ONLY_CURSUS=1 ;;
    --ensure-session-years) ENSURE_SESSION_YEARS=1 ;;
    --no-assets) NO_ASSETS=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

wait_db

# Decide install vs update from host flag file
if [ ! -f Claroline/files/installed ]; then
  echo "First install detected (Claroline/files/installed missing)."
  exec_web php bin/console claroline:install --env=dev -vvv
else
  echo "Existing install detected. Updating platform packages..."
  if [ "$NO_ASSETS" = "1" ]; then
    exec_web php bin/console claroline:update --env=dev -vvv --no_asset
  else
    exec_web php bin/console claroline:update --env=dev -vvv
  fi
fi

if [ "$ONLY_CURSUS" = "1" ]; then
  echo "Upgrading Cursus bundle to farthest migration..."
  exec_web php bin/console claroline:migration:upgrade ClarolineCursusBundle --target=farthest -vvv
else
  # Ensure Cursus specifically is at farthest (creates helper table and indexes we rely on)
  echo "Ensuring Cursus bundle is at farthest migration..."
  exec_web php bin/console claroline:migration:upgrade ClarolineCursusBundle --target=farthest -vvv
fi

# Decide whether to apply the session_years helper SQL
NEED_SESSION_YEARS=0
if [ "$ENSURE_SESSION_YEARS" = "1" ]; then
  NEED_SESSION_YEARS=1
else
  # Auto-apply if table is missing
  if ${COMPOSE} -f "$CFILE" exec -T web sh -lc 'mysql -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" --protocol=tcp --skip-ssl -N -B -e "SHOW TABLES LIKE \"claro_cursusbundle_session_years\"" "$DB_NAME" | grep -q claro_cursusbundle_session_years'; then
    NEED_SESSION_YEARS=0
  else
    NEED_SESSION_YEARS=1
  fi
fi

if [ "$NEED_SESSION_YEARS" = "1" ]; then
  echo "Applying idempotent SQL for session_years helper..."
  exec_web sh -lc 'mysql -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" --protocol=tcp --skip-ssl "$DB_NAME" < scripts/db-ensure-session-years.sql'
fi

echo "\nVerification:"
exec_web sh -lc 'mysql -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" --protocol=tcp --skip-ssl -e "\
  SELECT \"core tables\" AS section; \
  SHOW TABLES LIKE \"claro_user\"; \
  SELECT \"cursus tables\" AS section; \
  SHOW TABLES LIKE \"claro_cursusbundle_course_session\"; \
  SHOW TABLES LIKE \"claro_cursusbundle_session_years\"; \
  SELECT \"counts\" AS section; \
  SELECT (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"$DB_NAME\") AS total_tables; \
" "$DB_NAME"'

echo "\nDone."
