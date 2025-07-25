# 1000proxy Docker Development Setup Script for Windows
# This script sets up the development environment using Docker

Write-Host "🐳 1000proxy Docker Development Setup" -ForegroundColor Blue
Write-Host "======================================" -ForegroundColor Blue

function Write-Status {
    param($Message)
    Write-Host "[INFO] $Message" -ForegroundColor Cyan
}

function Write-Success {
    param($Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor Green
}

function Write-Warning {
    param($Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param($Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

# Check if Docker is installed
try {
    docker --version | Out-Null
} catch {
    Write-Error "Docker is not installed. Please install Docker Desktop first."
    exit 1
}

# Check if Docker Compose is installed
try {
    docker-compose --version | Out-Null
} catch {
    Write-Error "Docker Compose is not available. Please ensure Docker Desktop includes Docker Compose."
    exit 1
}

# Check if .env file exists
if (-not (Test-Path ".env")) {
    Write-Status "Creating .env file from .env.example..."
    Copy-Item ".env.example" ".env"
    Write-Success ".env file created"
} else {
    Write-Warning ".env file already exists"
}

# Build and start services
Write-Status "Building Docker images..."
docker-compose build --no-cache

Write-Status "Starting Docker services..."
docker-compose up -d

# Wait for services to be ready
Write-Status "Waiting for services to be ready..."
Start-Sleep -Seconds 30

# Check if services are running
Write-Status "Checking service status..."
docker-compose ps

# Install PHP dependencies
Write-Status "Installing PHP dependencies..."
docker-compose exec -T app composer install

# Install Node.js dependencies
Write-Status "Installing Node.js dependencies..."
docker-compose exec -T app npm install

# Build frontend assets
Write-Status "Building frontend assets..."
docker-compose exec -T app npm run build

# Generate application key
Write-Status "Generating application key..."
docker-compose exec -T app php artisan key:generate

# Wait for database to be ready
Write-Status "Waiting for database to be ready..."
do {
    Write-Host "Waiting for database connection..." -ForegroundColor Gray
    Start-Sleep -Seconds 2
    $dbReady = docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>$null
} while ($LASTEXITCODE -ne 0)

# Run database migrations
Write-Status "Running database migrations..."
docker-compose exec -T app php artisan migrate:fresh --seed

# Clear and cache configurations
Write-Status "Optimizing application..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Set storage permissions
Write-Status "Setting storage permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage
docker-compose exec -T app chmod -R 755 /var/www/html/storage

Write-Success "🎉 Setup completed successfully!"
Write-Host ""
Write-Host "📋 Service Information:" -ForegroundColor Yellow
Write-Host "======================="
Write-Host "🌐 Application:     http://localhost:8000"
Write-Host "👑 Admin Panel:     http://localhost:8000/admin"
Write-Host "📧 MailHog:         http://localhost:8025"
Write-Host "🗄️  MySQL:          localhost:3306"
Write-Host "🔄 Redis:           localhost:6379"
Write-Host ""
Write-Host "🔐 Default Admin Credentials:" -ForegroundColor Yellow
Write-Host "Email:    admin@example.com"
Write-Host "Password: password"
Write-Host ""
Write-Host "🛠️  Useful Commands:" -ForegroundColor Yellow
Write-Host "==================="
Write-Host "View logs:          docker-compose logs -f"
Write-Host "Stop services:      docker-compose down"
Write-Host "Restart services:   docker-compose restart"
Write-Host "Run artisan:        docker-compose exec app php artisan [command]"
Write-Host "Run npm:            docker-compose exec app npm [command]"
Write-Host "Access app shell:   docker-compose exec app bash"
Write-Host "Access MySQL:       docker-compose exec mysql mysql -u root -p"
Write-Host ""
Write-Success "Happy coding! 🚀"
