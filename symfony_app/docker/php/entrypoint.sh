#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [ ! -f var/.fixtures_loaded ]; then
  php bin/console doctrine:fixtures:load --no-interaction
  touch var/.fixtures_loaded
fi

exec "$@"
