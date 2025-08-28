Claroline — Local Setup & Requirements
=====================================

This document summarizes installation requirements and setup paths found in this repository, for both Docker and from‑source workflows.

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
- Ports: `80` (Apache), `8080` (webpack-dev-server), `3306` (MySQL)
  - You can change mappings in `docker-compose.dev.yml:1` if ports are busy
- Persistent MySQL data: `../mysql` relative to the repo root must be writable
- Start stack:
  - `docker compose -f docker-compose.dev.yml up --build`
  - First boot installs dependencies, waits for DB, installs Claroline, starts webpack dev server, then Apache
- Default environment (web service):
  - DB: `DB_HOST=claroline-db`, `DB_NAME=claroline`, `DB_USER=claroline`, `DB_PASSWORD=claroline`
  - Platform branding: `PLATFORM_NAME`, `PLATFORM_SUPPORT_EMAIL`
  - Optional admin auto‑creation (dev only): set `ADMIN_*` variables
  - Reference: `docker-compose.dev.yml:1`, `.docker.dev/web/entrypoint.sh:1`
  - App URL used by the frontend: set `APP_URL=http://localhost:8088` (already configured).
  - Ports:
    - `8088 -> Apache (HTTP)`, `8080 -> webpack-dev-server (optional)`, `8443 -> Apache (HTTPS self‑signed)`

From Source (Without Docker)
----------------------------

1) Install dependencies
- `composer install --no-dev --optimize-autoloader`
- `npm install` (or `npm install --legacy-peer-deps` with npm ≥ 7)

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

Updating an Existing Install
----------------------------

- With Docker (dev): container auto‑runs updates on restart when `files/installed` exists
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
- npm peer dependency conflicts on npm ≥ 7: use `--legacy-peer-deps`
- Port conflicts on 80/8080: adjust Compose port mappings if IIS or other services are bound
- MySQL TLS with self‑signed certs: dev flow disables TLS or falls back automatically.
- Apache SSL vhost: remove duplicate `Listen 443` lines; our image strips them at runtime.
- Public symlinks (e.g., `public/bundles`): ignored in `.dockerignore` to avoid build errors.
- Webpack assets blank page: ensure `APP_URL` includes the port (e.g., `http://localhost:8088`).
- Very slow page loads on Windows:
  - Set `APP_DEBUG=0` to disable the Symfony toolbar.
  - Prefer serving static assets: set `WEBPACK_DEV_SERVER=0` (default here).
  - Keep the repo under WSL2 home for faster bind‑mounts.
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

Front‑end assets (dev vs static)
--------------------------------

- Static (recommended on Windows): `WEBPACK_DEV_SERVER=0` → Apache serves `public/dist` (fast, predictable).
- Dev‑server (hot reload): `WEBPACK_DEV_SERVER=1` and port `8080:8080`; we also enable `writeToDisk`.

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
