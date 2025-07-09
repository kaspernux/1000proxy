# 1000proxy Development Environment Setup Script
# This script sets up the development environment for testing

Write-Host "=== 1000proxy Development Environment Setup ===" -ForegroundColor Green
Write-Host "Date: $(Get-Date)"
Write-Host "Working Directory: $(Get-Location)"
Write-Host ""

# Check if PHP is available
Write-Host "=== Checking PHP Installation ===" -ForegroundColor Yellow
$phpPath = Get-Command php -ErrorAction SilentlyContinue
if ($phpPath) {
    Write-Host "✓ PHP found at: $($phpPath.Source)" -ForegroundColor Green
    & php --version
} else {
    Write-Host "✗ PHP not found in PATH" -ForegroundColor Red
    Write-Host "Attempting to install PHP using winget..." -ForegroundColor Yellow
    
    try {
        winget install PHP.PHP
        Write-Host "✓ PHP installation attempted" -ForegroundColor Green
    } catch {
        Write-Host "✗ Failed to install PHP via winget" -ForegroundColor Red
        Write-Host "Please install PHP manually or use Docker" -ForegroundColor Yellow
    }
}

# Check if Composer is available
Write-Host ""
Write-Host "=== Checking Composer Installation ===" -ForegroundColor Yellow
$composerPath = Get-Command composer -ErrorAction SilentlyContinue
if ($composerPath) {
    Write-Host "✓ Composer found at: $($composerPath.Source)" -ForegroundColor Green
    & composer --version
} else {
    Write-Host "✗ Composer not found in PATH" -ForegroundColor Red
    Write-Host "Attempting to install Composer using winget..." -ForegroundColor Yellow
    
    try {
        winget install Composer.Composer
        Write-Host "✓ Composer installation attempted" -ForegroundColor Green
    } catch {
        Write-Host "✗ Failed to install Composer via winget" -ForegroundColor Red
    }
}

# Check Docker status
Write-Host ""
Write-Host "=== Checking Docker Status ===" -ForegroundColor Yellow
try {
    $dockerVersion = docker version 2>&1
    if ($dockerVersion -like "*error*") {
        Write-Host "✗ Docker is not running" -ForegroundColor Red
        Write-Host "Please start Docker Desktop" -ForegroundColor Yellow
    } else {
        Write-Host "✓ Docker is available" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ Docker command failed" -ForegroundColor Red
}

# Check project structure
Write-Host ""
Write-Host "=== Project Structure Validation ===" -ForegroundColor Yellow
$requiredFiles = @(
    "composer.json",
    "package.json",
    ".env",
    "app/Services/MonitoringService.php",
    "app/Console/Commands/HealthCheckCommand.php",
    "app/Providers/AppServiceProvider.php",
    "database/database.sqlite"
)

foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "✓ $file exists" -ForegroundColor Green
    } else {
        Write-Host "✗ $file missing" -ForegroundColor Red
    }
}

# Environment configuration
Write-Host ""
Write-Host "=== Environment Configuration ===" -ForegroundColor Yellow
if (Test-Path ".env") {
    $envContent = Get-Content ".env"
    $appEnv = ($envContent | Select-String "^APP_ENV=").ToString().Split("=")[1]
    $appDebug = ($envContent | Select-String "^APP_DEBUG=").ToString().Split("=")[1]
    $dbConnection = ($envContent | Select-String "^DB_CONNECTION=").ToString().Split("=")[1]
    
    Write-Host "APP_ENV: $appEnv" -ForegroundColor Cyan
    Write-Host "APP_DEBUG: $appDebug" -ForegroundColor Cyan
    Write-Host "DB_CONNECTION: $dbConnection" -ForegroundColor Cyan
}

# Next steps
Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Green
Write-Host "1. If PHP and Composer are available, run:" -ForegroundColor Yellow
Write-Host "   composer install" -ForegroundColor White
Write-Host "   php artisan migrate" -ForegroundColor White
Write-Host "   php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "2. If using Docker, run:" -ForegroundColor Yellow
Write-Host "   docker-compose up --build" -ForegroundColor White
Write-Host ""
Write-Host "3. To test the health check command:" -ForegroundColor Yellow
Write-Host "   php artisan system:health-check" -ForegroundColor White

Write-Host ""
Write-Host "=== Setup Complete ===" -ForegroundColor Green
