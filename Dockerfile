# --- ÉTAPE 1 : LE BUILD FRONT (Vite/Vue.js) ---
FROM node:22-alpine AS front
WORKDIR /app
COPY package*.json ./
RUN npm install --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY postcss.config.* ./
COPY tailwind.config.* ./
RUN npm run build


# --- ÉTAPE 2 : L'IMAGE APP (PHP + Supervisor + Worker) ---
FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www

# Installation PHP + Supervisor
RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev $PHPIZE_DEPS \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath gd zip \
    && pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=front /app/public/build /var/www/public/build

RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# On prépare le dossier de conf
RUN mkdir -p /etc/supervisor.d/
COPY docker/supervisor/worker.conf /etc/supervisor/conf.d/worker.conf

RUN chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]


# L'IMAGE NGINX 
FROM nginx:alpine AS nginx
WORKDIR /var/www

# Nginx a besoin du dossier public pour servir le JS/CSS
COPY public ./public
COPY --from=front /app/public/build /var/www/public/build

# configuration Nginx personnalisée pour Laravel
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# les dossiers pour éviter les erreurs de permissions
RUN mkdir -p /var/www/storage/app/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]