FROM php:8.4-fpm-alpine
WORKDIR /var/www

# Installer les dépendances système + extensions PHP requises
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

# Copier le code source (y compris assets pré-buildés)
COPY . .

# Installer les dépendances PHP pour la production uniquement
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Définir les permissions correctes pour Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

