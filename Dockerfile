FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www

# Installer les dépendances système nécessaires aux extensions PHP
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

# Copier Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le code source applicatif
# ce Dockerfile suppose que les assets front sont déjà buildés
COPY . .

# Installer les dépendances PHP pour la production
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Préparer les permissions Laravel 
RUN chown -R www-data:www-data storage bootstrap/cache \
	&& chmod -R ug+rwX storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]


FROM nginx:alpine AS nginx
WORKDIR /var/www

# Nginx sert uniquement le dossier public
COPY public ./public

# Charger la conf Nginx Laravel
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# Créer le chemin storage/public 
RUN mkdir -p /var/www/storage/app/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]

