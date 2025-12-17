#!/bin/bash

set -e

read_param() {
  local key="$1"
  local file="$2"
  if [ ! -f "$file" ]; then
    echo ""
    return 0
  fi

  # Expects "  key: value" YAML lines.
  local raw
  raw="$(grep -E "^[[:space:]]+$key:" "$file" | head -n 1 | sed -E "s/^[[:space:]]+$key:[[:space:]]*//")"
  raw="$(echo "$raw" | sed -E 's/^"//; s/"$//; s/^~$//')"
  echo "$raw"
}

PARAM_FILE="config/parameters.yml"

# If a parameters file exists but points to another DB host/user/pass (common when copied from a Windows setup),
# regenerate it from the current container env vars.
if [ -f "$PARAM_FILE" ] && [ -n "${DB_HOST:-}" ] && [ -n "${DB_USER:-}" ]; then
  current_host="$(read_param database_host "$PARAM_FILE")"
  current_user="$(read_param database_user "$PARAM_FILE")"
  current_pass="$(read_param database_password "$PARAM_FILE")"

  if [ "$current_host" != "$DB_HOST" ] || [ "$current_user" != "$DB_USER" ] || [ -n "${DB_PASSWORD:-}" -a "$current_pass" != "$DB_PASSWORD" ]; then
    echo "config/parameters.yml does not match container DB env; regenerating with bin/configure..."
    rm -f "$PARAM_FILE"
  fi
fi

echo "Installing dependencies (or checking if correct ones are installed)"
composer install # if composer.lock exists, this takes ~2 seconds (every subsequent run with no changes to deps)
npm install --legacy-peer-deps # if package-lock.json exists, this takes ~3 seconds (every subsequent run with no changes to deps)
# --legacy-peer-deps is needed until all dependencies are compatible with npm 7 (until npm install runs without error)

# Wait for MySQL to respond, depends on mysql-client
echo "Waiting for $DB_HOST..."
while ! mysqladmin ping -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --protocol=tcp --skip-ssl --connect-timeout=2 --silent; do
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

  USERS=$(mysql $DB_NAME -u $DB_USER -p$DB_PASSWORD -h $DB_HOST -se "select count(*) from claro_user")

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

if [ -n "${APP_URL:-}" ] && [ -f files/config/platform_options.json ]; then
  echo "Syncing platform URL in files/config/platform_options.json with APP_URL=$APP_URL"
  php -r '
    $path = "files/config/platform_options.json";
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) { fwrite(STDERR, "Invalid JSON in $path\n"); exit(0); }
    $appUrl = getenv("APP_URL") ?: "http://localhost";
    $parts = parse_url($appUrl);
    $host = $parts["host"] ?? "";
    $port = $parts["port"] ?? null;
    $domain = $host ? ($port ? ($host . ":" . $port) : $host) : "localhost";
    if (!isset($data["internet"]) || !is_array($data["internet"])) { $data["internet"] = []; }
    $data["internet"]["platform_url"] = $appUrl;
    $data["internet"]["domain_name"] = $domain;
    if (!isset($data["server"]) || !is_array($data["server"])) { $data["server"] = []; }
    $data["server"]["tmp_dir"] = "/tmp";
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
  ';
fi

echo "Clean cache after setting correct permissions, fixes SAML issues"
composer delete-cache # fixes SAML errors

echo "Setting correct file permissions for DEV"
chmod -R 777 var files config

echo "webpack-dev-server starting as a background process..."
nohup npm run webpack:dev -- --host=0.0.0.0 --disable-host-check &

echo "Starting Apache2 in the foreground"
exec "$@"
