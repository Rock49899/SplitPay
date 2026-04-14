# --- IMAGE APP (PHP + Supervisor) ---
FROM php:8.4-fpm-alpine AS app

WORKDIR /var/www

# Dépendances système + PHP extensions + outils runtime (netcat pour wait DB)
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    $PHPIZE_DEPS \
    supervisor \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie du code source, y compris les assets Vite déjà compilés dans public/build
COPY . .

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction \
    && php artisan package:discover --ansi

# Runtime scripts/config
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/supervisor/worker.conf /etc/supervisor/conf.d/worker.conf
RUN chmod +x /usr/local/bin/entrypoint.sh

# Dossiers écriture Laravel (le volume storage sera re-fixé au runtime)
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]


# --- IMAGE NGINX ---
FROM nginx:alpine AS nginx

WORKDIR /var/www

# Code applicatif + assets compilés depuis l'image app
COPY --from=app /var/www /var/www

# Config nginx projet
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf