#!/bin/sh
set -e

echo "Préparation de l'application..."

# Harmoniser les permissions runtime (volume storage)
mkdir -p /var/www/storage /var/www/bootstrap/cache /var/www/storage/logs
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true
chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache || true

# 1. Attendre que MySQL soit prêt
# (Utilise la variable d'env DB_HOST définie dans ton docker-compose)
until nc -z -v -w30 "$DB_HOST" "${DB_PORT:-3306}"; do
  echo "Attente de la base de données ($DB_HOST)..."
  sleep 5
done

if [ "${APP_RUN_INIT:-false}" = "true" ]; then
  # 2. Lancer les migrations
  echo "Exécution des migrations..."
  php artisan migrate --force

  # 3. Lancer uniquement les seeders essentiels de prod
  echo "Chargement des données initiales essentielles..."
  php artisan db:seed --class=RoleSeeder --force
  php artisan db:seed --class=PermissionSeeder --force
  php artisan db:seed --class=RolePermissionSeeder --force
  php artisan db:seed --class=PlatformAdminSeeder --force

  # 4. Optimisation du cache pour la prod
  echo "⚡ Optimisation du cache..."
  php artisan optimize:clear
  php artisan config:cache
  if [ "${APP_CACHE_ROUTES:-false}" = "true" ]; then
    php artisan route:cache
  fi
  php artisan view:cache
fi

echo "Application prête !"

# Lancer la commande passée au container (php-fpm ou supervisord)
exec "$@"