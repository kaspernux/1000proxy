#!/bin/bash
# Simple test script to verify Laravel application structure

echo "=== 1000proxy Laravel Project Debug Test ==="
echo "Date: $(date)"
echo "Working Directory: $(pwd)"
echo

echo "=== Checking Project Structure ==="
echo "✓ Checking app directory..."
if [ -d "app" ]; then
    echo "  - app/ directory exists"
    echo "  - Controllers: $(find app/Http/Controllers -name '*.php' | wc -l) files"
    echo "  - Models: $(find app/Models -name '*.php' | wc -l) files"
    echo "  - Services: $(find app/Services -name '*.php' | wc -l) files"
else
    echo "  ✗ app/ directory not found"
fi

echo
echo "✓ Checking configuration files..."
if [ -f "composer.json" ]; then
    echo "  - composer.json exists"
else
    echo "  ✗ composer.json not found"
fi

if [ -f "package.json" ]; then
    echo "  - package.json exists"
else
    echo "  ✗ package.json not found"
fi

if [ -f ".env" ]; then
    echo "  - .env exists"
else
    echo "  ✗ .env not found"
fi

echo
echo "✓ Checking database..."
if [ -f "database/database.sqlite" ]; then
    echo "  - SQLite database file exists"
    echo "  - Database size: $(du -h database/database.sqlite | cut -f1)"
else
    echo "  ✗ SQLite database file not found"
fi

echo
echo "✓ Checking migrations..."
if [ -d "database/migrations" ]; then
    echo "  - Migration files: $(find database/migrations -name '*.php' | wc -l)"
else
    echo "  ✗ Migrations directory not found"
fi

echo
echo "✓ Checking key files..."
for file in "app/Services/MonitoringService.php" "app/Console/Commands/HealthCheckCommand.php" "app/Providers/AppServiceProvider.php"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file exists"
    else
        echo "  ✗ $file not found"
    fi
done

echo
echo "=== Environment Configuration ==="
if [ -f ".env" ]; then
    echo "APP_ENV=$(grep '^APP_ENV=' .env | cut -d'=' -f2)"
    echo "APP_DEBUG=$(grep '^APP_DEBUG=' .env | cut -d'=' -f2)"
    echo "DB_CONNECTION=$(grep '^DB_CONNECTION=' .env | cut -d'=' -f2)"
    echo "CACHE_DRIVER=$(grep '^CACHE_DRIVER=' .env | cut -d'=' -f2)"
    echo "QUEUE_CONNECTION=$(grep '^QUEUE_CONNECTION=' .env | cut -d'=' -f2)"
fi

echo
echo "=== Test Complete ==="
echo "Project structure validation finished."
echo "Next steps: Run 'php artisan migrate' and 'php artisan serve' to start the application."
