#!/bin/bash

echo "=== 1000proxy Development Environment Setup ==="
echo "This script sets up the development environment with file-based drivers"
echo

# Create development .env
cp .env .env.production.backup
echo "✓ Backed up production .env to .env.production.backup"

# Update .env for development
sed -i.bak \
    -e 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' \
    -e 's/DB_HOST=mysql/# DB_HOST=mysql/' \
    -e 's/DB_PORT=3306/# DB_PORT=3306/' \
    -e 's/DB_DATABASE=1000proxy/DB_DATABASE=database\/database.sqlite/' \
    -e 's/DB_USERNAME=root/# DB_USERNAME=root/' \
    -e 's/DB_PASSWORD=password/# DB_PASSWORD=password/' \
    -e 's/CACHE_STORE=redis/CACHE_STORE=file/' \
    -e 's/SESSION_DRIVER=redis/SESSION_DRIVER=file/' \
    -e 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=sync/' \
    .env

echo "✓ Updated .env for development (file-based drivers)"

# Create SQLite database
touch database/database.sqlite
echo "✓ Created SQLite database file"

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Cleared Laravel caches"

# Generate app key if not set
php artisan key:generate --force
echo "✓ Generated application key"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force
echo "✓ Database migrations completed"

# Seed basic data
echo "Seeding basic data..."
php artisan db:seed --force
echo "✓ Database seeding completed"

# Create storage link
php artisan storage:link
echo "✓ Created storage symbolic link"

# Optimize for development
php artisan config:cache
echo "✓ Cached configuration"

echo
echo "=== Development Environment Ready! ==="
echo "You can now start the development server:"
echo "  php artisan serve"
echo
echo "Admin Panel: http://localhost:8000/admin"
echo "Customer Panel: http://localhost:8000/account"
echo "Main Site: http://localhost:8000"
echo
echo "To restore production settings:"
echo "  cp .env.production.backup .env"
