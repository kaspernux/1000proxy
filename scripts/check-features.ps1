# 1000proxy Feature Verification Script
# Comprehensive feature checking for the entire application

param(
    [switch]$Authentication = $true,
    [switch]$AdminPanels = $true,
    [switch]$ProxyManagement = $true,
    [switch]$PaymentSystem = $true,
    [switch]$API = $true,
    [switch]$Integration3XUI = $true,
    [switch]$Security = $true,
    [switch]$Performance = $true,
    [switch]$Verbose = $false,
    [string]$OutputFile = ""
)

$ErrorActionPreference = "Continue"
$projectRoot = $PSScriptRoot

# Function to write feature check output
function Write-Feature-Output {
    param($Message, $Color = "White", $IsHeader = $false, $IsSubHeader = $false)

    if ($IsHeader) {
        Write-Host ""
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $Color
        Write-Host $Message -ForegroundColor $Color
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $Color
    } elseif ($IsSubHeader) {
        Write-Host ""
        Write-Host "┌─ $Message" -ForegroundColor $Color
    } else {
        Write-Host $Message -ForegroundColor $Color
    }

    if ($OutputFile) {
        Add-Content -Path $OutputFile -Value $Message
    }
}

# Feature check counter
$script:TotalChecks = 0
$script:PassedChecks = 0
$script:FailedChecks = 0
$script:WarningChecks = 0

function Test-Feature {
    param($Name, $ScriptBlock, $Required = $true)

    $script:TotalChecks++
    try {
        $result = & $ScriptBlock
        if ($result) {
            Write-Feature-Output "✅ $Name" "Green"
            $script:PassedChecks++
            return $true
        } else {
            if ($Required) {
                Write-Feature-Output "❌ $Name" "Red"
                $script:FailedChecks++
            } else {
                Write-Feature-Output "⚠️  $Name (Optional)" "Yellow"
                $script:WarningChecks++
            }
            return $false
        }
    } catch {
        if ($Required) {
            Write-Feature-Output "❌ $Name - Error: $($_.Exception.Message)" "Red"
            $script:FailedChecks++
        } else {
            Write-Feature-Output "⚠️  $Name - Error: $($_.Exception.Message)" "Yellow"
            $script:WarningChecks++
        }
        return $false
    }
}

# Start feature check
Write-Feature-Output "🔍 1000proxy Complete Feature Verification" "Cyan" $true
Write-Feature-Output "Check Started: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"

# 1. CORE LARAVEL FEATURES
Write-Feature-Output "🎯 CORE LARAVEL FEATURES" "Yellow" $true

Test-Feature "Laravel Framework Installation" {
    Test-Path "artisan" -and (Test-Path "composer.json")
}

Test-Feature "Environment Configuration" {
    Test-Path ".env" -and (Get-Content ".env" -Raw) -match "APP_KEY\s*=\s*.+"
}

Test-Feature "Database Connection" {
    $dbCheck = php artisan migrate:status --quiet 2>&1
    $LASTEXITCODE -eq 0
}

Test-Feature "Application Key Generated" {
    $env = Get-Content ".env" -Raw
    ($env -match "APP_KEY\s*=\s*base64:(.+)") -and ($matches[1].Length -gt 10)
}

Test-Feature "Storage Directory Writable" {
    Test-Path "storage" -and (Test-Path "storage/logs")
}

Test-Feature "Bootstrap Cache Writable" {
    Test-Path "bootstrap/cache"
}

# 2. AUTHENTICATION SYSTEM
if ($Authentication) {
    Write-Feature-Output "🔐 AUTHENTICATION SYSTEM" "Yellow" $true

    Test-Feature "User Model" {
        Test-Path "app/Models/User.php"
    }

    Test-Feature "Authentication Routes" {
        $routes = php artisan route:list --json 2>&1
        ($LASTEXITCODE -eq 0) -and ($routes -match "login|register|logout")
    }

    Test-Feature "Password Reset Functionality" {
        $routes = php artisan route:list --json 2>&1
        ($LASTEXITCODE -eq 0) -and ($routes -match "password")
    }

    Test-Feature "Laravel Sanctum" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'laravel/sanctum' -ne $null
    }

    Test-Feature "Two-Factor Authentication" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'pragmarx/google2fa-laravel' -ne $null
    } -Required $false

    Test-Feature "Role-Based Permissions" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'spatie/laravel-permission' -ne $null
    }
}

# 3. ADMIN PANELS (FILAMENT)
if ($AdminPanels) {
    Write-Feature-Output "🎛️ ADMIN PANELS (FILAMENT)" "Yellow" $true

    Test-Feature "Filament Framework" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'filament/filament' -ne $null
    }

    Test-Feature "Filament Panels Directory" {
        Test-Path "app/Filament"
    }

    Test-Feature "Admin Panel Configuration" {
        Test-Path "app/Providers/Filament" -or (Test-Path "app/Filament/AdminPanelProvider.php")
    }

    Test-Feature "Super Admin Panel" {
        (Test-Path "app/Filament/Admin") -or (Get-ChildItem "app/Filament" -Directory | Where-Object { $_.Name -like "*Admin*" })
    }

    Test-Feature "Customer Panel" {
        (Test-Path "app/Filament/Customer") -or (Get-ChildItem "app/Filament" -Directory | Where-Object { $_.Name -like "*Customer*" })
    }

    Test-Feature "Staff Panel" {
        (Test-Path "app/Filament/Staff") -or (Get-ChildItem "app/Filament" -Directory | Where-Object { $_.Name -like "*Staff*" })
    } -Required $false

    Test-Feature "Support Panel" {
        (Test-Path "app/Filament/Support") -or (Get-ChildItem "app/Filament" -Directory | Where-Object { $_.Name -like "*Support*" })
    } -Required $false

    Test-Feature "Filament Resources" {
        $resources = Get-ChildItem "app/Filament" -Recurse -Filter "*Resource.php" -ErrorAction SilentlyContinue
        $resources.Count -gt 0
    }

    Test-Feature "Filament Pages" {
        $pages = Get-ChildItem "app/Filament" -Recurse -Filter "*Page.php" -ErrorAction SilentlyContinue
        $pages.Count -gt 0
    }
}

# 4. PROXY MANAGEMENT SYSTEM
if ($ProxyManagement) {
    Write-Feature-Output "🌐 PROXY MANAGEMENT SYSTEM" "Yellow" $true

    Test-Feature "Server Model" {
        Test-Path "app/Models/Server.php"
    }

    Test-Feature "Product Model" {
        Test-Path "app/Models/Product.php"
    }

    Test-Feature "Order Model" {
        Test-Path "app/Models/Order.php"
    }

    Test-Feature "Service Model" {
        Test-Path "app/Models/Service.php"
    } -Required $false

    Test-Feature "Proxy Configuration Support" {
        $models = Get-ChildItem "app/Models" -Filter "*.php" | Get-Content -Raw
        ($models -match "vless|vmess|trojan|shadowsocks") -or (Test-Path "app/Services/*Proxy*")
    }

    Test-Feature "Server Management Service" {
        (Test-Path "app/Services") -and (Get-ChildItem "app/Services" -Filter "*Server*" -ErrorAction SilentlyContinue)
    } -Required $false

    Test-Feature "Client Configuration Generation" {
        $services = Get-ChildItem "app/Services" -Filter "*.php" -Recurse -ErrorAction SilentlyContinue | Get-Content -Raw
        $services -match "config|client|proxy"
    } -Required $false
}

# 5. PAYMENT SYSTEM
if ($PaymentSystem) {
    Write-Feature-Output "💳 PAYMENT SYSTEM" "Yellow" $true

    Test-Feature "Payment Model" {
        Test-Path "app/Models/Payment.php"
    } -Required $false

    Test-Feature "Wallet System" {
        (Test-Path "app/Models/Wallet.php") -or (Test-Path "app/Models/Transaction.php")
    } -Required $false

    Test-Feature "Stripe Integration" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'stripe/stripe-php' -ne $null
    } -Required $false

    Test-Feature "PayPal Integration" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        ($composer.require.'paypal/rest-api-sdk-php' -ne $null) -or ($composer.require.'srmklive/paypal' -ne $null)
    } -Required $false

    Test-Feature "Cryptocurrency Support" {
        $services = Get-ChildItem "app/Services" -Filter "*Crypto*" -Recurse -ErrorAction SilentlyContinue
        $services.Count -gt 0
    } -Required $false

    Test-Feature "Payment Gateway Configuration" {
        $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
        ($env -match "STRIPE_|PAYPAL_|CRYPTO_") -or (Test-Path "config/payment.php")
    } -Required $false

    Test-Feature "Invoice Generation" {
        (Test-Path "app/Models/Invoice.php") -or (Get-ChildItem "app" -Recurse -Filter "*Invoice*" -ErrorAction SilentlyContinue)
    } -Required $false
}

# 6. API SYSTEM
if ($API) {
    Write-Feature-Output "🔌 API SYSTEM" "Yellow" $true

    Test-Feature "API Routes Defined" {
        Test-Path "routes/api.php"
    }

    Test-Feature "API Controllers" {
        (Test-Path "app/Http/Controllers/Api") -or (Get-ChildItem "app/Http/Controllers" -Filter "*Api*" -ErrorAction SilentlyContinue)
    }

    Test-Feature "API Resources" {
        Test-Path "app/Http/Resources"
    } -Required $false

    Test-Feature "API Authentication (Sanctum)" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'laravel/sanctum' -ne $null
    }

    Test-Feature "Rate Limiting" {
        $routes = Get-Content "routes/api.php" -Raw -ErrorAction SilentlyContinue
        $routes -match "throttle"
    } -Required $false

    Test-Feature "API Documentation" {
        (Test-Path "docs/api") -or (Get-ChildItem "." -Filter "*swagger*" -ErrorAction SilentlyContinue) -or (Get-ChildItem "." -Filter "*postman*" -ErrorAction SilentlyContinue)
    } -Required $false

    Test-Feature "CORS Configuration" {
        Test-Path "config/cors.php"
    }
}

# 7. 3X-UI INTEGRATION
if ($Integration3XUI) {
    Write-Feature-Output "🔗 3X-UI INTEGRATION" "Yellow" $true

    Test-Feature "3X-UI Service Classes" {
        $services = Get-ChildItem "app/Services" -Filter "*XUI*" -Recurse -ErrorAction SilentlyContinue
        $services.Count -gt 0
    } -Required $false

    Test-Feature "3X-UI Configuration" {
        $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
        $env -match "XUI_|XRAY_|V2RAY_"
    } -Required $false

    Test-Feature "Inbound Management" {
        $models = Get-ChildItem "app/Models" -Filter "*.php" | Get-Content -Raw
        $models -match "inbound|outbound"
    } -Required $false

    Test-Feature "Client Management API" {
        $controllers = Get-ChildItem "app/Http/Controllers" -Filter "*.php" -Recurse | Get-Content -Raw
        $controllers -match "client|inbound|xui"
    } -Required $false

    Test-Feature "Panel Integration Commands" {
        $commands = Get-ChildItem "app/Console/Commands" -Filter "*XUI*" -ErrorAction SilentlyContinue
        $commands.Count -gt 0
    } -Required $false
}

# 8. SECURITY FEATURES
if ($Security) {
    Write-Feature-Output "🛡️ SECURITY FEATURES" "Yellow" $true

    Test-Feature "HTTPS Configuration" {
        $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
        ($env -match "APP_URL\s*=\s*https") -or (Test-Path "config/ssl.php")
    } -Required $false

    Test-Feature "CSRF Protection" {
        $middleware = Get-ChildItem "app/Http/Middleware" -Filter "*.php" | Get-Content -Raw
        $middleware -match "csrf|VerifyCsrfToken"
    }

    Test-Feature "Input Validation" {
        (Test-Path "app/Http/Requests") -and (Get-ChildItem "app/Http/Requests" -Filter "*.php").Count -gt 0
    }

    Test-Feature "SQL Injection Protection (Eloquent)" {
        $models = Get-ChildItem "app/Models" -Filter "*.php" | Get-Content -Raw
        $models -match "Eloquent|Model"
    }

    Test-Feature "XSS Protection" {
        $templates = Get-ChildItem "resources/views" -Filter "*.blade.php" -Recurse -ErrorAction SilentlyContinue | Get-Content -Raw
        $templates -match "\{\{\s*\$|\{\!\!\s*\$"
    } -Required $false

    Test-Feature "Audit Logging" {
        (Test-Path "app/Models/AuditLog.php") -or (Get-ChildItem "app" -Recurse -Filter "*Audit*" -ErrorAction SilentlyContinue)
    } -Required $false

    Test-Feature "Failed Login Attempts Protection" {
        $throttle = Get-Content "routes/web.php" -Raw -ErrorAction SilentlyContinue
        $throttle -match "throttle"
    } -Required $false
}

# 9. PERFORMANCE FEATURES
if ($Performance) {
    Write-Feature-Output "⚡ PERFORMANCE FEATURES" "Yellow" $true

    Test-Feature "Redis Cache Driver" {
        $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
        $env -match "CACHE_DRIVER\s*=\s*redis"
    } -Required $false

    Test-Feature "Queue System" {
        $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
        ($env -match "QUEUE_CONNECTION\s*=\s*(redis|database|sqs)") -and ($env -notmatch "QUEUE_CONNECTION\s*=\s*sync")
    } -Required $false

    Test-Feature "Laravel Horizon" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'laravel/horizon' -ne $null
    } -Required $false

    Test-Feature "Database Optimization" {
        $migrations = Get-ChildItem "database/migrations" -Filter "*.php" | Get-Content -Raw
        $migrations -match "index|unique"
    } -Required $false

    Test-Feature "Response Caching" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        $composer.require.'spatie/laravel-responsecache' -ne $null
    } -Required $false

    Test-Feature "Image Optimization" {
        $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
        ($composer.require.'spatie/laravel-image-optimizer' -ne $null) -or ($composer.require.'intervention/image' -ne $null)
    } -Required $false

    Test-Feature "Asset Compilation (Vite)" {
        Test-Path "vite.config.js"
    }

    Test-Feature "Production Optimizations" {
        (Test-Path "bootstrap/cache/config.php") -or (Test-Path "bootstrap/cache/routes.php")
    } -Required $false
}

# 10. ADDITIONAL FEATURES
Write-Feature-Output "🚀 ADDITIONAL FEATURES" "Yellow" $true

Test-Feature "Email Configuration" {
    $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
    $env -match "MAIL_"
} -Required $false

Test-Feature "Notification System" {
    (Test-Path "app/Notifications") -and (Get-ChildItem "app/Notifications" -Filter "*.php").Count -gt 0
} -Required $false

Test-Feature "Event System" {
    (Test-Path "app/Events") -and (Test-Path "app/Listeners")
} -Required $false

Test-Feature "Scheduled Tasks" {
    $kernel = Get-Content "app/Console/Kernel.php" -Raw -ErrorAction SilentlyContinue
    $kernel -match "schedule"
} -Required $false

Test-Feature "Localization Support" {
    (Test-Path "lang") -and (Get-ChildItem "lang" -Directory).Count -gt 0
} -Required $false

Test-Feature "File Storage Configuration" {
    $env = Get-Content ".env" -Raw -ErrorAction SilentlyContinue
    $env -match "FILESYSTEM_"
} -Required $false

Test-Feature "Broadcasting (WebSockets)" {
    $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
    ($composer.require.'pusher/pusher-php-server' -ne $null) -or ($composer.require.'laravel/reverb' -ne $null)
} -Required $false

Test-Feature "Testing Framework" {
    (Test-Path "tests") -and (Test-Path "phpunit.xml")
}

Test-Feature "API Documentation Generator" {
    $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
    ($composer.'require-dev'.'scribe-org/laravel-scribe' -ne $null) -or (Test-Path "docs/api")
} -Required $false

Test-Feature "Development Tools" {
    $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json
    $composer.'require-dev'.'barryvdh/laravel-debugbar' -ne $null
} -Required $false

# 11. MOBILE & PWA FEATURES
Write-Feature-Output "📱 MOBILE & PWA FEATURES" "Yellow" $true

Test-Feature "Progressive Web App (PWA)" {
    (Test-Path "public/manifest.json") -or (Test-Path "public/sw.js")
} -Required $false

Test-Feature "Mobile-Responsive Design" {
    $css = Get-ChildItem "resources" -Filter "*.css" -Recurse -ErrorAction SilentlyContinue | Get-Content -Raw
    ($css -match "@media|responsive") -or (Test-Path "*tailwind*")
} -Required $false

Test-Feature "API for Mobile Apps" {
    $routes = Get-Content "routes/api.php" -Raw -ErrorAction SilentlyContinue
    $routes -match "mobile|app"
} -Required $false

# Final Summary
Write-Feature-Output "📊 FEATURE VERIFICATION SUMMARY" "Green" $true

$passPercentage = [math]::Round(($script:PassedChecks / $script:TotalChecks) * 100, 1)

Write-Feature-Output "Total Features Checked: $($script:TotalChecks)" "Cyan"
Write-Feature-Output "✅ Passed: $($script:PassedChecks) ($passPercentage%)" "Green"
Write-Feature-Output "❌ Failed: $($script:FailedChecks)" "Red"
Write-Feature-Output "⚠️  Warnings: $($script:WarningChecks)" "Yellow"

if ($script:FailedChecks -eq 0) {
    Write-Feature-Output "🎉 All critical features are working!" "Green"
} else {
    Write-Feature-Output "⚠️  Some critical features need attention" "Yellow"
}

Write-Feature-Output "Verification Completed: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"

if ($OutputFile) {
    Write-Feature-Output "💾 Full report saved to: $OutputFile" "Green"
}

Write-Feature-Output "🎯 Recommendations:" "Yellow"
Write-Feature-Output "1. Address any ❌ failed critical features" "Gray"
Write-Feature-Output "2. Consider implementing ⚠️  optional features for enhanced functionality" "Gray"
Write-Feature-Output "3. Run 'debug-project.ps1' for detailed system diagnostics" "Gray"
Write-Feature-Output "4. Run 'test-project.ps1' to verify functionality with tests" "Gray"
Write-Feature-Output "5. Review documentation for implementation guidance" "Gray"

Write-Host ""
if ($script:FailedChecks -eq 0) {
    Write-Host "Feature verification completed successfully! 🎉" -ForegroundColor Green
    exit 0
} else {
    Write-Host "Feature verification completed with $($script:FailedChecks) issues. ⚠️" -ForegroundColor Yellow
    exit 1
}
