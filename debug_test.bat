@echo off
echo === 1000proxy Laravel Project Debug Test ===
echo Date: %DATE% %TIME%
echo Working Directory: %CD%
echo.

echo === Checking Project Structure ===
echo Checking app directory...
if exist "app" (
    echo   - app\ directory exists
    dir /s /b app\Http\Controllers\*.php > nul 2>&1 && echo   - Controllers found
    dir /s /b app\Models\*.php > nul 2>&1 && echo   - Models found
    dir /s /b app\Services\*.php > nul 2>&1 && echo   - Services found
) else (
    echo   X app\ directory not found
)

echo.
echo Checking configuration files...
if exist "composer.json" (
    echo   - composer.json exists
) else (
    echo   X composer.json not found
)

if exist "package.json" (
    echo   - package.json exists
) else (
    echo   X package.json not found
)

if exist ".env" (
    echo   - .env exists
) else (
    echo   X .env not found
)

echo.
echo Checking database...
if exist "database\database.sqlite" (
    echo   - SQLite database file exists
    for %%A in (database\database.sqlite) do echo   - Database size: %%~zA bytes
) else (
    echo   X SQLite database file not found
)

echo.
echo Checking migrations...
if exist "database\migrations" (
    echo   - Migrations directory exists
    dir /b database\migrations\*.php > nul 2>&1 && echo   - Migration files found
) else (
    echo   X Migrations directory not found
)

echo.
echo Checking key files...
if exist "app\Services\MonitoringService.php" (
    echo   - app\Services\MonitoringService.php exists
) else (
    echo   X app\Services\MonitoringService.php not found
)

if exist "app\Console\Commands\HealthCheckCommand.php" (
    echo   - app\Console\Commands\HealthCheckCommand.php exists
) else (
    echo   X app\Console\Commands\HealthCheckCommand.php not found
)

if exist "app\Providers\AppServiceProvider.php" (
    echo   - app\Providers\AppServiceProvider.php exists
) else (
    echo   X app\Providers\AppServiceProvider.php not found
)

echo.
echo === Environment Configuration ===
if exist ".env" (
    findstr "^APP_ENV=" .env
    findstr "^APP_DEBUG=" .env
    findstr "^DB_CONNECTION=" .env
    findstr "^CACHE_DRIVER=" .env
    findstr "^QUEUE_CONNECTION=" .env
)

echo.
echo === Test Complete ===
echo Project structure validation finished.
echo Next steps: Run 'php artisan migrate' and 'php artisan serve' to start the application.
pause
