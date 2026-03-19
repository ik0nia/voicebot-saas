# Stage 1: Composer dependencies
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Stage 2: Node.js build
FROM node:20-alpine AS node
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

# Stage 3: Production
FROM php:8.3-fpm-alpine AS production

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    supervisor \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    intl \
    pcntl \
    sockets \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

COPY --from=composer /app/vendor vendor/
COPY . .
RUN composer dump-autoload --optimize --no-dev

COPY --from=node /app/public/build public/build/

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
