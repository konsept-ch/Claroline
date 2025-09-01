#!/bin/bash

set -e

echo "Preparing writable directories and clearing cache"
mkdir -p var files config var/geoip || true
# Ensure Symfony cache tree exists to avoid warmup failures on some hosts
mkdir -p var/cache/dev/doctrine/orm || true
chmod -R 777 var files config || true
composer delete-cache || true # proactively clear Symfony cache to avoid rmdir issues

echo "Installing dependencies (or checking if correct ones are installed)"
export COMPOSER_ALLOW_SUPERUSER=1
# Ensure parameters are generated even when skipping composer scripts
php bin/configure || true
# Skip composer scripts to avoid unconditional geoip download; run what we need manually
composer install --no-scripts
# Rebuild bundles that are usually created by composer scripts
composer bundles || true
# Conditionally download/update GeoIP database (persisted via volume)
GEOIP_DIR="var/geoip"
GEOIP_DB="$GEOIP_DIR/GeoLite2-City.mmdb"
GEOIP_LOCK="$GEOIP_DIR/.geoip.update.lock"

# Helper: return 0 if db missing or older than 7 days
needs_geoip_update() {
  if [ ! -s "$GEOIP_DB" ]; then
    return 0
  fi
  # Compare mtime with now-7days; use find to be POSIX-friendly
  if find "$GEOIP_DIR" -maxdepth 1 -type f -name "$(basename "$GEOIP_DB")" -mtime -7 | grep -q .; then
    return 1
  fi
  return 0
}

mkdir -p "$GEOIP_DIR" || true

# Allow disabling GeoIP in dev via env toggle
if [ "${GEOIP_DISABLE:-0}" = "1" ]; then
  echo "GeoIP download disabled (GEOIP_DISABLE=1). Skipping."
elif needs_geoip_update; then
  (
    # Use a subshell + flock if available; otherwise best-effort lock
    if command -v flock >/dev/null 2>&1; then
      exec 9>"$GEOIP_LOCK"
      if flock -n 9; then
        echo "GeoIP database missing or older than 7 days. Updating..."
        php bin/console claroline:geoip:download || echo "GeoIP download skipped or failed (license key not set?)."
        rm -f "$GEOIP_LOCK" || true
      fi
      exec 9>&-
    else
      if [ ! -f "$GEOIP_LOCK" ]; then
        echo "GeoIP database missing or older than 7 days. Updating..."
        touch "$GEOIP_LOCK"
        php bin/console claroline:geoip:download || echo "GeoIP download skipped or failed (license key not set?)."
        rm -f "$GEOIP_LOCK" || true
      fi
    fi
  )
else
  echo "GeoIP database is fresh (<7 days). Skipping download."
fi

npm install --legacy-peer-deps # if package-lock.json exists, this takes ~3 seconds (every subsequent run with no changes to deps)
# --legacy-peer-deps is needed until all dependencies are compatible with npm 7 (until npm install runs without error)

# Wait for MySQL to respond, depends on mysql-client
echo "Waiting for $DB_HOST (no TLS in dev)..."
while ! mysqladmin ping -h "$DB_HOST" --protocol=tcp --skip-ssl --connect-timeout=2 --silent; do
  echo "MySQL is down"
  sleep 1
done

echo "MySQL is up"

if [ -f files/installed ]; then
  echo "Claroline is already installed, updating and rebuilding themes and translations..."

  php bin/console claroline:update --env=dev -vvv
else
  echo "Installing Claroline for the first time..."
  php bin/console claroline:install --env=dev -vvv

  if [[ -v PLATFORM_NAME ]]; then
    echo "Changing platform name to $PLATFORM_NAME";
    sed -i "/name: claroline/c\name: $PLATFORM_NAME" files/config/platform_options.json
  fi

  if [[ -v PLATFORM_SUPPORT_EMAIL ]]; then
    echo "Changing platform support email to $PLATFORM_SUPPORT_EMAIL";
    sed -i "/support_email: null/c\support_email: $PLATFORM_SUPPORT_EMAIL" files/config/platform_options.json
  fi

  USERS=$(mysql $DB_NAME -u $DB_USER -p$DB_PASSWORD -h $DB_HOST --protocol=tcp --skip-ssl -se "select count(*) from claro_user")

  if [ "$USERS" == "1" ] && [ -v ADMIN_FIRSTNAME ] && [ -v ADMIN_LASTNAME ] && [ -v ADMIN_USERNAME ] && [ -v ADMIN_PASSWORD ]  && [ -v ADMIN_EMAIL ]; then
    echo '*********************************************************************************************************************'
    echo "Creating default admin user for development : $ADMIN_FIRSTNAME $ADMIN_LASTNAME $ADMIN_USERNAME $ADMIN_PASSWORD $ADMIN_EMAIL"
    echo '*********************************************************************************************************************'

    php bin/console claroline:user:create -a $ADMIN_FIRSTNAME $ADMIN_LASTNAME $ADMIN_USERNAME $ADMIN_PASSWORD $ADMIN_EMAIL
  else
    echo 'Users already exist or no admin vars detected, Claroline installed without an admin account'
  fi

  touch files/installed
  echo "Claroline installed, created file ./files/installed for future runs of this container"
fi

echo "Clean cache after setting correct permissions, fixes SAML issues"
composer delete-cache # fixes SAML errors

echo "Setting correct file permissions for DEV"
chmod -R 777 var files config

# Normalize SSL vhost file and ensure no duplicate Listen 443 (handles CRLF too)
if [ -f /etc/apache2/sites-enabled/claroline-ssl.conf ]; then
  sed -i 's/\r$//' /etc/apache2/sites-enabled/claroline-ssl.conf || true
  sed -i '/^Listen[[:space:]]\+443[[:space:]]*$/d' /etc/apache2/sites-enabled/claroline-ssl.conf || true
fi

if [ "${WEBPACK_DEV_SERVER:-1}" = "1" ]; then
  echo "webpack-dev-server starting as a background process..."
  export NODE_OPTIONS="${NODE_OPTIONS:---max_old_space_size=4096}"
  nohup npm run webpack:dev -- --host=0.0.0.0 --disable-host-check &
else
  echo "Building assets once (no dev server)..."
  npm run webpack
fi

echo "Starting Apache2 in the foreground"
exec "$@"
