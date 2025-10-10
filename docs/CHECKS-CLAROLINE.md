Claroline Sanity Check & 500 Error Investigation
===============================================

Purpose
-------
Use this checklist whenever `http://localhost:8088` returns `500 Internal Server Error` or when you need to validate that the Claroline stack is healthy.

Immediate Snapshot
------------------
1. Confirm services are up:
   - `docker compose -f Claroline/docker-compose.dev.yml ps`
   - Expected: `web`, `db`, and `mailhog` containers in `Up` status.
2. Capture container logs:
   - `docker compose -f Claroline/docker-compose.dev.yml logs --tail=200 web`
   - `docker compose -f Claroline/docker-compose.dev.yml logs --tail=200 db`
3. Note current env toggles (from `Claroline/.env.local`): `APP_ENV`, `APP_DEBUG`, `DATABASE_URL`.

HTTP 500 Triage
---------------
Follow these steps in order. Stop once you locate the root cause.

1. Enable verbose output (dev only):
   - Ensure `APP_DEBUG=1` in `Claroline/.env.local`.
   - Reload the page to surface the Symfony exception page (instead of a generic 500).
2. Inspect Symfony logs (bind-mounted to your host):
   - `Get-Content Claroline/var/log/dev.log -Tail 100` (PowerShell)
   - Look for the latest stack trace or uncaught exception.
3. Inspect Apache/PHP logs inside the container:
   - `docker compose -f Claroline/docker-compose.dev.yml exec web tail -n 100 /var/log/apache2/error.log`
   - `docker compose -f Claroline/docker-compose.dev.yml exec web tail -n 100 /var/log/apache2/access.log`
4. Check PHP runtime health:
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php -m` (confirm required extensions)
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php -i | Select-String memory_limit` (PowerShell) if memory errors appear.
5. Verify Composer dependencies:
   - `docker compose -f Claroline/docker-compose.dev.yml exec web composer check-platform-reqs`
   - `docker compose -f Claroline/docker-compose.dev.yml exec web composer validate`
6. Warm caches and rebuild assets:
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php bin/console cache:clear --env=dev`
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php bin/console cache:warmup --env=dev`
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php bin/console claroline:update --env=dev -vvv` (if `SKIP_REBUILD=0` or after changing bundles)
7. Restart Claroline services:
   - `docker compose -f Claroline/docker-compose.dev.yml restart web`
   - If the container fails to stay up, re-run with `--logs` to diagnose startup scripts.
8. Confirm filesystem permissions (rare on Windows but quick to verify):
   - `docker compose -f Claroline/docker-compose.dev.yml exec web ls -l var files config`
   - Expect writable (`drwxrwxrwx`). If not, rerun the container to trigger the entrypoint permissions fix.

Database Checks
---------------
1. Confirm the DB container responds:
   - `docker compose -f Claroline/docker-compose.dev.yml exec db mysqladmin ping -h localhost`
2. Run a basic query to verify schema ownership:
   - `docker compose -f Claroline/docker-compose.dev.yml exec db mysql -u claroline -pclaroline -e "USE claroline; SHOW TABLES;"`
3. Run Symfony health checks:
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php bin/console doctrine:mapping:info`
   - `docker compose -f Claroline/docker-compose.dev.yml exec web php bin/console doctrine:query:sql "SELECT COUNT(*) FROM claro_user"`
4. If migrations or schema look broken, rebuild from scratch (destructive):
   - Stop stack: `docker compose -f Claroline/docker-compose.dev.yml down`
   - Remove volumes (drops DB): `docker compose -f Claroline/docker-compose.dev.yml down -v`
   - Re-create: `docker compose -f Claroline/docker-compose.dev.yml up -d --build`

Configuration Sanity
--------------------
1. Verify `Claroline/.env.local` contains the Docker values:
   - `DATABASE_URL=mysql://claroline:claroline@claroline-db:3306/claroline?charset=utf8mb4`
   - `APP_ENV=dev`, `APP_DEBUG=1` while debugging.
2. Confirm `files/installed` exists. If missing, the entrypoint will re-run `claroline:install` on startup.
3. If you changed the host port or APP URL, ensure they align:
   - Compose publishes `8088 -> 80`. Update `.env.local` or Apache config only if you alter the mapping.
4. Mailhog and optional services do not block web boot, but check their logs if mail-related code fails.

When Opening a Bug Ticket
-------------------------
Capture the following so the team can help quickly:
- Command output from the "Immediate Snapshot" section.
- Last 100 lines of `Claroline/var/log/dev.log`.
- Apache `error.log` excerpt covering the failure.
- Any recent code changes or overrides (e.g., new bundles, custom themes).
- Exact timestamp and URL path that returned 500.

Last Resort Actions
-------------------
Only use these if earlier steps fail:
1. Purge caches manually: delete `Claroline/var/cache/*` (container will recreate them).
2. Reinstall Composer dependencies from scratch:
   - `docker compose -f Claroline/docker-compose.dev.yml exec web rm -rf vendor/*`
   - `docker compose -f Claroline/docker-compose.dev.yml exec web composer install --no-scripts`
3. Rebuild container image:
   - `docker compose -f Claroline/docker-compose.dev.yml build --no-cache web`
4. Destroy and recreate the MySQL volume (drops all data):
   - Delete `../mysql` host folder after shutting down containers, then rerun `docker compose up --build`.

References
----------
- Repo README for dev stack overview (`README.md`).
- Claroline detailed setup guide (`Claroline/README-SETUP.md`).
- Docker entrypoint script for startup tasks (`Claroline/.docker.dev/web/entrypoint.sh`).
