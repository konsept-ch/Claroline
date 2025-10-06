#!/bin/bash

set -euo pipefail

# Usage: ./scripts/seed-dev.sh [count]
# Creates one admin user and N demo users (default N=5).

cd "$(dirname "$0")/.."

if [ ! -f files/installed ]; then
  echo "Error: Platform not installed yet (files/installed missing)." >&2
  echo "Start the dev stack and let claroline:install finish before seeding." >&2
  exit 1
fi

COUNT=${1:-5}

echo "Seeding Claroline with demo users (COUNT=$COUNT)"

echo "Creating admin user: admin / admin"
php bin/console claroline:user:create -a Admin User admin admin admin@example.com || echo "Admin already exists, skipping"

for i in $(seq 1 "$COUNT"); do
  username="demo$i"
  email="demo$i@example.com"
  echo "Creating user: $username / password=$username"
  php bin/console claroline:user:create "Demo" "$i" "$username" "$username" "$email" || echo "User $username may already exist, skipping"
done

echo "Seed complete."

