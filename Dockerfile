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
    && apk add --no-cache fcgi imagemagick \
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

# Layer 2: App code
COPY . .

# Layer 3: Post-install steps
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs --no-scripts

# Layer 4: Vite asset build — runs at image build time so the bundle ALWAYS
# reflects the env vars Coolify injects via build args. Without this step,
# /public/build/ is whatever the host happened to have at COPY time, which
# led to a stale bundle pointing the Reverb client at the raw origin IP and
# port 8001 even after we corrected the runtime env vars.
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ARG VITE_APP_NAME=Sambla
ENV NODE_OPTIONS="--max-old-space-size=1024"
RUN if [ -f package.json ]; then \
        npm install --no-audit --no-fund --silent && \
        npm run build && \
        rm -rf node_modules; \
    fi

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
