# RDS Auth Bridge

A WordPress plugin that authenticates users against an **external PostgreSQL database** (e.g. AWS RDS) instead of the native `wp_users` table — for migrations where the user store lives in a separate, pre-existing system.

## How it works

```
WordPress login ──► authenticate filter ──► Authenticator ──► ExternalUserRepository ──PDO/SSL──► PostgreSQL (RDS)
                          (WpAuthFilter)        (WP-free core)                                      clients table
```

- Hooks the `authenticate` filter at priority 10 (before WordPress's own check).
- Looks the email up in the external `clients` table with a prepared statement.
- Verifies the password with `password_verify()` against the stored bcrypt hash.
- On success, creates/syncs a matching `wp_users` row so WordPress manages the session.
- Unknown emails pass through to WordPress's normal login (so `admin` still works).
- If the external DB is unreachable, fails gracefully with a clear "service unavailable" message. When it authoritatively rejects (or can't reach RDS for) a user that belongs to the external directory, it removes WordPress's default password handlers for that login attempt so its specific error message stands.

## Configuration (environment variables only)

| Variable | Required | Default |
|---|---|---|
| `RDS_DB_HOST` | yes | — |
| `RDS_DB_NAME` | yes | — |
| `RDS_DB_USER` | yes | — |
| `RDS_DB_PASSWORD` | yes | — |
| `RDS_DB_PORT` | no | `5432` |
| `RDS_DB_SSLMODE` | no | `require` |

Secrets are read from the environment — never stored in the database or committed to code. The admin screen (Settings → RDS Auth Bridge) shows the active config with the password masked and offers a "Test connection" button.

## Local development

```bash
docker compose run --rm phpunit composer install
docker compose up -d
# WordPress: http://localhost:8930  (admin / admin)
# Demo login: maria@taxplatform.com / TaxPass123!
```

## Tests

```bash
docker compose up -d rds
docker compose run --rm phpunit vendor/bin/phpunit
```

- **Unit** (`tests/Unit`) — WP-free core logic, no DB required.
- **Integration** (`tests/Integration`) — runs against the Docker PostgreSQL.

## Security

Prepared statements, SSL-enforced connections (`sslmode=require` by default), secrets in env vars, capability checks and nonces on the admin page. The plugin authenticates against RDS; the WordPress password is random and unused.

## Scaling & known considerations

- **One connection per login attempt.** The plugin opens a fresh PDO connection to RDS on each login attempt. Because authentication only runs at login (not on every page load), this is fine at typical scale. Under very high login volume, front the database with a connection pooler (PgBouncer / RDS Proxy) or introduce a persistent/shared PDO.
- **`user_login` is the email.** Synced users use their email as `user_login`. WordPress's `wp_users.user_login` column is `varchar(60)`; emails longer than 60 characters (very rare) would need a different mapping.
- **Authoritative for external users only.** Login attempts for emails not present in the external directory pass through untouched, so local WordPress accounts (e.g. `admin`) keep working normally.
