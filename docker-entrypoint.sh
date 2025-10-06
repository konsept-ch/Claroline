#!/bin/sh
set -e

if [ "${CLAROLINE_AUTO_INSTALL:-0}" = "1" ]; then
  php bin/console claroline:install --no-interaction
fi

exec "$@"
