#!/bin/bash

set -e

echo "Creating required directories in volumes"
mkdir -p var/geoip
mkdir -p files/config
mkdir -p files/data
mkdir -p files/templates

echo "Copying initial config files to /config volume"
cp -R ../initial/config ./

echo "Generating parameters.yml and bundles.ini"
php bin/configure # we run it again to generate parameters.yml inside the volume
composer bundles # we run it again to generate bundles.ini inside the volume
composer delete-cache # fixes install/update errors

echo "Cleaning up unused bundled themes for faster install/update..."
rm -rf src/main/theme/Resources/themes/claroline-black src/main/theme/Resources/themes/claroline-mint src/main/theme/Resources/themes/claroline-ruby

# Wait for MySQL to respond, depends on mysql-client
echo "Waiting for $DB_HOST..."
while ! mysqladmin ping -h "$DB_HOST" --silent; do
  echo "MySQL is down"
  sleep 1
done

echo "MySQL is up"

if [ -f files/installed ]; then
  echo "Claroline is already installed, updating and rebuilding themes and translations..."

  php bin/console claroline:update -vvv
else
  echo "Installing Claroline for the first time..."
  chown -R www-data:www-data var files config # set owner to avoid permission issues later on
  php bin/console claroline:install -vvv

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
    echo "Creating default non-admin user for production : $ADMIN_FIRSTNAME $ADMIN_LASTNAME $ADMIN_USERNAME $ADMIN_PASSWORD $ADMIN_EMAIL"
    echo '*********************************************************************************************************************'

    php bin/console claroline:user:create $ADMIN_FIRSTNAME $ADMIN_LASTNAME $ADMIN_USERNAME $ADMIN_PASSWORD $ADMIN_EMAIL
  else
    echo 'Users already exist or no admin vars detected, Claroline installed without an admin account'
  fi

  echo "In order to create an admin user, run the following command inside the docker container (and replace the variables):"
  echo "php bin/console claroline:user:create -a \$ADMIN_FIRSTNAME \$ADMIN_LASTNAME \$ADMIN_USERNAME \$ADMIN_PASSWORD \$ADMIN_EMAIL"

  touch files/installed
  echo "Claroline installed, created file ./files/installed for future runs of this container"
fi

echo "Clean cache after setting correct permissions, fixes SAML issues"
composer delete-cache # fixes SAML errors

echo "Setting correct file permissions for PROD"
chown -R www-data:www-data var files config
chmod -R 750 var files config
chmod -R 755 public

exec "$@"
