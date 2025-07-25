# 1000proxy Complete Debug Script
# Comprehensive debugging for the entire Laravel application

param(
    [switch]$Verbose = $false,
    [switch]$CheckDatabase = $true,
    [switch]$CheckServices = $true,
    [switch]$CheckAPI = $true,
    [string]$OutputFile = ""
)

$ErrorActionPreference = "Continue"
$projectRoot = $PSScriptRoot

# Function to write output
function Write-Output-Line {
    param($Message, $Color = "White", $IsHeader = $false)

    if ($IsHeader) {
        Write-Host ""
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $Color
        Write-Host $Message -ForegroundColor $Color
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $Color
    } else {
        Write-Host $Message -ForegroundColor $Color
    }

    if ($OutputFile) {
        Add-Content -Path $OutputFile -Value $Message
    }
}

# Start debug report
Write-Output-Line "🔍 1000proxy Complete System Debug Report" "Cyan" $true
Write-Output-Line "Report Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"

# 1. SYSTEM ENVIRONMENT CHECK
Write-Output-Line "🏥 SYSTEM ENVIRONMENT CHECK" "Yellow" $true

try {
    # PHP Version
    $phpVersion = php --version 2>$null | Select-Object -First 1
    if ($phpVersion) {
        Write-Output-Line "✅ PHP: $phpVersion" "Green"
    } else {
        Write-Output-Line "❌ PHP: Not found or not in PATH" "Red"
    }

    # Composer Version
    $composerVersion = composer --version 2>$null
    if ($composerVersion) {
        Write-Output-Line "✅ Composer: $composerVersion" "Green"
    } else {
        Write-Output-Line "❌ Composer: Not found or not in PATH" "Red"
    }

    # Node.js Version
    $nodeVersion = node --version 2>$null
    if ($nodeVersion) {
        Write-Output-Line "✅ Node.js: $nodeVersion" "Green"
    } else {
        Write-Output-Line "❌ Node.js: Not found or not in PATH" "Red"
    }

    # NPM Version
    $npmVersion = npm --version 2>$null
    if ($npmVersion) {
        Write-Output-Line "✅ NPM: $npmVersion" "Green"
    } else {
        Write-Output-Line "❌ NPM: Not found or not in PATH" "Red"
    }
} catch {
    Write-Output-Line "❌ Error checking system environment: $($_.Exception.Message)" "Red"
}

# 2. LARAVEL PROJECT STATUS
Write-Output-Line "🎯 LARAVEL PROJECT STATUS" "Yellow" $true

try {
    # Check if we're in a Laravel project
    if (Test-Path "artisan") {
        Write-Output-Line "✅ Laravel Project: Detected" "Green"

        # Laravel Version
        $laravelVersion = php artisan --version 2>$null
        if ($laravelVersion) {
            Write-Output-Line "✅ $laravelVersion" "Green"
        }

        # Environment File
        if (Test-Path ".env") {
            Write-Output-Line "✅ Environment: .env file exists" "Green"
        } else {
            Write-Output-Line "❌ Environment: .env file missing" "Red"
        }

        # Vendor Directory
        if (Test-Path "vendor") {
            Write-Output-Line "✅ Dependencies: vendor directory exists" "Green"
        } else {
            Write-Output-Line "❌ Dependencies: vendor directory missing (run composer install)" "Red"
        }

        # Storage Permissions
        if (Test-Path "storage") {
            Write-Output-Line "✅ Storage: Directory exists" "Green"
        } else {
            Write-Output-Line "❌ Storage: Directory missing" "Red"
        }

        # Bootstrap Cache
        if (Test-Path "bootstrap/cache") {
            Write-Output-Line "✅ Bootstrap Cache: Directory exists" "Green"
        } else {
            Write-Output-Line "❌ Bootstrap Cache: Directory missing" "Red"
        }

    } else {
        Write-Output-Line "❌ Laravel Project: Not detected (artisan file missing)" "Red"
    }
} catch {
    Write-Output-Line "❌ Error checking Laravel status: $($_.Exception.Message)" "Red"
}

# 3. CONFIGURATION CHECK
Write-Output-Line "⚙️ CONFIGURATION CHECK" "Yellow" $true

try {
    if (Test-Path ".env") {
        $envContent = Get-Content ".env" -Raw

        # App Debug
        if ($envContent -match "APP_DEBUG\s*=\s*(.+)") {
            Write-Output-Line "✅ APP_DEBUG: $($matches[1])" "Green"
        } else {
            Write-Output-Line "❌ APP_DEBUG: Not configured" "Red"
        }

        # App Environment
        if ($envContent -match "APP_ENV\s*=\s*(.+)") {
            Write-Output-Line "✅ APP_ENV: $($matches[1])" "Green"
        } else {
            Write-Output-Line "❌ APP_ENV: Not configured" "Red"
        }

        # Database Configuration
        if ($envContent -match "DB_CONNECTION\s*=\s*(.+)") {
            Write-Output-Line "✅ DB_CONNECTION: $($matches[1])" "Green"
        } else {
            Write-Output-Line "❌ DB_CONNECTION: Not configured" "Red"
        }

        # Cache Driver
        if ($envContent -match "CACHE_DRIVER\s*=\s*(.+)") {
            Write-Output-Line "✅ CACHE_DRIVER: $($matches[1])" "Green"
        } else {
            Write-Output-Line "❌ CACHE_DRIVER: Not configured" "Red"
        }

        # Queue Driver
        if ($envContent -match "QUEUE_CONNECTION\s*=\s*(.+)") {
            Write-Output-Line "✅ QUEUE_CONNECTION: $($matches[1])" "Green"
        } else {
            Write-Output-Line "❌ QUEUE_CONNECTION: Not configured" "Red"
        }
    }
} catch {
    Write-Output-Line "❌ Error checking configuration: $($_.Exception.Message)" "Red"
}

# 4. DATABASE CHECK
if ($CheckDatabase) {
    Write-Output-Line "🗄️ DATABASE CHECK" "Yellow" $true

    try {
        $dbCheck = php artisan db:show --quiet 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Output-Line "✅ Database Connection: Working" "Green"

            # Check migrations
            $migrationStatus = php artisan migrate:status 2>&1
            if ($LASTEXITCODE -eq 0) {
                Write-Output-Line "✅ Migrations: Status checked successfully" "Green"
            } else {
                Write-Output-Line "❌ Migrations: Error checking status" "Red"
            }
        } else {
            Write-Output-Line "❌ Database Connection: Failed" "Red"
            if ($Verbose) {
                Write-Output-Line "Error: $dbCheck" "Red"
            }
        }
    } catch {
        Write-Output-Line "❌ Database Check Error: $($_.Exception.Message)" "Red"
    }
}

# 5. SERVICES CHECK
if ($CheckServices) {
    Write-Output-Line "🚀 SERVICES CHECK" "Yellow" $true

    try {
        # Check if server is running
        $serverCheck = Test-NetConnection -ComputerName "127.0.0.1" -Port 8000 -InformationLevel Quiet 2>$null
        if ($serverCheck) {
            Write-Output-Line "✅ Laravel Server: Running on port 8000" "Green"
        } else {
            Write-Output-Line "❌ Laravel Server: Not running on port 8000" "Red"
        }

        # Check Redis (if configured)
        try {
            $redisCheck = php artisan tinker --execute="Redis::ping()" 2>&1
            if ($redisCheck -match "PONG" -or $LASTEXITCODE -eq 0) {
                Write-Output-Line "✅ Redis: Connection working" "Green"
            } else {
                Write-Output-Line "❌ Redis: Connection failed or not configured" "Red"
            }
        } catch {
            Write-Output-Line "❌ Redis: Cannot check connection" "Red"
        }

        # Check Queue Workers
        $queueCheck = php artisan queue:work --timeout=1 --sleep=1 --tries=1 --stop-when-empty 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Output-Line "✅ Queue System: Working" "Green"
        } else {
            Write-Output-Line "❌ Queue System: Error or no jobs" "Yellow"
        }

    } catch {
        Write-Output-Line "❌ Services Check Error: $($_.Exception.Message)" "Red"
    }
}

# 6. API ENDPOINTS CHECK
if ($CheckAPI) {
    Write-Output-Line "🌐 API ENDPOINTS CHECK" "Yellow" $true

    try {
        # Check if Laravel server is running
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000" -TimeoutSec 5 -UseBasicParsing 2>$null
        if ($response.StatusCode -eq 200) {
            Write-Output-Line "✅ Web Application: Responding (Status: $($response.StatusCode))" "Green"
        } else {
            Write-Output-Line "❌ Web Application: Error (Status: $($response.StatusCode))" "Red"
        }
    } catch {
        Write-Output-Line "❌ Web Application: Cannot connect (Server not running?)" "Red"
    }

    # Check API routes
    try {
        $apiRoutes = php artisan route:list --json 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Output-Line "✅ API Routes: Listed successfully" "Green"
        } else {
            Write-Output-Line "❌ API Routes: Error listing routes" "Red"
        }
    } catch {
        Write-Output-Line "❌ API Routes Check Error: $($_.Exception.Message)" "Red"
    }
}

# 7. FILAMENT PANELS CHECK
Write-Output-Line "🎛️ FILAMENT PANELS CHECK" "Yellow" $true

try {
    # Check Filament installation
    $filamentCheck = php artisan about --only=filament 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Output-Line "✅ Filament: Installed and working" "Green"
    } else {
        Write-Output-Line "❌ Filament: Not installed or error" "Red"
    }

    # Check for Filament panels
    if (Test-Path "app/Filament") {
        $panelDirs = Get-ChildItem "app/Filament" -Directory
        Write-Output-Line "✅ Filament Panels: $($panelDirs.Count) panels found" "Green"
        foreach ($panel in $panelDirs) {
            Write-Output-Line "  📁 $($panel.Name)" "Cyan"
        }
    } else {
        Write-Output-Line "❌ Filament Panels: No panels directory found" "Red"
    }
} catch {
    Write-Output-Line "❌ Filament Check Error: $($_.Exception.Message)" "Red"
}

# 8. 3X-UI INTEGRATION CHECK
Write-Output-Line "🔗 3X-UI INTEGRATION CHECK" "Yellow" $true

try {
    # Check for 3X-UI related models/services
    $xuiFiles = @()
    if (Test-Path "app/Services") {
        $xuiFiles += Get-ChildItem "app/Services" -Filter "*XUI*" -Recurse
        $xuiFiles += Get-ChildItem "app/Services" -Filter "*3x*" -Recurse
    }

    if ($xuiFiles.Count -gt 0) {
        Write-Output-Line "✅ 3X-UI Integration: Files found ($($xuiFiles.Count))" "Green"
        foreach ($file in $xuiFiles) {
            Write-Output-Line "  📄 $($file.FullName.Replace($projectRoot, '.'))" "Cyan"
        }
    } else {
        Write-Output-Line "❌ 3X-UI Integration: No integration files found" "Red"
    }
} catch {
    Write-Output-Line "❌ 3X-UI Check Error: $($_.Exception.Message)" "Red"
}

# 9. LOG FILES CHECK
Write-Output-Line "📋 LOG FILES CHECK" "Yellow" $true

try {
    if (Test-Path "storage/logs") {
        $logFiles = Get-ChildItem "storage/logs" -Filter "*.log" | Sort-Object LastWriteTime -Descending
        if ($logFiles.Count -gt 0) {
            Write-Output-Line "✅ Log Files: $($logFiles.Count) log files found" "Green"
            $latestLog = $logFiles[0]
            Write-Output-Line "  📄 Latest: $($latestLog.Name) (Modified: $($latestLog.LastWriteTime))" "Cyan"

            # Check for recent errors
            $recentErrors = Get-Content $latestLog.FullName -Tail 50 | Where-Object { $_ -match "ERROR|CRITICAL|EMERGENCY" }
            if ($recentErrors) {
                Write-Output-Line "⚠️  Recent Errors Found: $($recentErrors.Count)" "Yellow"
                if ($Verbose) {
                    foreach ($error in $recentErrors[-5..-1]) {
                        Write-Output-Line "    $error" "Red"
                    }
                }
            } else {
                Write-Output-Line "✅ No recent errors in logs" "Green"
            }
        } else {
            Write-Output-Line "❌ Log Files: No log files found" "Red"
        }
    } else {
        Write-Output-Line "❌ Log Files: logs directory not found" "Red"
    }
} catch {
    Write-Output-Line "❌ Log Files Check Error: $($_.Exception.Message)" "Red"
}

# 10. PERFORMANCE CHECK
Write-Output-Line "⚡ PERFORMANCE CHECK" "Yellow" $true

try {
    # Check for optimization commands
    $optimizations = @(
        @{Name="Config Cache"; Command="config:cache"; Status=$false},
        @{Name="Route Cache"; Command="route:cache"; Status=$false},
        @{Name="View Cache"; Command="view:cache"; Status=$false}
    )

    foreach ($opt in $optimizations) {
        $result = php artisan $opt.Command --help 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Output-Line "✅ $($opt.Name): Available" "Green"
        } else {
            Write-Output-Line "❌ $($opt.Name): Not available" "Red"
        }
    }

    # Check cache directories
    $cacheFiles = @()
    if (Test-Path "bootstrap/cache") {
        $cacheFiles = Get-ChildItem "bootstrap/cache" -File
    }
    Write-Output-Line "📁 Bootstrap Cache Files: $($cacheFiles.Count)" "Cyan"

} catch {
    Write-Output-Line "❌ Performance Check Error: $($_.Exception.Message)" "Red"
}

# 11. SECURITY CHECK
Write-Output-Line "🔒 SECURITY CHECK" "Yellow" $true

try {
    # Check for APP_KEY
    if (Test-Path ".env") {
        $envContent = Get-Content ".env" -Raw
        if ($envContent -match "APP_KEY\s*=\s*(.+)" -and $matches[1] -ne "") {
            Write-Output-Line "✅ APP_KEY: Configured" "Green"
        } else {
            Write-Output-Line "❌ APP_KEY: Not configured (run php artisan key:generate)" "Red"
        }
    }

    # Check for security packages
    $securityPackages = @("spatie/laravel-permission", "laravel/sanctum", "pragmarx/google2fa-laravel")
    $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json

    foreach ($package in $securityPackages) {
        if ($composer.require.$package -or $composer.'require-dev'.$package) {
            Write-Output-Line "✅ Security Package: $package installed" "Green"
        } else {
            Write-Output-Line "❌ Security Package: $package not found" "Yellow"
        }
    }

} catch {
    Write-Output-Line "❌ Security Check Error: $($_.Exception.Message)" "Red"
}

# Final Summary
Write-Output-Line "📊 DEBUG SUMMARY" "Green" $true
Write-Output-Line "Debug completed at: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"

if ($OutputFile) {
    Write-Output-Line "💾 Full report saved to: $OutputFile" "Green"
}

Write-Output-Line "🎯 Recommendations:" "Yellow"
Write-Output-Line "1. Check any ❌ items above and resolve issues" "Gray"
Write-Output-Line "2. Run 'test-project.ps1' to verify functionality" "Gray"
Write-Output-Line "3. Use 'check-features.ps1' to validate all features" "Gray"

Write-Host ""
Write-Host "Debug completed! 🎉" -ForegroundColor Green
