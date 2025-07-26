# Multi-stage build for production

FROM node:20.12.2-alpine3.19 AS frontend-build
WORKDIR /var/www/1000proxy
COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci --only=production; else npm install; fi
COPY . .
RUN npm run build


FROM php:8.3.7-fpm-alpine3.19 AS backend
# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    autoconf \
    g++ \
    make \
    mysql-client \
    linux-headers
# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    sockets
# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis
# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Set working directory
WORKDIR /var/www/1000proxy
# Copy composer files
COPY composer.json composer.lock ./
# Copy application files
COPY . .
COPY --from=frontend-build /var/www/1000proxy/public/build ./public/build
# Install composer dependencies (optimized) after all source files are in place
RUN composer install --no-dev --optimize-autoloader
# Set permissions and create storage symlink
RUN chown -R www-data:www-data /var/www/1000proxy \
    && chmod -R 775 /var/www/1000proxy/storage \
    && chmod -R 775 /var/www/1000proxy/bootstrap/cache \
    && php artisan storage:link || true
# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:9000/health || exit 1
# Expose port
EXPOSE 9000
# Start command
CMD ["php-fpm"]
