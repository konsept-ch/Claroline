# Claroline Local Setup

This document focuses on the Docker workflow that now ships with tuned PHP 8.2 images, opcache enabled and a slimmer bind-mount strategy for Windows/macOS developers. Keep it close the first time you bootstrap the stack.

## Prerequisites

- Docker Desktop **4.27+** with 6 GB RAM dedicated to Linux containers.
- Node.js **16.x** on the host if you plan to run webpack outside Docker.
- Access to the Claroline repository (clone this project and its sibling frontends if needed).

### First-time bootstrap

1. Copy `.env.dist` to `.env` (or export the variables listed inside).
2. Build and start the dev stack:
   ```bash
   docker compose -f docker-compose.dev.yml up --build
   ```
3. Wait for the `dgcs-claroline-web` logs to print `Starting Apache`. The entrypoint now handles:
   - `php bin/configure`, `composer install --no-scripts`, `composer bundles` and `composer dump-autoload -o`.
   - Weekly GeoIP refresh (set `GEOIP_DISABLE=1` to skip).
   - Optional `claroline:update` skip when `SKIP_REBUILD=1`.
   - Switching between `webpack-dev-server` and a one-off build by toggling `WEBPACK_DEV_SERVER`.
4. Visit the mapped ports:
   - HTTP: [http://localhost:8088](http://localhost:8088)
   - HTTPS: [https://localhost:8443](https://localhost:8443)
   - MySQL: `localhost:3307` (user/pass `claroline` by default)
   - Mailhog UI: [http://localhost:8025](http://localhost:8025)

## Volumes & caching

The compose file overlays named volumes on top of the project bind mount:

| Mount | Path inside container | Why |
| ----- | --------------------- | --- |
| `claroline_vendor` | `/var/www/html/claroline/vendor` | Keep Composer deps inside Linux FS for huge speed ups on Windows/macOS. |
| `claroline_cache` | `/var/www/html/claroline/var/cache` | Symfony cache stays warm between container restarts. |
| `claroline_log` | `/var/www/html/claroline/var/log` | Avoid massive log churn on bind mounts. |
| `claroline_geoip` | `/var/www/html/claroline/var/geoip` | Persist the weekly GeoIP download. |

Because vendor and cache live in named volumes, you can nuke them safely when dependencies change:

```bash
docker volume rm claroline_claroline_vendor claroline_claroline_cache
```

## Runtime knobs

All knobs live in `docker-compose.dev.yml` so you can override them per user/machine with `docker-compose.override.yml`:

| Variable | Default | Description |
| -------- | ------- | ----------- |
| `APP_DEBUG` | `0` | Leave Symfony in prod-ish mode for faster boot, flip to `1` while debugging. |
| `NODE_OPTIONS` | `--max-old-space-size=4096` | Prevent out-of-memory errors during webpack builds. |
| `WEBPACK_DEV_SERVER` | `0` | When `1`, launches `webpack-dev-server` in watch mode; otherwise runs a single `npm run webpack`. |
| `SKIP_REBUILD` | `0` | When `1`, skips `claroline:update` if `files/installed` already exists (useful for quick front-end tweaks). |
| `GEOIP_DISABLE` | `0` | Disable GeoIP downloads entirely when the license key isn’t available. |
| `MAILER_DSN` | `smtp://mailhog:1025` | Routes every Symfony mailer call to Mailhog. |

You can also inject `APP_URL`, `PLATFORM_NAME`, `PLATFORM_SUPPORT_EMAIL` and the `ADMIN_*` variables to pre-provision a super admin account during the first install.

## Common tasks

| Task | Command |
| ---- | ------- |
| Tail web logs | `docker logs -f dgcs-claroline-web` |
| Drop web container but keep DB/volumes | `docker compose -f docker-compose.dev.yml up --build --force-recreate web` |
| Run a Symfony command | `docker exec -it dgcs-claroline-web php bin/console some:command` |
| Rebuild assets without restarting | `docker exec -it dgcs-claroline-web npm run webpack` |
| Seed demo cursus data | `docker exec -it dgcs-claroline-web scripts/seed-dev.sh --courses=2 --sessions=2 --events=2` |

## Data seeding helpers

Two helper scripts live inside `scripts/`:

- `scripts/seed-dev.sh`: wraps `scripts/seed-cursus.php` and ensures the current `APP_ENV` is forwarded. Great for quickly creating demo curricula while testing the new Docker flow.
- `scripts/seed-cursus.php`: boots the Symfony kernel, purges previous demo trainings (when `--reset` is provided) and creates demo courses, sessions and events with deterministic codes (`DEMO-C01`, `DEMO-C01-S01`, etc.).

Example:

```bash
docker exec -it dgcs-claroline-web scripts/seed-dev.sh --courses=3 --sessions=2 --events=2 --reset
```

## Troubleshooting

- **Composer install keeps running** – blow away the `claroline_claroline_vendor` volume so dependencies can be reinstalled cleanly.
- **Waiting for MySQL** – ensure port `3307` is free on Windows and no existing MySQL service conflicts with the compose service.
- **GeoIP download fails** – set `MAXMIND_LICENSE_KEY` in your `.env` or temporarily export `GEOIP_DISABLE=1`.
- **Need hot reload** – set `WEBPACK_DEV_SERVER=1` and forward port `8080:8080` if you want to reach the dev server directly. Apache will still serve `/dist` thanks to `devServer.writeToDisk=true`.
- **Need to skip rebuilds** – export `SKIP_REBUILD=1` to only run webpack and leave the PHP schema untouched (ideal for CSS/JS iterations).

## On-host development (optional)

You can still use the host Node stack while keeping PHP inside Docker:

1. Install dependencies locally (`npm install`).
2. Set `WEBPACK_DEV_SERVER=1` inside `.env.local` or an override file.
3. Run the dev server on the host (`npm run webpack:dev`) and let Apache read bundles from `public/dist`.

Happy hacking! Contributions to this setup doc are welcome—just keep it short and actionable.
