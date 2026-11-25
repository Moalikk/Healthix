#!/bin/sh
# Dijalankan setiap container start: siapkan database demo lalu jalankan server.
set -e
cd /app/backend

[ -f .env ] || cp .env.example .env

export APP_ENV=production
export APP_DEBUG=false
export LOG_LEVEL=warning
export DB_CONNECTION=sqlite
export DB_DATABASE=/tmp/quickcare.sqlite
export BCRYPT_ROUNDS=10
export PHP_CLI_SERVER_WORKERS=4

touch "$DB_DATABASE"
php artisan key:generate --force
# Data demo dibuat ulang setiap start (disk gratis bersifat sementara)
php artisan migrate:fresh --seed --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"