<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

echo "=== Migration Check ===\n";

// Check if columns exist
$columns = [
    'server_brands' => 'display_order',
    'server_categories' => 'display_order',
    'server_plans' => 'display_order'
];

foreach ($columns as $table => $column) {
    $exists = Schema::hasColumn($table, $column);
    echo "Table '{$table}' has column '{$column}': " . ($exists ? 'YES' : 'NO') . "\n";
}

echo "\n=== Running Migrations ===\n";
try {
    Artisan::call('migrate', ['--force' => true]);
    echo "Migrations completed successfully!\n";
    echo Artisan::output();
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}

echo "\n=== Re-checking Columns ===\n";
foreach ($columns as $table => $column) {
    $exists = Schema::hasColumn($table, $column);
    echo "Table '{$table}' has column '{$column}': " . ($exists ? 'YES' : 'NO') . "\n";
}
