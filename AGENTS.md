# AGENTS — SmartBK

## Stack
- Pure PHP 8.2 (no framework) + Apache + MySQL 8.0. DB helpers in `config/db.php` (`db_query`/`db_fetch` with `?` placeholders + auto type binding). App config in `config/app.php`.
- Session auth + CSRF (`includes/session.php`, `includes/auth.php`, `includes/csrf.php`). Google OAuth via `google/apiclient` (`includes/google_oauth.php`, `auth/google_callback.php`).
- React dashboard is separate Vite app in `orbit/` (React 19 + Tailwind v4). Built output mounted into `dashboard.php` — not part of PHP autoload.

## Run & Commands
```bash
cp .env.example .env  # edit DB_PASS, MYSQL_ROOT_PASSWORD, Google OAuth; required before first up
docker compose up -d --build  # app http://<host>:9000  (inside container also 9000, not 80)
docker compose logs -f app
docker compose down -v  # only way to re-import sql/smart_bk.sql (volume-gated init)

# native (XAMPP) — no Docker
mysql -u root -p < sql/smart_bk.sql
# then set env vars DB_HOST/DB_NAME/DB_USER/DB_PASS or edit config/db.php defaults

composer install
composer test          # phpunit ^10 — but repo has no phpunit.xml and no tests committed
php -l path/to/file.php  # only lint available (composer lint is just php -l, no phpcs/eslint)
```

Orbit (separate):
```bash
cd orbit && npm install
npm run dev              # Vite dev at /orbit/
npm run build:dash       # DASH=1 → dist/dash/dashboard.js + chunks (what dashboard.php loads)
npm run build            # normal orbit build (not used by PHP)
```

## Structure
- `index.php`→`login.php`→`dashboard.php` — entry flow; `logout.php`, `register.php`, `pending_approval.php`.
- `api/` — session-REST, each file is an endpoint; helpers in `api/index.php` (`api_response`/`api_error`/`api_success`, `require_auth`/`require_role`, `get_json_input`). See `api/README.md`.
- `includes/` — `auth.php` (guards + `is_wali_kelas`/`can_see_all_data`/`can_approve_users`), `session.php` (lockout 5 fails/15 min), `functions.php`, `upload.php` thin shim → `src/Uploader.php` (single upload base).
- `src/` — `Uploader.php` (centralized upload/delete, fixes `../../` bug, basename guard), `Validators.php` (shared `validateSiswa`/`validateJenisPelanggaran` + enums).
- `siswa/`, `user/`, `kelas/`, `pelanggaran/`, `buku_tamu/`, `konsultasi/` — PHP page CRUD (not API).
- `sql/smart_bk.sql` — fresh install (auto-mounted on first `docker compose up`). `sql/migrations/` (v1.1.0–v1.6.2) are **not** auto-run — apply manually.
- `assets/uploads/` — bind-mounted + `.gitignore`d; subdirs `foto_siswa`, `bukti_pelanggaran`, `lampiran_konsultasi`, `kop` need `www-data` write.

## Conventions & Gotchas (verify before editing)
- **No framework routing**: `.htaccess` rewrites `*.php` extension away, proxies `/api/` through, sets CSP/headers. Apache needs `AllowOverride All` + `mod_rewrite,mod_headers` (Dockerfile enables both; `docker/000-default.conf` + `docker/ports.conf` force port 9000).
- **DB access**: always via `db_query('... WHERE id=?', [$id])` / `db_fetch(..., 'row')` / `db_last_id()`. No ORM, no PDO.
- **Auth pattern** for pages: `require_once __DIR__.'/../includes/auth.php'` (auto-redirects to `login.php` + CSRF check on POST/PUT/DELETE). For API: `require_auth()` / `require_role(['Admin','Guru BK'])` from `api/index.php`.
- **APP_BASE** auto-detects `/smartbk/` vs `/` in `config/app.php`; `OAUTH_REDIRECT_URI` defaults to `http://$host$APP_BASE/auth/google_callback.php` unless `GOOGLE_REDIRECT_URI` env set. `.env` loader is manual file parse, not vlucas.
- **Role scoping**: `Wali Kelas` must be filtered by `kelas_id` (`get_user_kelas_id()`); `can_see_all_data()` is only `Admin`+`Guru BK`. Forgetting the filter leaks data.
- **OAuth**: only `@belajar.id` / `@guru.smk.belajar.id` allowed; new Google users get `approval_status='pending'` → `pending_approval.php` until Admin/Guru BK approves.
- **CORS**: same-origin only in `api/index.php` (wildcard removed). No `*`; `api/.htaccess` does not set CORS.
- **Uploads**: check `assets/uploads/*` writability; `vendor/`, `.env` excluded in `.dockerignore`/`.gitignore`. Passwords in `.env` must avoid spaces/`#`/`$`/quotes (shell/MySQL parse issue noted in `.env.example`).
- **Orbit**: `vite.config.ts` uses `base: './'` when `DASH=1` — chunks resolve relative to `dashboard.js`. Changing base breaks dashboard.php loading. Alias `@` → `orbit/src`.
- **No CI/workflow, no pre-commit, no opencode.json** — do not assume lint/typecheck gates. Docs are canonical but prefer `composer.json`/`docker-compose.yml`/`Dockerfile` when docs conflict.
