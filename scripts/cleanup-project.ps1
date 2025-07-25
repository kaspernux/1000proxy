# 1000proxy Project Cleanup Script
# This script removes temporary files and organizes the project structure

param(
    [switch]$DryRun = $false,
    [switch]$Force = $false
)

Write-Host "🧹 1000proxy Project Cleanup Script" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan

$projectRoot = $PSScriptRoot
$tempFilesRemoved = 0
$totalSizeFreed = 0

# Define files and directories to clean
$cleanupTargets = @{
    "Debug Files" = @(
        "debug_*.php",
        "debug_*.bat",
        "debug_*.sh"
    )
    "Test Files" = @(
        "test_*.php",
        "test_*.bat",
        "test_*.sh",
        "test-*.ps1",
        "test-*.json",
        "*_test.php",
        "comprehensive_relationship_test.php",
        "final_test.php"
    )
    "Check Files" = @(
        "check_*.php"
    )
    "Seed Files" = @(
        "basic_seed.php",
        "fixed_seeding.php",
        "reset_and_seed.php",
        "run_seeders.php",
        "seed_database.php"
    )
    "Temporary Executables" = @(
        "Composer-Setup.exe",
        "composer.phar",
        "composer-setup.php"
    )
    "Temporary Scripts" = @(
        "composer.bat",
        "php.bat",
        "quick_check.php",
        "production_readiness_check.php",
        "production-readiness-check.php"
    )
    "Build Files" = @(
        "jest.config.js",
        "postcss.config.js"
    )
    "Log Files" = @(
        "*.log"
    )
    "Cache Files" = @(
        ".DS_Store",
        "Thumbs.db",
        "desktop.ini"
    )
}

# Define directories to clean
$cleanupDirs = @(
    "temp",
    "tmp",
    ".temp",
    ".cache",
    "logs"
)

function Remove-CleanupItem {
    param(
        [string]$Path,
        [string]$Category
    )

    if (Test-Path $Path) {
        $item = Get-Item $Path
        $size = 0

        if ($item.PSIsContainer) {
            $size = (Get-ChildItem $Path -Recurse -File | Measure-Object -Property Length -Sum).Sum
        } else {
            $size = $item.Length
        }

        $sizeKB = [math]::Round($size / 1KB, 2)

        if ($DryRun) {
            Write-Host "  [DRY RUN] Would remove: $($item.Name) ($sizeKB KB)" -ForegroundColor Yellow
        } else {
            try {
                if ($item.PSIsContainer) {
                    Remove-Item $Path -Recurse -Force
                    Write-Host "  ✓ Removed directory: $($item.Name) ($sizeKB KB)" -ForegroundColor Green
                } else {
                    Remove-Item $Path -Force
                    Write-Host "  ✓ Removed file: $($item.Name) ($sizeKB KB)" -ForegroundColor Green
                }

                $script:tempFilesRemoved++
                $script:totalSizeFreed += $size
            } catch {
                Write-Host "  ✗ Failed to remove: $($item.Name) - $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
}

# Clean files by category
foreach ($category in $cleanupTargets.Keys) {
    Write-Host "`n📂 Cleaning: $category" -ForegroundColor Magenta

    foreach ($pattern in $cleanupTargets[$category]) {
        $files = Get-ChildItem -Path $projectRoot -Name $pattern -ErrorAction SilentlyContinue

        if ($files) {
            foreach ($file in $files) {
                $fullPath = Join-Path $projectRoot $file
                Remove-CleanupItem -Path $fullPath -Category $category
            }
        }
    }
}

# Clean directories
Write-Host "`n📁 Cleaning Directories" -ForegroundColor Magenta
foreach ($dir in $cleanupDirs) {
    $dirPath = Join-Path $projectRoot $dir
    if (Test-Path $dirPath) {
        Remove-CleanupItem -Path $dirPath -Category "Directories"
    }
}

# Clean Laravel specific temporary files
Write-Host "`n🏗️ Cleaning Laravel Files" -ForegroundColor Magenta

$laravelCleanup = @(
    "bootstrap/cache/*.php",
    "storage/logs/*.log",
    "storage/framework/cache/data/*",
    "storage/framework/sessions/*",
    "storage/framework/views/*.php"
)

foreach ($pattern in $laravelCleanup) {
    $fullPattern = Join-Path $projectRoot $pattern
    $files = Get-ChildItem -Path (Split-Path $fullPattern) -Name (Split-Path $fullPattern -Leaf) -ErrorAction SilentlyContinue

    if ($files) {
        foreach ($file in $files) {
            $fullPath = Join-Path (Split-Path $fullPattern) $file
            Remove-CleanupItem -Path $fullPath -Category "Laravel Cache"
        }
    }
}

# Clean node_modules if exists (can be regenerated)
$nodeModules = Join-Path $projectRoot "node_modules"
if (Test-Path $nodeModules) {
    Write-Host "`n📦 Node Modules Found" -ForegroundColor Magenta
    if ($Force -or $DryRun) {
        Remove-CleanupItem -Path $nodeModules -Category "Node Modules"
    } else {
        $response = Read-Host "Remove node_modules directory? (y/N)"
        if ($response -eq 'y' -or $response -eq 'Y') {
            Remove-CleanupItem -Path $nodeModules -Category "Node Modules"
        }
    }
}

# Clean vendor if requested
$vendor = Join-Path $projectRoot "vendor"
if (Test-Path $vendor) {
    Write-Host "`n📦 Vendor Directory Found" -ForegroundColor Magenta
    if ($Force) {
        Remove-CleanupItem -Path $vendor -Category "Vendor"
    } elseif (!$DryRun) {
        $response = Read-Host "Remove vendor directory? (requires composer install) (y/N)"
        if ($response -eq 'y' -or $response -eq 'Y') {
            Remove-CleanupItem -Path $vendor -Category "Vendor"
        }
    }
}

# Summary
Write-Host "`n📊 Cleanup Summary" -ForegroundColor Cyan
Write-Host "==================" -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "DRY RUN - No files were actually removed" -ForegroundColor Yellow
} else {
    $sizeMB = [math]::Round($totalSizeFreed / 1MB, 2)
    Write-Host "Files removed: $tempFilesRemoved" -ForegroundColor Green
    Write-Host "Space freed: $sizeMB MB" -ForegroundColor Green
}

Write-Host "`n✨ Project cleanup completed!" -ForegroundColor Green

# Regeneration instructions
if (!$DryRun -and $tempFilesRemoved -gt 0) {
    Write-Host "`n🔄 To regenerate removed dependencies:" -ForegroundColor Yellow
    Write-Host "  - Run: composer install" -ForegroundColor White
    Write-Host "  - Run: npm install" -ForegroundColor White
    Write-Host "  - Run: php artisan config:cache" -ForegroundColor White
    Write-Host "  - Run: php artisan route:cache" -ForegroundColor White
    Write-Host "  - Run: php artisan view:cache" -ForegroundColor White
}
