#!/usr/bin/env bash
# Runs ON THE SERVER, in the repository, after the new code has been pulled. The GitHub "Deploy" workflow calls it
# after every push to main; you can also run it by hand: ./deploy.sh
#
# The student app is not built here (the server has no recent Node): its build is committed in api/public/student.
set -euo pipefail

cd "$(dirname "$0")/api"

PHP_BIN="${PHP_BIN:-/opt/plesk/php/8.3/bin/php}"
COMPOSER_PHAR="${COMPOSER_PHAR:-/usr/lib64/plesk-9.0/composer.phar}"

if [ -f "$COMPOSER_PHAR" ]; then
    COMPOSER="$PHP_BIN $COMPOSER_PHAR"
elif command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
else
    echo "Composer not found. Set COMPOSER_PHAR to the path of composer.phar." >&2
    exit 1
fi

echo "==> Dependencies"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "==> Database migrations"
$PHP_BIN artisan migrate --force

echo "==> Caches"
$PHP_BIN artisan optimize

echo "==> Done"
