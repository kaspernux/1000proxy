# 1000proxy Complete Test Script
# Comprehensive testing for the entire Laravel application

param(
    [switch]$Unit = $true,
    [switch]$Feature = $true,
    [switch]$Browser = $false,
    [switch]$API = $true,
    [switch]$Coverage = $false,
    [string]$Filter = "",
    [string]$TestSuite = "",
    [switch]$Verbose = $false,
    [switch]$StopOnFailure = $false
)

$ErrorActionPreference = "Continue"
$projectRoot = $PSScriptRoot

# Function to write test output
function Write-Test-Output {
    param($Message, $Color = "White", $IsHeader = $false)

    if ($IsHeader) {
        Write-Host ""
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $Color
        Write-Host $Message -ForegroundColor $Color
        Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $Color
    } else {
        Write-Host $Message -ForegroundColor $Color
    }
}

# Start test report
Write-Test-Output "🧪 1000proxy Complete Test Suite" "Cyan" $true
Write-Test-Output "Test Started: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"

# Verify test environment
Write-Test-Output "🔍 PRE-TEST ENVIRONMENT CHECK" "Yellow" $true

try {
    # Check if we're in a Laravel project
    if (!(Test-Path "artisan")) {
        Write-Test-Output "❌ Not in a Laravel project directory" "Red"
        exit 1
    }

    # Check if PHPUnit is available
    $phpunitCheck = php artisan test --help 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Test-Output "❌ PHPUnit not available. Run 'composer install' first." "Red"
        exit 1
    }

    Write-Test-Output "✅ Laravel project detected" "Green"
    Write-Test-Output "✅ PHPUnit available" "Green"

    # Check test database configuration
    if (Test-Path ".env.testing") {
        Write-Test-Output "✅ Testing environment file found" "Green"
    } else {
        Write-Test-Output "⚠️  No .env.testing file found, using default .env" "Yellow"
    }

    # Check if tests directory exists
    if (Test-Path "tests") {
        $testFiles = Get-ChildItem "tests" -Recurse -Filter "*.php" | Measure-Object
        Write-Test-Output "✅ Tests directory found with $($testFiles.Count) test files" "Green"
    } else {
        Write-Test-Output "❌ Tests directory not found" "Red"
        exit 1
    }

} catch {
    Write-Test-Output "❌ Environment check failed: $($_.Exception.Message)" "Red"
    exit 1
}

# Build PHPUnit command
$phpunitCommand = "php artisan test"
$testTypes = @()

# Add test type filters
if ($Unit -and !$Feature -and !$Browser) {
    $phpunitCommand += " --testsuite=Unit"
    $testTypes += "Unit Tests"
} elseif ($Feature -and !$Unit -and !$Browser) {
    $phpunitCommand += " --testsuite=Feature"
    $testTypes += "Feature Tests"
} elseif ($Browser -and !$Unit -and !$Feature) {
    $phpunitCommand += " --testsuite=Browser"
    $testTypes += "Browser Tests"
}

# Add custom test suite
if ($TestSuite) {
    $phpunitCommand += " --testsuite=$TestSuite"
    $testTypes += "Custom Suite: $TestSuite"
}

# Add filter
if ($Filter) {
    $phpunitCommand += " --filter=$Filter"
    $testTypes += "Filtered: $Filter"
}

# Add coverage
if ($Coverage) {
    $phpunitCommand += " --coverage-html storage/coverage"
    $testTypes += "with Coverage"
}

# Add verbose
if ($Verbose) {
    $phpunitCommand += " --verbose"
}

# Add stop on failure
if ($StopOnFailure) {
    $phpunitCommand += " --stop-on-failure"
}

if ($testTypes.Count -eq 0) {
    $testTypes += "All Tests"
}

Write-Test-Output "🎯 RUNNING: $($testTypes -join ', ')" "Cyan"

# 1. DATABASE PREPARATION
Write-Test-Output "🗄️ DATABASE PREPARATION" "Yellow" $true

try {
    # Refresh test database
    Write-Test-Output "Refreshing test database..." "Cyan"
    $dbRefresh = php artisan migrate:fresh --seed --env=testing 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Test-Output "✅ Test database refreshed successfully" "Green"
    } else {
        Write-Test-Output "❌ Failed to refresh test database" "Red"
        Write-Test-Output "Error: $dbRefresh" "Red"
    }
} catch {
    Write-Test-Output "❌ Database preparation error: $($_.Exception.Message)" "Red"
}

# 2. CACHE CLEARING
Write-Test-Output "🧹 CLEARING CACHES" "Yellow" $true

try {
    $cacheCommands = @(
        @{Name="Config Cache"; Command="config:clear"},
        @{Name="Route Cache"; Command="route:clear"},
        @{Name="View Cache"; Command="view:clear"},
        @{Name="Application Cache"; Command="cache:clear"}
    )

    foreach ($cache in $cacheCommands) {
        $result = php artisan $cache.Command 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Test-Output "✅ $($cache.Name) cleared" "Green"
        } else {
            Write-Test-Output "⚠️  $($cache.Name) clear failed: $result" "Yellow"
        }
    }
} catch {
    Write-Test-Output "❌ Cache clearing error: $($_.Exception.Message)" "Red"
}

# 3. RUN TESTS
Write-Test-Output "🧪 EXECUTING TEST SUITE" "Yellow" $true

try {
    Write-Test-Output "Command: $phpunitCommand" "Cyan"
    Write-Test-Output "Starting tests..." "Cyan"

    # Execute the tests
    $testStartTime = Get-Date
    $testOutput = Invoke-Expression $phpunitCommand 2>&1
    $testEndTime = Get-Date
    $testDuration = $testEndTime - $testStartTime

    # Display test output
    $testOutput | ForEach-Object {
        $line = $_.ToString()
        if ($line -match "PASSED|OK") {
            Write-Host $line -ForegroundColor Green
        } elseif ($line -match "FAILED|ERROR|FAILURES") {
            Write-Host $line -ForegroundColor Red
        } elseif ($line -match "WARNING") {
            Write-Host $line -ForegroundColor Yellow
        } else {
            Write-Host $line
        }
    }

    Write-Test-Output "Test Duration: $($testDuration.TotalSeconds) seconds" "Cyan"

    if ($LASTEXITCODE -eq 0) {
        Write-Test-Output "✅ All tests passed!" "Green"
    } else {
        Write-Test-Output "❌ Some tests failed (Exit code: $LASTEXITCODE)" "Red"
    }

} catch {
    Write-Test-Output "❌ Test execution error: $($_.Exception.Message)" "Red"
}

# 4. API ENDPOINT TESTING (if API flag is set)
if ($API) {
    Write-Test-Output "🌐 API ENDPOINT TESTING" "Yellow" $true

    try {
        # Start Laravel server for API testing
        Write-Test-Output "Starting Laravel development server..." "Cyan"
        $serverJob = Start-Job -ScriptBlock {
            Set-Location $using:projectRoot
            php artisan serve --host=127.0.0.1 --port=8001 --quiet
        }

        Start-Sleep 3  # Wait for server to start

        # Test API endpoints
        $apiEndpoints = @(
            @{Url="http://127.0.0.1:8001"; Name="Home Page"},
            @{Url="http://127.0.0.1:8001/api/health"; Name="Health Check"},
            @{Url="http://127.0.0.1:8001/admin"; Name="Admin Panel"},
            @{Url="http://127.0.0.1:8001/customer"; Name="Customer Panel"}
        )

        foreach ($endpoint in $apiEndpoints) {
            try {
                $response = Invoke-WebRequest -Uri $endpoint.Url -TimeoutSec 10 -UseBasicParsing 2>$null
                if ($response.StatusCode -eq 200) {
                    Write-Test-Output "✅ $($endpoint.Name): OK (Status: $($response.StatusCode))" "Green"
                } else {
                    Write-Test-Output "⚠️  $($endpoint.Name): Status $($response.StatusCode)" "Yellow"
                }
            } catch {
                Write-Test-Output "❌ $($endpoint.Name): Failed - $($_.Exception.Message)" "Red"
            }
        }

        # Stop server
        Stop-Job $serverJob -Force
        Remove-Job $serverJob -Force

    } catch {
        Write-Test-Output "❌ API testing error: $($_.Exception.Message)" "Red"
    }
}

# 5. COVERAGE REPORT (if coverage was requested)
if ($Coverage) {
    Write-Test-Output "📊 COVERAGE REPORT" "Yellow" $true

    try {
        if (Test-Path "storage/coverage") {
            Write-Test-Output "✅ Coverage report generated in storage/coverage/" "Green"

            # Try to open coverage report
            $indexFile = "storage/coverage/index.html"
            if (Test-Path $indexFile) {
                Write-Test-Output "📁 Coverage report: file:///$($projectRoot -replace '\\', '/')/$indexFile" "Cyan"
            }
        } else {
            Write-Test-Output "❌ Coverage report not generated" "Red"
        }
    } catch {
        Write-Test-Output "❌ Coverage report error: $($_.Exception.Message)" "Red"
    }
}

# 6. TEST ANALYSIS
Write-Test-Output "📋 TEST ANALYSIS" "Yellow" $true

try {
    # Count test files by type
    $unitTests = Get-ChildItem "tests/Unit" -Recurse -Filter "*.php" -ErrorAction SilentlyContinue | Measure-Object
    $featureTests = Get-ChildItem "tests/Feature" -Recurse -Filter "*.php" -ErrorAction SilentlyContinue | Measure-Object
    $browserTests = Get-ChildItem "tests/Browser" -Recurse -Filter "*.php" -ErrorAction SilentlyContinue | Measure-Object

    Write-Test-Output "📊 Test File Count:" "Cyan"
    Write-Test-Output "  • Unit Tests: $($unitTests.Count)" "Gray"
    Write-Test-Output "  • Feature Tests: $($featureTests.Count)" "Gray"
    Write-Test-Output "  • Browser Tests: $($browserTests.Count)" "Gray"
    Write-Test-Output "  • Total: $(($unitTests.Count + $featureTests.Count + $browserTests.Count))" "Cyan"

    # Check for test configuration files
    $configFiles = @(
        @{File="phpunit.xml"; Name="PHPUnit Configuration"},
        @{File="tests/TestCase.php"; Name="Base Test Case"},
        @{File="tests/CreatesApplication.php"; Name="Application Creator"}
    )

    foreach ($config in $configFiles) {
        if (Test-Path $config.File) {
            Write-Test-Output "✅ $($config.Name): Found" "Green"
        } else {
            Write-Test-Output "❌ $($config.Name): Missing" "Red"
        }
    }

} catch {
    Write-Test-Output "❌ Test analysis error: $($_.Exception.Message)" "Red"
}

# 7. PERFORMANCE METRICS
Write-Test-Output "⚡ PERFORMANCE METRICS" "Yellow" $true

try {
    # Check for performance-related packages
    $composer = Get-Content "composer.json" -Raw | ConvertFrom-Json

    $performancePackages = @(
        "laravel/horizon",
        "spatie/laravel-query-builder",
        "spatie/laravel-responsecache",
        "barryvdh/laravel-debugbar"
    )

    foreach ($package in $performancePackages) {
        if ($composer.require.$package -or $composer.'require-dev'.$package) {
            Write-Test-Output "✅ Performance Package: $package" "Green"
        } else {
            Write-Test-Output "❌ Performance Package: $package not installed" "Yellow"
        }
    }

    # Check for queue configuration
    if (Test-Path ".env") {
        $envContent = Get-Content ".env" -Raw
        if ($envContent -match "QUEUE_CONNECTION\s*=\s*(.+)") {
            Write-Test-Output "✅ Queue Connection: $($matches[1])" "Green"
        }
    }

} catch {
    Write-Test-Output "❌ Performance metrics error: $($_.Exception.Message)" "Red"
}

# Final Summary
Write-Test-Output "📊 TEST SUMMARY" "Green" $true
Write-Test-Output "Test Completed: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"

if ($LASTEXITCODE -eq 0) {
    Write-Test-Output "🎉 All tests completed successfully!" "Green"
} else {
    Write-Test-Output "⚠️  Some tests failed. Check output above for details." "Yellow"
}

Write-Test-Output "🎯 Next Steps:" "Yellow"
Write-Test-Output "1. Review any failing tests and fix issues" "Gray"
Write-Test-Output "2. Run 'debug-project.ps1' if you encounter errors" "Gray"
Write-Test-Output "3. Use 'check-features.ps1' to validate specific features" "Gray"
Write-Test-Output "4. Consider adding more tests for better coverage" "Gray"

if ($Coverage) {
    Write-Test-Output "5. Review coverage report for untested code" "Gray"
}

Write-Host ""
Write-Host "Testing completed! 🎉" -ForegroundColor Green

exit $LASTEXITCODE
