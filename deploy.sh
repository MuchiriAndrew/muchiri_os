#!/usr/bin/env bash
set -euo pipefail

# NOTE: this script assumes `git pull` has ALREADY happened in the caller
# (see .github/workflows/deploy.yml). Do not add `git pull` here — bash
# buffers the script it's executing, so if this file rewrites itself via a
# pull mid-run, bash keeps running the stale in-memory copy while the
# working directory has already moved on. That mismatch is exactly what
# broke the first Filament deploy (npm steps ran against a package.json
# that pull had already deleted).

cd /var/www/muchiri_os

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
sudo systemctl reload php8.3-fpm
