# GramMaster

Dutch grammar practice app for students from VMBO up to university.
Monorepo: Laravel JSON API + Filament admin (`/api`), Vue 3 SPA (`/app`), server deploy files (`/deploy`).

Local development runs on [Laravel Herd](https://herd.laravel.com) (PHP 8.3, nginx, MySQL, Node). No Docker.

## Quick start

You need Herd with PHP 8.3, its MySQL service and Node (22 or newer).

```bash
# 1. API: link it as a Herd site (http://gramaster.test) and set it up
cd api
herd link gramaster
cp .env.example .env                    # DB user root, no password (Herd's MySQL)
composer install
php artisan key:generate
mysql -uroot -h127.0.0.1 -e 'create database grammatica; create database grammatica_test'
php artisan migrate --seed

# 2. Student app (second terminal): builds into api/public/student and rebuilds on every save
cd app
npm install
npm run watch
```

| What                      | URL                                    | Log in with               |
| ------------------------- | -------------------------------------- | ------------------------- |
| **Landing page**          | http://gramaster.test                  |                           |
| **Student app**           | http://gramaster.test/student          | test@arsus.nl / test1234  |
| **Admin**                 | http://gramaster.test/admin            | info@arsus.nl / admin1234 |
| API                       | http://gramaster.test/api/v1/ping      |                           |
| Design review (dev only)  | http://localhost:5174/student/design (needs `npm run dev`) |                           |

Everything is on one address, like on the server: Herd serves the built student app (static files in
`api/public/student`), the admin and the API. `npm run watch` rebuilds when you save a file; reload the page to see it.
For hot reload run `npm run dev` instead and open http://localhost:5174/student/ (Vite forwards `/api` to
`gramaster.test`; after `herd secure gramaster` start it with `VITE_DEV_API_TARGET=https://gramaster.test npm run dev`).

Mail is written to `api/storage/logs` (`MAIL_MAILER=log`).

## Default accounts (local only)

`php artisan db:seed` creates (not in production):

| Role    | E-mail          | Password   |
| ------- | --------------- | ---------- |
| Admin   | info@arsus.nl   | admin1234  |
| Student | test@arsus.nl   | test1234   |

The admin manages questions, topics, levels, exams, students and administrators.

## API

All endpoints live under `/api/v1` (list in [CLAUDE.md](CLAUDE.md#api-phase-4-all-under-apiv1)).
Quick try (after seeding):

```bash
TOKEN=$(curl -s -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"test@arsus.nl","password":"test1234"}' http://gramaster.test/api/v1/auth/login | jq -r .data.token)
curl -s -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN" \
  'http://gramaster.test/api/v1/questions?level=vmbo&limit=3'
```

## Commands

Run these in `api/`:

| Command                                  | What it does                              |
| ---------------------------------------- | ----------------------------------------- |
| `php artisan migrate`                    | Run migrations                            |
| `php artisan db:seed`                    | Run seeders                               |
| `php artisan migrate:fresh --seed`       | Drop everything, migrate and seed         |
| `php artisan test`                       | Pest (API and admin tests)                |
| `./vendor/bin/pint`                      | Format PHP (`--test` only checks)         |

And in `app/`:

| Command                                  | What it does                              |
| ---------------------------------------- | ----------------------------------------- |
| `npm run watch`                          | Build into `api/public/student` and rebuild on save (http://gramaster.test/student) |
| `npm run build:herd`                     | The same, once                            |
| `npm run dev`                            | Optional dev server with hot reload (port 5174) |
| `npm test`                               | Vitest                                    |
| `npm run lint` / `npm run typecheck`     | ESLint / vue-tsc                          |
| `npm run format`                         | Prettier                                  |
| `npm run build`                          | Production build (static, `app/dist`)     |
| `npm run build && npm run preview`       | Production build on port 5174, with the service worker (offline mode, install) |
| `npm run assets`                         | Regenerate favicons, PWA icons and splash images from the logo |

## Notes

- Tests use the separate `grammatica_test` database, never your dev data.
- The service worker only exists in a build and needs https. Try offline mode on https://gramaster.test/student/
  (`herd secure gramaster` once) or with `npm run build && npm run preview` (http://localhost:5174/student/).

## Deploying to the development server (grammaster.arsus.it, Plesk, no Docker)

The server (CentOS 7 + Plesk) runs the app natively: Plesk's nginx + PHP 8.3 FPM + MySQL. No Docker anywhere.

**One time, in Plesk**
1. Add the domain `grammaster.arsus.it` (DNS A record to the VPS). Hosting settings: PHP 8.3, "FPM application served by nginx", Proxy mode off, document root `.../api/public`.
2. SSL/TLS: issue Let's Encrypt and turn on "Permanent SEO-safe 301 redirect from HTTP to HTTPS".
3. Databases: create a database and user. Note the credentials.
4. Apache & nginx Settings > Additional nginx directives: paste the block below. If Plesk already adds a `location /` with `try_files` (Laravel Toolkit does), leave out the last `location /` block.

```nginx
# Student app: static files built into api/public/student
location = /student {
    return 301 /student/;
}

location ^~ /student/ {
    # Unknown paths (e.g. /student/examens/poging/7) belong to the Vue router.
    try_files $uri $uri/ /student/index.html;

    # Hashed files never change: cache for a year.
    location ~* ^/student/assets/ {
        add_header Cache-Control "public, max-age=31536000, immutable";
        try_files $uri =404;
    }

    # The service worker, its helpers, the manifest and the page itself must always be re-checked,
    # otherwise users stay on an old version of the app.
    location ~* ^/student/(index\.html|sw\.js|registerSW\.js|manifest\.webmanifest|workbox-.*\.js)$ {
        add_header Cache-Control "no-cache";
        try_files $uri =404;
    }
}

# Everything else is Laravel (API, admin, Livewire).
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\.(?!well-known).* {
    deny all;
}
```


**One time, over SSH** (clone with a GitHub deploy key, read-only)
```
git clone git@github.com:arsusbravo/grammaster.git && cd grammaster/api
cp .env.production.example .env        # fill in DB_* (APP_KEY comes next)
export PHP_BIN=/opt/plesk/php/8.3/bin/php
$PHP_BIN /usr/lib64/plesk-9.0/composer.phar install --no-dev --optimize-autoloader
$PHP_BIN artisan key:generate
$PHP_BIN artisan migrate --force
$PHP_BIN artisan db:seed --force       # levels, topics, questions, exams (the demo accounts are skipped in production)
$PHP_BIN artisan app:create-admin you@example.nl   # asks for a name and password
$PHP_BIN artisan optimize
```
`storage/` and `bootstrap/cache/` must be writable by the site's system user (run the commands as that user, or `chown -R`).
The repository can live outside the web root; point the Plesk document root at its `api/public`.

**Every update**
- Server (over SSH, in the repository): `git pull --ff-only`, `cd api`, `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan optimize` (with `PHP_BIN` and the Plesk Composer path as above).
- Student app (needs Node, so it is built on your Mac): `cd app && VITE_BASE=/student/ VITE_API_BASE_URL=/api/v1 npm run build`, then `rsync -az --delete dist/ user@host:<path>/api/public/student/`. CI also uploads the build as the `student-dist` artifact.

**CI** (`.github/workflows/ci.yml`): Pint + Pest on PHP 8.3 with MySQL 9.7, and lint, typecheck, Vitest and build for the app, on every push and pull request.

Checks afterwards: `/up`, `/api/v1/levels`, `/student/` (service worker needs HTTPS), `/admin` login.
