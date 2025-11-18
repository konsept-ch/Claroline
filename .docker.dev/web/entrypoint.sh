#!/bin/bash

set -euo pipefail

APP_DIR="/var/www/html/claroline"
APP_ENV="${APP_ENV:-dev}"
APP_DEBUG="${APP_DEBUG:-0}"
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
  mkdir -p var/cache var/log var/sessions var/tmp var/geoip files/config files/data files/templates public/js public/dist
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

bootstrap_composer() {
  log "Clearing Composer cache"
  composer clear-cache >/dev/null 2>&1 || true

  log "Generating configuration via bin/configure"
  php bin/configure || php bin/configure --default

  log "Installing Composer dependencies"
  composer install --prefer-dist --no-interaction --no-progress --no-scripts

  log "Refreshing bundles configuration"
  composer bundles

  log "Optimizing Composer autoload"
  composer dump-autoload -o
}

bootstrap_node() {
  log "Ensuring Node dependencies are present"
  npm install --legacy-peer-deps --no-progress
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
      log "Creating default admin user ${ADMIN_USERNAME}"
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
    php bin/console claroline:install --env="$APP_ENV" -vvv
    maybe_update_platform
    maybe_create_admin
    touch files/installed
    log "Created files/installed to mark completed install"
  fi
}

disable_maintenance_mode() {
  local maintenance_flag="files/config/.update"
  if [ -f "$maintenance_flag" ]; then
    log "Maintenance flag detected, disabling maintenance mode"
    if php bin/console claroline:maintenance:disable >/dev/null 2>&1; then
      log "Maintenance mode disabled via console command"
    else
      log "Unable to run claroline:maintenance:disable, removing ${maintenance_flag} manually"
      rm -f "$maintenance_flag"
    fi
  fi
  if [ -f "$maintenance_flag" ]; then
    log "Warning: maintenance flag still present at ${maintenance_flag}"
  fi
}

build_frontend() {
  if is_enabled "${WEBPACK_DEV_SERVER:-0}"; then
    log "Starting webpack-dev-server (WEBPACK_DEV_SERVER=1)"
    nohup npm run webpack:dev -- --host=0.0.0.0 --disable-host-check >/var/log/claroline-webpack.log 2>&1 &
  else
    log "Building static frontend assets (set WEBPACK_DEV_SERVER=1 for hot reload)"
    npm run webpack
  fi
}

finalize_permissions() {
  log "Setting liberal permissions for dev"
  chmod -R 777 var files config
}

ensure_directories
prepare_ssl
bootstrap_composer
bootstrap_node
refresh_geoip
wait_for_mysql
run_claroline_tasks
disable_maintenance_mode
dump_js_routes
build_frontend
finalize_permissions

log "Starting Apache"
exec "$@"
