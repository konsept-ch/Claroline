#!/bin/bash

set -euo pipefail

APP_DIR="/var/www/html/claroline"
APP_ENV="${APP_ENV:-prod}"
DB_HOST="${DB_HOST:-claroline-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-claroline}"
DB_USER="${DB_USER:-claroline}"
DB_PASSWORD="${DB_PASSWORD:-claroline}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-120}"
GEOIP_DB_PATH="${GEOIP_DB_PATH:-var/geoip/GeoLite2-City.mmdb}"

cd "$APP_DIR"

export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=4096}"

log() {
  echo "[$(date +'%H:%M:%S')] $*"
}

is_enabled() {
  case "${1:-0}" in
    1|true|TRUE|on|ON|yes|YES) return 0 ;;
    *) return 1 ;;
  esac
}

ensure_directories() {
  mkdir -p var/cache var/log var/sessions var/tmp var/geoip files/config files/data files/templates public/js
}

sync_initial_config() {
  if [ -d /var/www/html/initial/config ]; then
    log "Syncing default config into mounted volume"
    rsync -a --ignore-existing /var/www/html/initial/config/ ./config/
  fi
}

prepare_ssl() {
  local server_name="${APP_URL:-claroline.local}"
  server_name="${server_name#http://}"
  server_name="${server_name#https://}"
  server_name="${server_name%%/*}"

  if [ -n "$server_name" ]; then
    sed -i "s/claroline.local/$server_name/g" /etc/apache2/sites-available/claroline.conf /etc/apache2/sites-available/claroline-ssl.conf
  fi
}

warm_up_configuration() {
  log "Generating parameters.yml for the mounted config"
  php bin/configure || php bin/configure --default

  log "Ensuring bundles.ini exists"
  composer bundles
}

refresh_geoip() {
  if is_enabled "${GEOIP_DISABLE:-0}"; then
    log "GEOIP_DISABLE=1, skipping GeoIP download"
    return
  fi

  if [ ! -f "$GEOIP_DB_PATH" ] || find "$GEOIP_DB_PATH" -mtime +7 -print -quit | grep -q "."; then
    log "Downloading GeoIP database (weekly refresh)"
    composer setup-geoip || log "GeoIP download skipped (missing license key?)"
  else
    log "Reusing existing GeoIP database (fresh enough)"
  fi
}

dump_js_routes() {
  local routes_file="public/js/fos_js_routes.json"
  if [ ! -f "$routes_file" ]; then
    log "Dumping FOS JS routes"
    php bin/console fos:js-routing:dump --format=json --target="$routes_file" --env="$APP_ENV" || log "Unable to dump FOS JS routes (command unavailable?)"
  fi
}

wait_for_mysql() {
  log "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
  local waited=0
  while ! mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" --protocol=TCP --silent >/dev/null 2>&1; do
    sleep 2
    waited=$((waited + 2))
    if [ "$waited" -ge "$DB_WAIT_TIMEOUT" ]; then
      log "MySQL did not respond after ${DB_WAIT_TIMEOUT}s"
      exit 1
    fi
    log "Still waiting for MySQL..."
  done
  log "MySQL is up."
}

maybe_update_platform() {
  if [ -n "${PLATFORM_NAME:-}" ]; then
    log "Setting platform name to ${PLATFORM_NAME}"
    sed -i "/name: claroline/c\\name: ${PLATFORM_NAME}" files/config/platform_options.json
  fi

  if [ -n "${PLATFORM_SUPPORT_EMAIL:-}" ]; then
    log "Setting support email to ${PLATFORM_SUPPORT_EMAIL}"
    sed -i "/support_email: null/c\\support_email: ${PLATFORM_SUPPORT_EMAIL}" files/config/platform_options.json
  fi
}

maybe_create_admin() {
  local admin_required_count=1
  local mysql_cmd=(mysql -h "$DB_HOST" -P "$DB_PORT" --protocol=TCP --connect-timeout=5 -u "$DB_USER" "-p$DB_PASSWORD" "$DB_NAME" -N -e "SELECT COUNT(*) FROM claro_user")
  local users
  if users="$("${mysql_cmd[@]}" 2>/dev/null)"; then
    if [ "$users" = "$admin_required_count" ] && \
       [ -n "${ADMIN_FIRSTNAME:-}" ] && \
       [ -n "${ADMIN_LASTNAME:-}" ] && \
       [ -n "${ADMIN_USERNAME:-}" ] && \
       [ -n "${ADMIN_PASSWORD:-}" ] && \
       [ -n "${ADMIN_EMAIL:-}" ]; then
      log "Creating default administrator ${ADMIN_USERNAME}"
      php bin/console claroline:user:create -a "$ADMIN_FIRSTNAME" "$ADMIN_LASTNAME" "$ADMIN_USERNAME" "$ADMIN_PASSWORD" "$ADMIN_EMAIL"
    else
      log "Users already exist or admin env vars missing, skipping auto user creation"
    fi
  else
    log "Unable to inspect users table, skipping admin creation"
  fi
}

run_claroline_tasks() {
  if [ -f files/installed ]; then
    if is_enabled "${SKIP_REBUILD:-0}"; then
      log "files/installed detected but SKIP_REBUILD=1 so claroline:update will be skipped"
    else
      log "Updating Claroline"
      php bin/console claroline:update --env="$APP_ENV" -vvv
    fi
  else
    log "Running initial Claroline installation"
    chown -R www-data:www-data var files config
    php bin/console claroline:install --env="$APP_ENV" -vvv
    maybe_update_platform
    maybe_create_admin
    touch files/installed
    log "Created files/installed to mark completed install"
  fi
}

finalize_permissions() {
  log "Normalizing permissions for production"
  chown -R www-data:www-data var files config
  chmod -R 750 var files config
  chmod -R 755 public
}

warn_if_dev_server() {
  if is_enabled "${WEBPACK_DEV_SERVER:-0}"; then
    log "WEBPACK_DEV_SERVER=1 requested but production images ship with pre-built assets; ignoring."
  fi
}

ensure_directories
sync_initial_config
prepare_ssl
warm_up_configuration
refresh_geoip
wait_for_mysql
run_claroline_tasks
dump_js_routes
warn_if_dev_server
finalize_permissions

log "Starting Apache"
exec "$@"
