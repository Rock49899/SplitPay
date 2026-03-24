FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www

# Dépendances système + extensions PHP requises
RUN apk add --no-cache \
			libpng-dev \
			libjpeg-turbo-dev \
			freetype-dev \
			libzip-dev \
			$PHPIZE_DEPS \
		&& docker-php-ext-configure gd --with-freetype --with-jpeg \
		&& docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath gd zip \
		&& pecl install redis \
		&& docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Code source (inclut public/build)
COPY . .

# Dépendances PHP production
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Permissions Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
	&& chmod -R ug+rwX storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]


FROM nginx:alpine AS nginx
WORKDIR /var/www

# Fichiers publics servis par Nginx
COPY public ./public

# Vhost Laravel
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# Répertoire storage attendu (monté en volume au runtime)
RUN mkdir -p /var/www/storage/app/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

