#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Laravel needs writable runtime paths in bind-mounted development setups.
mkdir -p storage/logs storage/framework bootstrap/cache
chmod -R 0777 storage bootstrap/cache || true
touch storage/logs/laravel.log || true
chmod 0666 storage/logs/laravel.log || true

# In local Docker setups we bind-mount the project root.
# That mount hides image-built dependencies, so install when missing.
# Use a lock inside the project path so all containers share it.
if [ ! -f vendor/autoload.php ]; then
  mkdir -p storage/framework
  lock_dir="storage/framework/.composer-install.lock"
  has_lock=0

  while [ ${has_lock} -eq 0 ]; do
    if mkdir "${lock_dir}" 2>/dev/null; then
      has_lock=1
      trap 'rmdir "${lock_dir}" 2>/dev/null || true' EXIT
    elif [ -f vendor/autoload.php ]; then
      break
    else
      sleep 2
    fi
  done

  if [ ${has_lock} -eq 1 ] && [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi

  if [ ${has_lock} -eq 1 ]; then
    rmdir "${lock_dir}" 2>/dev/null || true
    trap - EXIT
  fi
fi

if [ "${RUN_MIGRATIONS}" = "true" ]; then
  if ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --force >/dev/null 2>&1 || true
  fi

  if [ ! -f storage/.migrated ]; then
    php artisan migrate --force
    touch storage/.migrated
  fi

  # Remove legacy Scribe static docs path so `/docs` resolves to Laravel route.
  rm -rf public/docs

  if [ ! -f public/scribe/openapi.yaml ]; then
    php artisan scribe:generate --no-interaction
  fi
fi

exec "$@"
