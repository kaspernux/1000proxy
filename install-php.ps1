# PHP Installation Script for Windows
# This script downloads and sets up PHP for development

Write-Host "=== PHP Installation Script ===" -ForegroundColor Green
Write-Host "Date: $(Get-Date)"
Write-Host ""

# Create a temporary directory for downloads
$tempDir = "$env:TEMP\php_install"
if (!(Test-Path $tempDir)) {
    New-Item -ItemType Directory -Path $tempDir
}

# Download PHP
$phpUrl = "https://windows.php.net/downloads/releases/latest/php-8.3-nts-Win32-vs16-x64-latest.zip"
$phpZip = "$tempDir\php.zip"
$phpDir = "$env:USERPROFILE\php"

Write-Host "Downloading PHP 8.3.14..." -ForegroundColor Yellow
try {
    Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip
    Write-Host "✓ PHP downloaded successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to download PHP: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Extract PHP
Write-Host "Extracting PHP..." -ForegroundColor Yellow
try {
    if (Test-Path $phpDir) {
        Remove-Item -Path $phpDir -Recurse -Force
    }

    Expand-Archive -Path $phpZip -DestinationPath $phpDir
    Write-Host "✓ PHP extracted successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to extract PHP: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Configure PHP
Write-Host "Configuring PHP..." -ForegroundColor Yellow
$phpIni = "$phpDir\php.ini"
$phpIniDev = "$phpDir\php.ini-development"

if (Test-Path $phpIniDev) {
    Copy-Item -Path $phpIniDev -Destination $phpIni

    # Enable required extensions
    $extensions = @(
        "extension=pdo_sqlite",
        "extension=openssl",
        "extension=curl",
        "extension=fileinfo",
        "extension=mbstring",
        "extension=gd",
        "extension=zip"
    )

    $iniContent = Get-Content $phpIni
    $iniContent = $iniContent -replace ";extension=pdo_sqlite", "extension=pdo_sqlite"
    $iniContent = $iniContent -replace ";extension=openssl", "extension=openssl"
    $iniContent = $iniContent -replace ";extension=curl", "extension=curl"
    $iniContent = $iniContent -replace ";extension=fileinfo", "extension=fileinfo"
    $iniContent = $iniContent -replace ";extension=mbstring", "extension=mbstring"
    $iniContent = $iniContent -replace ";extension=gd", "extension=gd"
    $iniContent = $iniContent -replace ";extension=zip", "extension=zip"

    $iniContent | Set-Content $phpIni
    Write-Host "✓ PHP configured successfully" -ForegroundColor Green
} else {
    Write-Host "✗ PHP configuration file not found" -ForegroundColor Red
}

# Add PHP to PATH
Write-Host "Adding PHP to PATH..." -ForegroundColor Yellow
$currentPath = [Environment]::GetEnvironmentVariable("PATH", "User")
if ($currentPath -notlike "*$phpDir*") {
    [Environment]::SetEnvironmentVariable("PATH", "$currentPath;$phpDir", "User")
    Write-Host "✓ PHP added to PATH (restart terminal to take effect)" -ForegroundColor Green
} else {
    Write-Host "✓ PHP already in PATH" -ForegroundColor Green
}

# Download and install Composer
Write-Host "Installing Composer..." -ForegroundColor Yellow
$composerInstaller = "$tempDir\composer-setup.php"
$composerUrl = "https://getcomposer.org/installer"

try {
    Invoke-WebRequest -Uri $composerUrl -OutFile $composerInstaller

    # Run Composer installer
    & "$phpDir\php.exe" $composerInstaller --install-dir="$phpDir" --filename=composer

    Write-Host "✓ Composer installed successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to install Composer: $($_.Exception.Message)" -ForegroundColor Red
}

# Clean up
Remove-Item -Path $tempDir -Recurse -Force

Write-Host ""
Write-Host "=== Installation Complete ===" -ForegroundColor Green
Write-Host "PHP installed to: $phpDir" -ForegroundColor Cyan
Write-Host "Please restart your terminal and run 'php -v' to verify installation" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Green
Write-Host "1. Restart your terminal or run: refreshenv" -ForegroundColor White
Write-Host "2. Run: composer install" -ForegroundColor White
Write-Host "3. Run: php artisan migrate" -ForegroundColor White
Write-Host "4. Run: php artisan serve" -ForegroundColor White
