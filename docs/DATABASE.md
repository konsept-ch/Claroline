# Database Notes

This page complements `README-SETUP.md` with DB-focused instructions. Claroline targets MySQL 8+ (or MariaDB 10.6+) and uses UTF-8MB4 everywhere.

## Default credentials

| Variable | Default value | Location |
| -------- | ------------- | -------- |
| `DB_HOST` | `dgcs-claroline-db` | `docker-compose.dev.yml` |
| `DB_PORT` | `3306` inside the container (`3307` exposed on the host) | Compose |
| `DB_NAME` | `claroline` | Compose, `bin/configure` |
| `DB_USER` | `claroline` | Compose |
| `DB_PASSWORD` | `claroline` | Compose |

`bin/configure` pulls those values and generates `config/parameters.yml`. You can override any of them via environment variables or by editing the generated file before restarting the web container.

## Charset & collation

The Docker image sets `utf8mb4`/`utf8mb4_unicode_ci` through `.docker.dev/mysql/my.cnf`. If you connect to an external database make sure the server uses the same charset; otherwise long indexes created by Doctrine migrations may fail.

## Networking tips

- The MySQL container exposes `3307` on the host. Pick another port by editing the `db` service in `docker-compose.dev.yml` if you already run MySQL locally.
- The entrypoint uses `mysqladmin ping --protocol=TCP` with retry/backoff. When running the stack on flaky networks increase `DB_WAIT_TIMEOUT` (seconds) to avoid premature exits.

## Seeding data

Claroline does not ship heavy fixtures by default, so two helper scripts were added:

1. `scripts/seed-cursus.php`: boots the Symfony kernel and inserts demo courses/sessions/events. Options:
   - `--courses=3` – number of courses to create (default: 2)
   - `--sessions=2` – sessions per course (default: 2)
   - `--events=2` – events per session (default: 2)
   - `--prefix=DEMO` – code prefix used for generated entities
   - `--reset` – delete previous demo entities that match the prefix
   - `--env=dev` – Symfony environment (defaults to `APP_ENV`)
2. `scripts/seed-dev.sh`: thin bash wrapper that forwards CLI args to the PHP script while preserving `APP_ENV`.

Example run from the host:

```bash
docker exec -it dgcs-claroline-web scripts/seed-dev.sh --courses=4 --sessions=2 --events=1 --reset
```

The script prints every entity it inserts so you can copy/paste the generated codes into the UI.

## Backups

The MySQL data lives outside the Claroline directory (`../mysql`). You can snapshot that folder or use standard tools:

```bash
docker exec dgcs-claroline-db mysqldump -u claroline -pclaroline claroline > dump.sql
```

Restoring is just as easy:

```bash
docker exec -i dgcs-claroline-db mysql -u claroline -pclaroline claroline < dump.sql
```

## Troubleshooting

- **Access denied** – verify `config/parameters.yml` matches the credentials defined in compose. Regenerate it with `php bin/configure --default` when in doubt.
- **Too many open files on Windows** – keep the DB directory outside `C:\Users` when using WSL2 file sharing for better performance.
- **Stuck migrations** – run `docker exec -it dgcs-claroline-web php bin/console claroline:update -vvv` manually to inspect errors before restarting the web container.
