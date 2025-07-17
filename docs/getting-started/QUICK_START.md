# Quick Start Guide

Get up and running with 1000proxy in under 10 minutes and experience the modern, professional UI with Heroicons integration.

## Prerequisites

Before you begin, ensure you have:

- **Docker & Docker Compose** (recommended) OR
- **PHP 8.3+** installed
- **Composer** package manager
- **Node.js 18+** and npm
- **MySQL 8.0+** or compatible database
- **Redis** server (recommended)
- **Git** for version control

## 🐳 Quick Installation with Docker (Recommended)

### 1. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy

# Copy environment file
cp .env.example .env
```

### 2. Start with Docker

```bash
# Build and start all services
docker-compose up -d

# Setup application
docker-compose exec app composer install
docker-compose exec app npm install && npm run build
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate:fresh --seed
```

### 3. Access Your Application

- **Frontend**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin
- **Database**: localhost:3306
- **Redis**: localhost:6379

**Default Admin Credentials**:
- Email: admin@example.com
- Password: password

## 📦 Traditional Installation

### 1. Clone the Repository

```bash
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies and build modern UI assets
npm install
npm run build
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment

Edit `.env` file with your settings:

```env
# Application
APP_NAME="1000proxy"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy1000
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE proxy1000;
exit

# Run migrations and seeders
php artisan migrate:fresh --seed
```

### 6. Build Assets

```bash
# Development build
npm run dev

# Or production build
npm run build
```

### 7. Start the Application

```bash
# Start Laravel development server
php artisan serve

# In another terminal, start the queue worker
php artisan queue:work
```

Visit [http://localhost:8000](http://localhost:8000) to access your modern, professional proxy management platform.

## 🎨 Modern UI Features

After setup, you'll experience:

### Professional Design
- **Heroicons Integration**: 20+ professional SVG icons replacing all emojis
- **Gradient Aesthetics**: Modern gradient-based design system
- **Responsive Layout**: Mobile-first design optimized for all devices
- **Interactive Components**: Livewire 3.x reactive components with real-time updates

### Key UI Components
- **Dynamic Product Filtering**: Real-time search and category filtering
- **Interactive Cart**: Instant updates without page reloads  
- **Modern Forms**: Progressive validation and submission
- **Professional Navigation**: Clean, intuitive navigation with proper iconography

## Default Access

### Admin Panel
- **URL**: http://localhost:8000/admin
- **Email**: admin@example.com
- **Password**: password

### Customer Panel
- **URL**: http://localhost:8000/customer
- **Registration**: Open registration enabled by default

## Quick Verification

Run our feature verification script:

```bash
# Check all features
./check-features.ps1

# Run tests
./test-project.ps1

# Debug any issues
./debug-project.ps1
```

## Next Steps

1. **[Complete Installation Guide](INSTALLATION.md)** - Detailed setup instructions
2. **[Configuration Guide](CONFIGURATION.md)** - Advanced configuration options
3. **[Development Setup](DEVELOPMENT_SETUP.md)** - Development environment setup
4. **[Admin Guide](../user-guides/USER_GUIDES.md)** - Using the admin panel
5. **[API Documentation](../api/API_DOCUMENTATION.md)** - API integration

## Common Issues

### Database Connection Error
```bash
# Check database credentials in .env
# Ensure MySQL is running
sudo systemctl status mysql
```

### Permission Errors
```bash
# Fix storage permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Redis Connection Error
```bash
# Install and start Redis
sudo apt install redis-server
sudo systemctl start redis-server
```

### NPM Build Errors
```bash
# Clear npm cache
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

## Development Tools

```bash
# Enable debugging
APP_DEBUG=true

# Install development dependencies
composer install --dev

# Run tests
php artisan test

# Code formatting
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

## Production Considerations

For production deployment:

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Use a proper web server (Nginx/Apache)
3. Configure SSL certificates
4. Set up proper queue workers
5. Configure caching and optimization
6. Set up monitoring and backups

See the [Deployment Guide](../deployment/DEPLOYMENT_GUIDE.md) for detailed production setup instructions.

## Getting Help

- **Documentation**: Browse the complete documentation
- **Issues**: Report bugs on GitHub Issues
- **Community**: Join our community discussions
- **Support**: Contact support for assistance

---

**Ready to explore?** Continue with the [Installation Guide](INSTALLATION.md) for detailed setup instructions.
