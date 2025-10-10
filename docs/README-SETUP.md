Claroline — Local Setup & Requirements
=====================================

This document summarizes installation requirements and setup paths found in this repository, for both Docker and from-source workflows.

Quick Start
-----------

- Docker (dev):
  - `docker compose -f docker-compose.dev.yml up --build`
  - App: `http://localhost:8088` (webpack dev server on `http://localhost:8080` if enabled)
  - Default dev admin (from compose env): `root / claroline`
  - To speed up Windows/dev and avoid hot reload complexity, we default to static assets (`WEBPACK_DEV_SERVER=0`).
  - GeoIP download is disabled in dev by default (`GEOIP_DISABLE=1`). Set a MaxMind key and remove the toggle if needed.
- Docker (prod image):
  - `docker compose -f docker-compose.prod.yml up -d`
  - Set `APP_URL`, DB env, and optional `PLATFORM_*` in compose or an override file
- From source:
  - `composer install` and `npm install` (use `--legacy-peer-deps` with npm 7+)
  - `php bin/configure` then `npm run webpack`
  - Install DB schema/data: `php bin/console claroline:install -vvv`
  - Serve: `php -S 127.0.0.1:8000 -t public`

System Requirements (From Source)
---------------------------------

- PHP: 8.1+ (8.2 recommended). Required extensions:
  - curl, dom, fileinfo, gd, iconv, intl, json, mbstring, pdo, pdo_mysql, openssl, simplexml, zip
  - Reference: `composer.json:require`
- Database: MySQL 8.0
  - Defaults: host `127.0.0.1`, name `claroline`, user `root`, password empty
  - Reference: `config/parameters.yml.dist:1`
- Node.js: 14/16 LTS recommended
  - npm 6 is ideal; with npm 7+ use `--legacy-peer-deps`
  - Reference: `package.json:engines`
- Tools: Composer 2.x, Git

Docker (Development)
--------------------

- Requirements: Docker Desktop with Compose v2
- Ports exposed by default:
  - Web: `8088 -> 80` (Apache HTTP), `8443 -> 443` (Apache HTTPS, self-signed)
  - Webpack dev-server: `8080 -> 8080` (optional)
  - MySQL: `3307 -> 3306`
  - Adjust mappings in `docker-compose.dev.yml`
- Persistent MySQL data: `../mysql` relative to the repo root must be writable
- Start stack:
  - `docker compose -f docker-compose.dev.yml up --build`
  - First boot installs dependencies, waits for DB, installs Claroline, then starts webpack dev server and Apache
  
Fast dev startup (skip rebuild)
-------------------------------

- To skip the update/theme/translations rebuild on container restarts, set `SKIP_REBUILD=1` on the `web` service (dev only).
  - Effect: skips `claroline:update` (which runs `assets:install`, JS translations dump, and theme build). Startup typically saves 2–3 minutes.
  - When to use: daily dev restarts where code, themes and translations did not change.
  - When not to use: after composer updates, enabling/disabling bundles/plugins, or when you changed translations/themes — run a full `claroline:update` once (unset `SKIP_REBUILD`).
  
Example override in `docker-compose.dev.yml`:

```yaml
services:
  web:
    environment:
      SKIP_REBUILD: "1"
```
- Default environment (web service):
  - DB: `DB_HOST=claroline-db`, `DB_NAME=claroline`, `DB_USER=claroline`, `DB_PASSWORD=claroline`
  - Platform branding: `PLATFORM_NAME`, `PLATFORM_SUPPORT_EMAIL`
  - Optional admin auto-creation (dev only): set `ADMIN_*` variables (compose defines `root/claroline` by default)
  - Reference: `docker-compose.dev.yml:1`, `.docker.dev/web/entrypoint.sh:1`
  - App URL used by the frontend: set `APP_URL=http://localhost:8088` (already configured)

From Source (Without Docker)
----------------------------

1) Install dependencies
- `composer install` (use `--no-dev --optimize-autoloader` for prod)
- `npm install` (or `npm install --legacy-peer-deps` with npm 7+)

2) Configure application
- Generate `config/parameters.yml` from `config/parameters.yml.dist`:
  - `php bin/configure` (reads `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` from env if present)
- Symfony env (optional): define `APP_ENV`, `APP_DEBUG`, and `DATABASE_URL` in `.env.local` (example present)
  - Reference: `.env.local:1`, `config/bootstrap.php:1`

3) Build and install
- Frontend build: `npm run webpack`
- Backend install: `php bin/console claroline:install -vvv`
- Create admin (optional):
  - `php bin/console claroline:user:create FIRST LAST USER PASS EMAIL -a`

4) Serve the app
- Development: `php -S 127.0.0.1:8000 -t public` and browse http://127.0.0.1:8000
- Production: configure Apache/Nginx to serve `public/` as document root

Docker (Production)
-------------------

- Use the prebuilt image and compose file:
  - `docker compose -f docker-compose.prod.yml up -d`
- Configure via env/compose:
  - `APP_URL`, `APP_ENV=prod`, `APP_DEBUG=0`
  - DB: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
  - Optional branding: `PLATFORM_NAME`, `PLATFORM_SUPPORT_EMAIL`
- Volumes persist `config`, `files`, `public/js`, `public/themes`, `var`
- References: `docker-compose.prod.yml:1`, `.docker/web/entrypoint.sh:1`

Updating an Existing Install
----------------------------

- With Docker (dev): container auto-runs updates on restart when `files/installed` exists
  - Reference: `.docker.dev/web/entrypoint.sh:1`
- From source: `php bin/console claroline:update -vvv`

Permissions & Cache
-------------------

- Writable directories: `var`, `files`, `config`
  - Dev container sets permissive rights automatically
- Clear cache if needed: `rm -rf var/cache/*` or `composer delete-cache`
  - Reference: `composer.json:scripts`

Known Pitfalls
--------------

- Line endings on Windows: shell scripts must use LF, not CRLF
  - Enforced with `.gitattributes` rule `*.sh text eol=lf`
- npm peer dependency conflicts on npm 7+: use `--legacy-peer-deps`
- Port conflicts on 8088/8080: adjust Compose port mappings if IIS or other services are bound
- MySQL TLS with self-signed certs: dev flow disables TLS or falls back automatically.
- Apache SSL vhost: remove duplicate `Listen 443` lines; our image strips them at runtime.
- Public symlinks (e.g., `public/bundles`): ignored in `.dockerignore` to avoid build errors.
- Webpack assets blank page: ensure `APP_URL` includes the port (e.g., `http://localhost:8088`).
- Very slow page loads on Windows:
  - Set `APP_DEBUG=0` to disable the Symfony toolbar.
  - Prefer serving static assets: set `WEBPACK_DEV_SERVER=0` (default here).
  - Keep the repo under WSL2 home for faster bind-mounts.
  - Increase Docker resources (4 CPU, 6–8 GB RAM).

GeoIP Database (faster, persistent)
-----------------------------------

- The GeoIP mmdb is persisted to a named volume `geoip-data` and only refreshed if missing or older than 7 days.
- Manual update: `docker compose -f docker-compose.dev.yml run --rm web php bin/console claroline:geoip:download`

Creating Users
--------------

- Promote an existing user to admin via SQL:
  - `INSERT IGNORE INTO claro_user_role (user_id, role_id)
     SELECT u.id, r.id FROM claro_user u JOIN claro_role r ON r.name='ROLE_ADMIN' WHERE u.username='demo1';`

Optional Mail Catcher
---------------------

- MailHog is included (SMTP 1025, UI http://localhost:8025).
- `files/config/platform_options.json` preconfigured for SMTP `mailhog:1025`.

Front-end assets (dev vs static)
--------------------------------

- Static (recommended on Windows): `WEBPACK_DEV_SERVER=0` — Apache serves `public/dist` (fast, predictable).
- Dev server (hot reload): `WEBPACK_DEV_SERVER=1` and port `8080:8080`; we also enable `writeToDisk`.

Key Files & References
----------------------

- Docker (dev): `docker-compose.dev.yml:1`, `.docker.dev/web/Dockerfile:1`, `.docker.dev/web/entrypoint.sh:1`
- App config: `config/parameters.yml.dist:1`, `bin/configure:1`, `config/bootstrap.php:1`
- Builds: `package.json:1`, `webpack.config.dev.js:1`, `webpack.config.prod.js:1`

Sample Data (Cursus)
--------------------

- A small seeder is available to create an example training, session and one event:
  - `docker compose -f docker-compose.dev.yml exec web php scripts/seed-cursus.php`
- It links the demo user as creator and creates a session this week so you can browse the Cursus tools.

Troubleshooting Cheatsheet
--------------------------

- ERR_EMPTY_RESPONSE on 8088:
  - Apache failed early (most likely SSL vhost). Rebuild and check for duplicate `Listen 443` lines.
- Browser blank page:
  - Assets not loading from the right origin. Ensure `APP_URL=http://localhost:8088` and use static assets.
  - Disable extensions / Brave Shields; hard reload with cache disabled.
- DB wait loop:
  - Confirm `claroline-db` is up; avoid TLS in dev (`--skip-ssl`).
- Build errors on public symlinks:
  - We ignore `public/data`, `public/packages`, `public/bundles` in `.dockerignore`.

Stopping and Cleaning Up
------------------------

- Stop containers: `docker compose -f docker-compose.dev.yml stop`
- Remove containers: `docker compose -f docker-compose.dev.yml down`
- Remove containers and volumes (including MySQL data):
  - `docker compose -f docker-compose.dev.yml down -v`
