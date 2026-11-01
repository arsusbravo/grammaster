# Grammatica

Dutch grammar practice app for students from VMBO up to university.
Monorepo: Laravel JSON API (`/api`), Filament admin, Vue 3 SPA (`/app`), Docker environment (`/docker`).

Everything runs in containers. You only need Docker (Compose v2).

## Quick start

```bash
cp .env.example .env     # set UID/GID to `id -u` / `id -g` if they differ
make up
make migrate
make seed
```

Everything runs on **one address**, http://localhost:8088 (change `NGINX_PORT` in `.env`):

| What                      | URL                                    | Log in with                         |
| ------------------------- | -------------------------------------- | ----------------------------------- |
| **Student app**           | http://localhost:8088/student          | test@arsus.nl / test1234            |
| **Admin**                 | http://localhost:8088/admin            | info@arsus.nl / admin1234           |
| API                       | http://localhost:8088/api/v1/ping      |                                     |
| Design review (dev only)  | http://localhost:8088/student/design   |                                     |
| Mailpit                   | http://localhost:8025                  |                                     |
| MySQL (from your machine) | 127.0.0.1:3307 (see `.env`)            |                                     |

`http://localhost:8088/` redirects to the student app. nginx sends `/student/` to the Vite dev server (hot reload
works) and everything else to Laravel. The app calls the API on the same address (`/api/v1`), so there is no CORS.

Ports and credentials come from the root `.env`. The first `make up` runs `composer install`,
creates `api/.env` from `api/.env.example` and generates the app key. The `node` container
runs `npm install` on first start.

## Default accounts (local only)

`make seed` creates (not in production):

| Role    | E-mail          | Password   |
| ------- | --------------- | ---------- |
| Admin   | info@arsus.nl   | admin1234  |
| Student | test@arsus.nl   | test1234   |

The admin (http://localhost:8088/admin) manages questions, topics, levels, exams, students and administrators.

## API

All endpoints live under `/api/v1` (list in [CLAUDE.md](CLAUDE.md#api-phase-4-all-under-apiv1)).
Quick try (after `make seed`):

```bash
TOKEN=$(curl -s -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"test@arsus.nl","password":"test1234"}' http://localhost:8088/api/v1/auth/login | jq -r .data.token)
curl -s -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN" \
  'http://localhost:8088/api/v1/questions?level=vmbo&limit=3'
```

## Commands

| Command                          | What it does                                       |
| -------------------------------- | -------------------------------------------------- |
| `make up` / `make down`          | Start (build) / stop all containers                |
| `make migrate`                   | Run migrations                                     |
| `make seed`                      | Run seeders                                        |
| `make fresh`                     | Drop everything, migrate and seed                  |
| `make test`                      | Pest (API) and Vitest (SPA)                        |
| `make lint`                      | Pint check and ESLint                              |
| `make shell` / `make node-shell` | Shell in the php / node container                  |
| `make composer c="require x/y"`  | Run Composer in the php container                  |
| `make artisan c="make:model X"`  | Run Artisan                                        |
| `make npm c="install x"`         | Run npm in the node container                      |
| `make npm c="run assets"`         | Regenerate favicons, PWA icons, splash from the logo |
| `make preview`                    | Serve the production build (needed to try the service worker / offline mode) |
| `make dev`                        | Back to the Vite dev server (hot reload) |
| `make logs`                      | Follow container logs                              |

Format code: `docker compose exec php ./vendor/bin/pint` and `make npm c="run format"`.

## Notes

- Containers run as your host UID/GID, so files in `api/` and `app/` are never root-owned.
- Tests use the separate `grammatica_test` database (created by `docker/mysql/init.sql` on a fresh
  volume). On an existing volume, run that file once through the mysql container.
- Production build of the SPA: `make npm c="run build"` (static output in `app/dist`).
