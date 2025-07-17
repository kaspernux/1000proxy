#!/bin/bash

# 1000proxy Docker Development Setup Script
# This script sets up the development environment using Docker

set -e

echo "🐳 1000proxy Docker Development Setup"
echo "======================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Check if .env file exists
if [ ! -f .env ]; then
    print_status "Creating .env file from .env.example..."
    cp .env.example .env
    print_success ".env file created"
else
    print_warning ".env file already exists"
fi

# Build and start services
print_status "Building Docker images..."
docker-compose build --no-cache

print_status "Starting Docker services..."
docker-compose up -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Check if services are running
print_status "Checking service status..."
docker-compose ps

# Install PHP dependencies
print_status "Installing PHP dependencies..."
docker-compose exec -T app composer install

# Install Node.js dependencies
print_status "Installing Node.js dependencies..."
docker-compose exec -T app npm install

# Build frontend assets
print_status "Building frontend assets..."
docker-compose exec -T app npm run build

# Generate application key
print_status "Generating application key..."
docker-compose exec -T app php artisan key:generate

# Wait for database to be ready
print_status "Waiting for database to be ready..."
until docker-compose exec -T mysql mysqladmin ping -h localhost --silent; do
    echo "Waiting for database connection..."
    sleep 2
done

# Run database migrations
print_status "Running database migrations..."
docker-compose exec -T app php artisan migrate:fresh --seed

# Clear and cache configurations
print_status "Optimizing application..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Set storage permissions
print_status "Setting storage permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage
docker-compose exec -T app chmod -R 755 /var/www/html/storage

print_success "🎉 Setup completed successfully!"
echo ""
echo "📋 Service Information:"
echo "======================="
echo "🌐 Application:     http://localhost:8000"
echo "👑 Admin Panel:     http://localhost:8000/admin"
echo "📧 MailHog:         http://localhost:8025"
echo "🗄️  MySQL:          localhost:3306"
echo "🔄 Redis:           localhost:6379"
echo ""
echo "🔐 Default Admin Credentials:"
echo "Email:    admin@example.com"
echo "Password: password"
echo ""
echo "🛠️  Useful Commands:"
echo "==================="
echo "View logs:          docker-compose logs -f"
echo "Stop services:      docker-compose down"
echo "Restart services:   docker-compose restart"
echo "Run artisan:        docker-compose exec app php artisan [command]"
echo "Run npm:            docker-compose exec app npm [command]"
echo "Access app shell:   docker-compose exec app bash"
echo "Access MySQL:       docker-compose exec mysql mysql -u root -p"
echo ""
print_success "Happy coding! 🚀"
