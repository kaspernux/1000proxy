# Development Setup Guide

Complete development environment setup for 1000proxy application.

## Development Environment Requirements

### System Requirements

- **Operating System**: Windows 10/11, macOS 10.15+, or Ubuntu 20.04+
- **PHP**: 8.3 or higher with required extensions
- **Node.js**: 18.x or higher (20.x LTS recommended)
- **Composer**: Latest version
- **Git**: Latest version
- **Docker**: Optional but recommended for consistent environment

### IDE Recommendations

- **VS Code** with PHP, Laravel, and JavaScript extensions
- **PhpStorm** (full Laravel support)
- **Sublime Text** with Laravel packages
- **Vim/Neovim** with Laravel plugins

## Quick Development Setup

### 1. Clone and Install

```bash
# Clone repository
git clone https://github.com/kaspernux/1000proxy.git
cd 1000proxy

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Database Setup

#### Using Docker (Recommended)

```bash
# Start development services
docker-compose up -d mysql redis

# Wait for services to start
sleep 10

# Run migrations and seeders
php artisan migrate:fresh --seed
```

#### Manual Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE proxy1000_dev;
CREATE USER 'proxy_dev'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON proxy1000_dev.* TO 'proxy_dev'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env file
DB_DATABASE=proxy1000_dev
DB_USERNAME=proxy_dev
DB_PASSWORD=password

# Run migrations
php artisan migrate:fresh --seed
```

### 3. Development Services

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Asset compilation (watch mode)
npm run dev

# Terminal 3: Queue worker
php artisan queue:work

# Terminal 4: Laravel Horizon (if installed)
php artisan horizon
```

## Environment Configuration

### Development Environment File

Create `.env` for development:

```env
# Application
APP_NAME="1000proxy Dev"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy1000_dev
DB_USERNAME=proxy_dev
DB_PASSWORD=password

# Cache & Sessions (Redis for development consistency)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (Use Mailtrap for development)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="dev@1000proxy.local"
MAIL_FROM_NAME="1000proxy Dev"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Development Tools
DEBUGBAR_ENABLED=true
TELESCOPE_ENABLED=true
RAY_ENABLED=true

# 3X-UI (Development Panel)
XUI_PANEL_URL=http://localhost:2053
XUI_USERNAME=admin
XUI_PASSWORD=admin
XUI_SSL_VERIFY=false
```

## Development Tools Setup

### Laravel Debugbar

```bash
# Install Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev

# Publish configuration
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

### Laravel Telescope

```bash
# Install Telescope
composer require laravel/telescope --dev

# Install Telescope assets
php artisan telescope:install

# Run migrations
php artisan migrate
```

### Laravel Ray

```bash
# Install Ray
composer require spatie/laravel-ray --dev

# Publish configuration
php artisan vendor:publish --provider="Spatie\LaravelRay\RayServiceProvider"
```

### Laravel Pint (Code Formatting)

```bash
# Install Pint (included by default in Laravel)
# Format code
./vendor/bin/pint

# Check formatting
./vendor/bin/pint --test
```

### PHPStan (Static Analysis)

```bash
# Install PHPStan
composer require phpstan/phpstan --dev
composer require phpstan/phpstan-doctrine --dev
composer require phpstan/phpstan-strict-rules --dev

# Run analysis
./vendor/bin/phpstan analyse
```

## Git Configuration

### Git Hooks Setup

Create `.git/hooks/pre-commit`:

```bash
#!/bin/sh
# Pre-commit hook for 1000proxy

echo "Running pre-commit checks..."

# Check PHP syntax
echo "Checking PHP syntax..."
find . -name "*.php" -not -path "./vendor/*" -not -path "./storage/*" -exec php -l {} \; | grep -v "No syntax errors"

# Run PHPStan
echo "Running PHPStan..."
./vendor/bin/phpstan analyse --no-progress

# Run Pint
echo "Running Pint..."
./vendor/bin/pint --test

# Run tests
echo "Running tests..."
php artisan test --stop-on-failure

echo "Pre-commit checks completed!"
```

Make it executable:

```bash
chmod +x .git/hooks/pre-commit
```

### Git Configuration

```bash
# Set Git configuration
git config user.name "Your Name"
git config user.email "your.email@example.com"

# Set up useful aliases
git config alias.st status
git config alias.co checkout
git config alias.br branch
git config alias.up rebase
git config alias.ci commit
```

## IDE Configuration

### VS Code Setup

Install recommended extensions:

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "bradlc.vscode-tailwindcss",
    "ms-vscode.vscode-json",
    "formulahendry.auto-rename-tag",
    "christian-kohler.path-intellisense",
    "ms-vscode.vscode-typescript-next",
    "bradlc.vscode-tailwindcss",
    "onecentlin.laravel-blade",
    "amiralizadeh9480.laravel-extra-intellisense",
    "codingyu.laravel-goto-view",
    "ryannaddy.laravel-artisan",
    "open-southeners.laravel-pint"
  ]
}
```

Create `.vscode/settings.json`:

```json
{
  "php.validate.executablePath": "/usr/bin/php8.3",
  "intelephense.files.maxSize": 5000000,
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll": true
  },
  "tailwindCSS.includeLanguages": {
    "blade": "html"
  },
  "files.associations": {
    "*.blade.php": "blade"
  },
  "emmet.includeLanguages": {
    "blade": "html"
  }
}
```

### PhpStorm Setup

1. **Configure PHP Interpreter**:
   - File → Settings → Languages & Frameworks → PHP
   - Set CLI Interpreter to PHP 8.3

2. **Laravel Plugin**:
   - Install Laravel Plugin
   - Enable Laravel support in project settings

3. **Database Configuration**:
   - Add database connection
   - Configure database inspection

## Testing Environment

### Test Database Setup

```bash
# Create test database
mysql -u root -p
CREATE DATABASE proxy1000_test;
GRANT ALL PRIVILEGES ON proxy1000_test.* TO 'proxy_dev'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Testing Configuration

Create `.env.testing`:

```env
APP_ENV=testing
APP_KEY=base64:your-test-key
DB_CONNECTION=mysql
DB_DATABASE=proxy1000_test
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=UserCanLoginTest

# Parallel testing
php artisan test --parallel
```

## Asset Development

### Frontend Development

```bash
# Install frontend dependencies
npm install

# Development build (watch mode)
npm run dev

# Production build
npm run build

# Lint JavaScript
npm run lint

# Fix JavaScript linting issues
npm run lint:fix
```

### CSS Development with Tailwind

```bash
# Build CSS with Tailwind
npm run build

# Watch for changes
npm run dev

# Build for production
npm run build
```

## Database Development

### Migrations

```bash
# Create migration
php artisan make:migration create_products_table

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Seeders and Factories

```bash
# Create seeder
php artisan make:seeder ProductSeeder

# Create factory
php artisan make:factory ProductFactory

# Run specific seeder
php artisan db:seed --class=ProductSeeder

# Create model with migration, factory, and seeder
php artisan make:model Product -mfs
```

## API Development

### API Resources

```bash
# Create API resource
php artisan make:resource ProductResource

# Create API resource collection
php artisan make:resource ProductCollection

# Create API controller
php artisan make:controller Api/ProductController --api --resource
```

### API Testing

```bash
# Test API endpoints
php artisan test --filter=ApiTest

# Generate API documentation
php artisan scribe:generate
```

## Debugging

### Debug Configuration

```env
# Enable debugging
APP_DEBUG=true
LOG_LEVEL=debug

# Ray debugging
RAY_ENABLED=true

# Debugbar
DEBUGBAR_ENABLED=true

# Telescope
TELESCOPE_ENABLED=true
```

### Debugging Tools

```php
// Ray debugging
ray($variable);
ray()->showQueries();
ray()->showJobs();

// Traditional debugging
dd($variable);
dump($variable);

// Log debugging
Log::debug('Debug message', $context);
Log::info('Info message');
Log::error('Error message');
```

## Performance Optimization

### Development Performance

```bash
# Clear all caches
php artisan optimize:clear

# Optimize for development
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Profiling

```bash
# Install Laravel Clockwork
composer require itsgoingd/clockwork --dev

# Profile queries
DB::enableQueryLog();
// Your code here
dd(DB::getQueryLog());
```

## Docker Development Environment

### Docker Compose Setup

Create `docker-compose.dev.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.dev
    volumes:
      - .:/var/www/html
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    ports:
      - "8000:8000"
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: proxy1000_dev
      MYSQL_USER: proxy_dev
      MYSQL_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  mysql_data:
```

### Docker Commands

```bash
# Start development environment
docker-compose -f docker-compose.dev.yml up -d

# Run artisan commands
docker-compose exec app php artisan migrate

# Access container shell
docker-compose exec app bash

# View logs
docker-compose logs -f app

# Stop environment
docker-compose down
```

## Troubleshooting

### Common Development Issues

1. **Permission Errors**

   ```bash
   sudo chown -R $USER:$USER .
   chmod -R 755 storage bootstrap/cache
   ```

2. **Composer Memory Issues**

   ```bash
   php -d memory_limit=-1 /usr/local/bin/composer install
   ```

3. **NPM Permission Issues**

   ```bash
   npm config set prefix ~/.npm-global
   export PATH=~/.npm-global/bin:$PATH
   ```

4. **Port Already in Use**

   ```bash
   # Find process using port
   lsof -i :8000
   
   # Kill process
   kill -9 <PID>
   
   # Use different port
   php artisan serve --port=8001
   ```

### Performance Issues

1. **Slow Asset Compilation**

   ```bash
   # Use polling for file watching
   npm run dev -- --watch-poll
   
   # Increase memory for Node.js
   NODE_OPTIONS="--max-old-space-size=4096" npm run dev
   ```

2. **Database Query Performance**

   ```bash
   # Enable query logging
   DB_LOG_QUERIES=true
   
   # Use Ray to monitor queries
   ray()->showQueries();
   ```

## Development Workflow

### Feature Development

1. **Create Feature Branch**

   ```bash
   git checkout -b feature/new-feature
   ```

2. **Development Process**

   ```bash
   # Make changes
   # Run tests
   php artisan test
   
   # Check code style
   ./vendor/bin/pint --test
   
   # Static analysis
   ./vendor/bin/phpstan analyse
   ```

3. **Commit and Push**

   ```bash
   git add .
   git commit -m "feat: add new feature"
   git push origin feature/new-feature
   ```

### Code Review Process

1. Create pull request
2. Run automated tests
3. Code review by team members
4. Merge to main branch

### Release Process

1. Update version numbers
2. Run full test suite
3. Build production assets
4. Create release tag
5. Deploy to staging
6. Deploy to production

---

**Next Steps**: Continue with [User Guides](../user-guides/USER_GUIDES.md) to learn how to use the application.
