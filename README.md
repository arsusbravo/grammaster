# GramMaster

Dutch grammar practice app for students from VMBO up to university.
Monorepo: Laravel JSON API + Filament admin (`/api`), Vue 3 SPA (`/app`), server deploy files (`/deploy`).

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

## Deploying to the development server (grammaster.arsus.it, Plesk, no Docker)

The server (CentOS 7 + Plesk) runs the app natively: Plesk's nginx (proxy) + Apache + PHP 8.3 FPM + MySQL. No Docker anywhere.

**One time, in Plesk**
1. Add the domain `grammaster.arsus.it` (DNS A record to the VPS). Hosting settings: PHP 8.3 as "FPM application served by Apache", document root `.../api/public`. Plesk's nginx stays in front as a proxy (the default), and Apache reads `api/public/.htaccess`.
2. SSL/TLS: issue Let's Encrypt and turn on "Permanent SEO-safe 301 redirect from HTTP to HTTPS".
3. Databases: create a database and user. Note the credentials.
4. Apache & nginx Settings: untick "Serve static files directly by nginx" (otherwise the cache headers in `.htaccess` do not apply to the student app's files). No nginx directives are needed.

How the student app is served: its files are static in `api/public/student`; any other `/student/...` address (deep links like `/student/examens`) falls through `.htaccess` to Laravel, whose route returns the app's `index.html`. `.htaccess` also sets the cache headers: hashed files cached for a year, and `index.html`, `sw.js`, `registerSW.js` and the manifest `no-cache`, so students always get a new version of the app.

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
