# Stage 1: Production
FROM php:8.3-fpm-alpine AS production

# System deps & PHP extensions (rarely changes - cached)
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    supervisor \
    nodejs \
    npm \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    intl \
    pcntl \
    sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && apk add --no-cache fcgi \
    && echo '#!/bin/sh' > /usr/local/bin/php-fpm-healthcheck \
    && echo 'SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1' >> /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
RUN echo '[www]' > /usr/local/etc/php-fpm.d/healthcheck.conf \
    && echo 'ping.path = /ping' >> /usr/local/etc/php-fpm.d/healthcheck.conf

WORKDIR /var/www/html

# Layer 1: Composer deps (cached if composer.lock unchanged)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Layer 2: Node deps (cached if package-lock.json unchanged)
COPY package.json package-lock.json ./
RUN npm ci

# Layer 3: App code (changes every deploy - but fast COPY)
COPY . .

# Layer 4: Post-install steps (quick, uses cached deps)
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs --no-scripts
RUN npm run build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
