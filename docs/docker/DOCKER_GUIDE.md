# Docker Guide

This guide covers Docker development and deployment for the 1000proxy application with modern UI implementation.

## Table of Contents

1. [Quick Start with Docker](#quick-start-with-docker)
2. [Development Environment](#development-environment)
3. [Production Deployment](#production-deployment)
4. [Docker Services](#docker-services)
5. [Environment Configuration](#environment-configuration)
6. [Troubleshooting](#troubleshooting)
7. [Performance Optimization](#performance-optimization)

## Quick Start with Docker

### Prerequisites

- Docker 24.0+ installed
- Docker Compose 2.0+ installed
- Git for cloning the repository

### Development Setup

```bash
# Clone the repository
git clone https://github.com/your-username/1000proxy.git
cd 1000proxy

# Quick setup with automated script (recommended)
chmod +x scripts/docker-setup.sh
./scripts/docker-setup.sh

# Or manual setup
cp .env.example .env
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app npm run build
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed

# Access the application
open http://localhost:8000
```

The application will be available at:
- **Frontend**: http://localhost:8000
- **MySQL**: localhost:3306
- **Redis**: localhost:6379
- **Filament Admin**: http://localhost:8000/admin

## Development Environment

### Docker Compose Services

The development environment includes:

```yaml
services:
  app:          # Laravel application with Livewire & modern UI
  mysql:        # MySQL 8.0 database
  redis:        # Redis for caching and sessions
  queue:        # Laravel queue worker
  scheduler:    # Laravel task scheduler
  mailhog:      # Email testing (optional)
  nginx:        # Web server (production-like)
```

### Modern UI Development

The Docker environment is optimized for modern UI development:

```bash
# Install UI dependencies
docker-compose exec app npm install

# Build modern UI assets
docker-compose exec app npm run build

# Watch for changes (development)
docker-compose exec app npm run dev

# Run Livewire tests
docker-compose exec app php artisan test --filter=Livewire
```

### Working with Livewire Components

```bash
# Create new Livewire component
docker-compose exec app php artisan make:livewire MyComponent

# Test component
docker-compose exec app php artisan livewire:test MyComponent

# Publish Livewire assets
docker-compose exec app php artisan livewire:publish --assets
```

### Heroicons Integration

The Docker environment includes all dependencies for Heroicons:

```bash
# Install Heroicons
docker-compose exec app npm install @heroicons/react

# Build with Heroicons
docker-compose exec app npm run build

# Test icon component
docker-compose exec app php artisan test --filter=HeroiconsTest
```

### File Structure in Container

```
/var/www/html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/           # Compiled assets and Livewire scripts
├── resources/
│   ├── css/         # Tailwind CSS source
│   ├── js/          # Alpine.js and Livewire JS
│   └── views/       # Blade templates with Livewire components
├── storage/
├── tests/
└── vendor/
```

## Production Deployment

### Production Docker Compose

Create `docker-compose.production.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: proxy-app
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=mysql
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/html/storage
      - ./public:/var/www/html/public
    networks:
      - proxy-network
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: proxy-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/ssl:/etc/ssl/certs
      - ./public:/var/www/html/public:ro
    networks:
      - proxy-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: proxy-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
    networks:
      - proxy-network

  redis:
    image: redis:7-alpine
    container_name: proxy-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - proxy-network

  queue:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: proxy-queue
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - proxy-network
    depends_on:
      - mysql
      - redis

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: proxy-scheduler
    restart: unless-stopped
    command: |
      sh -c 'while true; do
        php artisan schedule:run
        sleep 60
      done'
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - proxy-network
    depends_on:
      - mysql
      - redis

volumes:
  mysql_data:
  redis_data:

networks:
  proxy-network:
    driver: bridge
```

### Production Dockerfile

Create `Dockerfile.production`:

```dockerfile
# Multi-stage build for production
FROM node:20-alpine AS frontend-build

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

FROM php:8.3-fpm-alpine AS backend-build

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
    mysql-client

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

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create app directory
WORKDIR /var/www/html

# Copy composer files
COPY composer*.json ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .
COPY --from=frontend-build /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Production optimizations
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 9000

CMD ["php-fpm"]
```

### Deploy to Production

```bash
# Build and deploy
docker-compose -f docker-compose.production.yml up -d --build

# Run migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Clear and cache everything
docker-compose -f docker-compose.production.yml exec app php artisan optimize

# Check health
docker-compose -f docker-compose.production.yml exec app php artisan health:check
```

## Docker Services

### Application Service (app)

The main Laravel application with modern UI components:

```dockerfile
# Based on PHP 8.3 FPM
FROM php:8.3-fpm

# Includes:
- Laravel 12.x framework
- Livewire 3.x for reactive components
- Tailwind CSS 3.x for modern styling
- Heroicons for professional icons
- Alpine.js for enhanced interactivity
```

**Key Features:**
- Modern UI with Livewire components
- Heroicons integration
- Tailwind CSS compilation
- Laravel optimization commands

### Database Service (mysql)

MySQL 8.0 optimized for production:

```yaml
mysql:
  image: mysql:8.0
  environment:
    MYSQL_ROOT_PASSWORD: secure_password
    MYSQL_DATABASE: proxy_production
  volumes:
    - mysql_data:/var/lib/mysql
    - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
```

### Cache Service (redis)

Redis for sessions, cache, and queues:

```yaml
redis:
  image: redis:7-alpine
  command: redis-server --appendonly yes --requirepass secure_password
  volumes:
    - redis_data:/data
```

### Queue Worker Service

Processes background jobs:

```bash
# Queue commands
docker-compose exec queue php artisan queue:work
docker-compose exec queue php artisan queue:status
docker-compose exec queue php artisan queue:restart
```

### Scheduler Service

Handles Laravel scheduled tasks:

```bash
# View scheduled tasks
docker-compose exec scheduler php artisan schedule:list

# Run schedule manually
docker-compose exec scheduler php artisan schedule:run
```

## Environment Configuration

### Development Environment

```bash
# .env.docker (for development)
APP_NAME="1000proxy"
APP_ENV=local
APP_KEY=base64:your-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=1000proxy
DB_USERNAME=root
DB_PASSWORD=password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Livewire configuration
LIVEWIRE_ASSET_URL=http://localhost:8000
LIVEWIRE_UPDATE_METHOD=POST

# Frontend build
VITE_APP_NAME="${APP_NAME}"
VITE_APP_URL="${APP_URL}"
```

### Production Environment

```bash
# .env.production (for production)
APP_NAME="1000proxy"
APP_ENV=production
APP_KEY=base64:your-production-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=proxy_production
DB_USERNAME=proxy_user
DB_PASSWORD=secure_production_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

### Docker-specific Configuration

Create `config/docker.php`:

```php
<?php

return [
    'services' => [
        'mysql' => [
            'host' => env('DB_HOST', 'mysql'),
            'port' => env('DB_PORT', 3306),
        ],
        'redis' => [
            'host' => env('REDIS_HOST', 'redis'),
            'port' => env('REDIS_PORT', 6379),
        ],
    ],
    
    'healthcheck' => [
        'enabled' => env('DOCKER_HEALTHCHECK', true),
        'endpoints' => [
            'database' => 'mysql:3306',
            'cache' => 'redis:6379',
        ],
    ],
];
```

## Troubleshooting

### Common Docker Issues

#### 1. Port Conflicts

```bash
# Check what's using port 8000
lsof -i :8000

# Use different port
docker-compose up -d --build && docker-compose port app 8000
```

#### 2. Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

#### 3. Database Connection Issues

```bash
# Check MySQL logs
docker-compose logs mysql

# Test connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

#### 4. Frontend Build Issues

```bash
# Clear npm cache
docker-compose exec app npm cache clean --force

# Rebuild frontend
docker-compose exec app rm -rf node_modules
docker-compose exec app npm install
docker-compose exec app npm run build
```

#### 5. Livewire Issues

```bash
# Clear Livewire cache
docker-compose exec app php artisan livewire:clear

# Rebuild Livewire assets
docker-compose exec app php artisan livewire:publish --assets --force
```

### Debugging Commands

```bash
# View all service logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f mysql
docker-compose logs -f redis

# Execute commands in containers
docker-compose exec app bash
docker-compose exec mysql mysql -u root -p
docker-compose exec redis redis-cli

# Check service status
docker-compose ps
docker-compose top

# Restart services
docker-compose restart app
docker-compose restart mysql
```

### Performance Monitoring

```bash
# Monitor resource usage
docker stats

# Check container health
docker-compose ps
docker inspect proxy-app | grep Health

# Database performance
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"

# Redis performance
docker-compose exec redis redis-cli info
docker-compose exec redis redis-cli monitor
```

## Performance Optimization

### Production Optimizations

#### 1. Multi-stage Builds

Use multi-stage builds to reduce image size:

```dockerfile
# Build stage
FROM node:20-alpine AS build
COPY package*.json ./
RUN npm ci --only=production

# Production stage
FROM php:8.3-fpm-alpine
COPY --from=build /app/public/build ./public/build
```

#### 2. Layer Caching

Optimize Docker layer caching:

```dockerfile
# Copy dependency files first
COPY composer.json composer.lock ./
RUN composer install --no-scripts

# Copy source code last
COPY . .
```

#### 3. PHP-FPM Optimization

Configure PHP-FPM for production:

```ini
; docker/php/php-fpm.conf
[www]
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

#### 4. OPcache Configuration

```ini
; docker/php/opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
```

### Development Optimizations

#### 1. Volume Mounts

Use bind mounts for development:

```yaml
volumes:
  - .:/var/www/html
  - /var/www/html/vendor
  - /var/www/html/node_modules
```

#### 2. File Watching

Enable file watching for frontend:

```bash
# Watch for changes
docker-compose exec app npm run dev

# Or use Vite with HMR
docker-compose exec app npm run dev -- --host 0.0.0.0
```

#### 3. Xdebug Configuration

Enable Xdebug for debugging:

```dockerfile
# Install Xdebug (development only)
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configure Xdebug
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
```

---

This Docker guide provides comprehensive instructions for both development and production environments with full support for the modern UI implementation including Livewire components and Heroicons integration.
