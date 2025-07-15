# 1000proxy Development Environment Setup Script for Windows
Write-Host "=== 1000proxy Development Environment Setup ===" -ForegroundColor Green
Write-Host "This script sets up the development environment with file-based drivers" -ForegroundColor Yellow
Write-Host ""

# Create development .env backup
Copy-Item ".env" ".env.production.backup" -Force
Write-Host "✓ Backed up production .env to .env.production.backup" -ForegroundColor Green

# Read current .env content
$envContent = Get-Content ".env"

# Update configuration for development
$envContent = $envContent -replace "DB_CONNECTION=mysql", "DB_CONNECTION=sqlite"
$envContent = $envContent -replace "DB_HOST=mysql", "# DB_HOST=mysql"
$envContent = $envContent -replace "DB_PORT=3306", "# DB_PORT=3306"
$envContent = $envContent -replace "DB_DATABASE=1000proxy", "DB_DATABASE=database/database.sqlite"
$envContent = $envContent -replace "DB_USERNAME=root", "# DB_USERNAME=root"
$envContent = $envContent -replace "DB_PASSWORD=password", "# DB_PASSWORD=password"
$envContent = $envContent -replace "CACHE_STORE=redis", "CACHE_STORE=file"
$envContent = $envContent -replace "SESSION_DRIVER=redis", "SESSION_DRIVER=file"
$envContent = $envContent -replace "QUEUE_CONNECTION=redis", "QUEUE_CONNECTION=sync"

# Save updated .env
$envContent | Set-Content ".env"
Write-Host "✓ Updated .env for development (file-based drivers)" -ForegroundColor Green

# Create SQLite database
if (-not (Test-Path "database\database.sqlite")) {
    New-Item -ItemType File -Path "database\database.sqlite" -Force | Out-Null
    Write-Host "✓ Created SQLite database file" -ForegroundColor Green
} else {
    Write-Host "✓ SQLite database file already exists" -ForegroundColor Green
}

# Clear caches
Write-Host "Clearing Laravel caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
Write-Host "✓ Cleared Laravel caches" -ForegroundColor Green

# Generate app key if not set
php artisan key:generate --force
Write-Host "✓ Generated application key" -ForegroundColor Green

# Run migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force
Write-Host "✓ Database migrations completed" -ForegroundColor Green

# Seed basic data
Write-Host "Seeding basic data..." -ForegroundColor Yellow
try {
    php artisan db:seed --force
    Write-Host "✓ Database seeding completed" -ForegroundColor Green
} catch {
    Write-Host "⚠ Database seeding failed or no seeders available" -ForegroundColor Yellow
}

# Create storage link
php artisan storage:link
Write-Host "✓ Created storage symbolic link" -ForegroundColor Green

# Optimize for development
php artisan config:cache
Write-Host "✓ Cached configuration" -ForegroundColor Green

Write-Host ""
Write-Host "=== Development Environment Ready! ===" -ForegroundColor Green
Write-Host "You can now start the development server:" -ForegroundColor Yellow
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "Access Points:" -ForegroundColor Yellow
Write-Host "  Admin Panel: http://localhost:8000/admin" -ForegroundColor White
Write-Host "  Customer Panel: http://localhost:8000/account" -ForegroundColor White
Write-Host "  Main Site: http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "To restore production settings:" -ForegroundColor Yellow
Write-Host "  Copy-Item '.env.production.backup' '.env' -Force" -ForegroundColor White
