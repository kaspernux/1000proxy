# 1000Proxy Production Setup Script for Windows
# This script will help you set up MySQL and prepare the application for production

Write-Host "=== 1000Proxy Production Setup ===" -ForegroundColor Green

# Check if Chocolatey is installed
if (!(Get-Command choco -ErrorAction SilentlyContinue)) {
    Write-Host "Installing Chocolatey..." -ForegroundColor Yellow
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
    refreshenv
}

# Install MySQL
Write-Host "Installing MySQL..." -ForegroundColor Yellow
choco install mysql -y

# Start MySQL service
Write-Host "Starting MySQL service..." -ForegroundColor Yellow
Start-Service MySQL80

# Wait for MySQL to start
Start-Sleep -Seconds 10

# Create database and user
Write-Host "Setting up database..." -ForegroundColor Yellow
$mysqlCommands = @"
CREATE DATABASE IF NOT EXISTS 1000proxy;
CREATE USER IF NOT EXISTS '1000proxy'@'localhost' IDENTIFIED BY 'SecurePassword123!';
GRANT ALL PRIVILEGES ON 1000proxy.* TO '1000proxy'@'localhost';
ALTER USER 'root'@'localhost' IDENTIFIED BY 'RootPassword123!';
FLUSH PRIVILEGES;
EXIT;
"@

# Save commands to temp file
$mysqlCommands | Out-File -FilePath "setup_db.sql" -Encoding UTF8

# Execute MySQL commands
mysql -u root -e "source setup_db.sql"

Write-Host "=== Setup Complete! ===" -ForegroundColor Green
Write-Host "Database: 1000proxy" -ForegroundColor Cyan
Write-Host "Username: 1000proxy" -ForegroundColor Cyan
Write-Host "Password: SecurePassword123!" -ForegroundColor Cyan
Write-Host "Root Password: RootPassword123!" -ForegroundColor Cyan

# Clean up
Remove-Item "setup_db.sql" -ErrorAction SilentlyContinue
