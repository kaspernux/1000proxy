<?php

/**
 * 1000proxy Production Readiness Check
 * This script validates the application is ready for production deployment
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🚀 1000proxy Production Readiness Check\n";
echo "=====================================\n\n";

$checks = [];
$warnings = [];

// 1. Check Laravel installation
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $checks['Laravel Framework'] = '✅ Loaded successfully';
} catch (Exception $e) {
    $checks['Laravel Framework'] = '❌ Failed to load: ' . $e->getMessage();
}

// 2. Check required directories
$requiredDirs = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($requiredDirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir) && is_writable(__DIR__ . '/' . $dir)) {
        $checks["Directory: $dir"] = '✅ Exists and writable';
    } else {
        $checks["Directory: $dir"] = '❌ Missing or not writable';
    }
}

// 3. Check required files
$requiredFiles = [
    'composer.json',
    'artisan',
    'routes/web.php',
    'routes/api.php',
    'bootstrap/app.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $checks["File: $file"] = '✅ Exists';
    } else {
        $checks["File: $file"] = '❌ Missing';
    }
}

// 4. Check Filament panels
$filamentPanels = [
    'app/Filament/Admin',
    'app/Filament/Customer'
];

foreach ($filamentPanels as $panel) {
    if (is_dir(__DIR__ . '/' . $panel)) {
        $resourceCount = count(glob(__DIR__ . '/' . $panel . '/**/*Resource.php', GLOB_BRACE));
        if ($resourceCount === 0) {
            // Check for cluster-based resources
            $clusterCount = count(glob(__DIR__ . '/' . $panel . '/Clusters/*/Resources/*Resource.php'));
            $resourceCount = $clusterCount;
        }
        $checks["Filament Panel: " . basename($panel)] = "✅ $resourceCount resources found";
    } else {
        $checks["Filament Panel: " . basename($panel)] = '❌ Missing';
    }
}

// 5. Check Telegram bot infrastructure
$telegramFiles = [
    'app/Services/TelegramBotService.php',
    'app/Http/Controllers/TelegramBotController.php',
    'app/Jobs/ProcessTelegramMessage.php',
    'app/Http/Middleware/TelegramRateLimit.php'
];

foreach ($telegramFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $checks["Telegram: " . basename($file)] = '✅ Exists';
    } else {
        $checks["Telegram: " . basename($file)] = '❌ Missing';
    }
}

// 6. Check deployment files
$deploymentFiles = [
    'deploy/production-deploy.sh',
    'deploy/PRODUCTION_CHECKLIST.md',
    'deploy/supervisor.conf',
    'Dockerfile',
    'docker-compose.yml'
];

foreach ($deploymentFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $checks["Deployment: " . basename($file)] = '✅ Exists';
    } else {
        $checks["Deployment: " . basename($file)] = '❌ Missing';
    }
}

// 7. Check configuration templates
if (file_exists(__DIR__ . '/.env.example')) {
    $envExample = file_get_contents(__DIR__ . '/.env.example');
    $requiredEnvVars = [
        'APP_NAME', 'APP_KEY', 'DB_CONNECTION', 'TELEGRAM_BOT_TOKEN',
        'STRIPE_SECRET', 'PAYPAL_CLIENT_SECRET', 'NOWPAYMENTS_API_KEY'
    ];

    $missingVars = [];
    foreach ($requiredEnvVars as $var) {
        if (strpos($envExample, $var) === false) {
            $missingVars[] = $var;
        }
    }

    if (empty($missingVars)) {
        $checks['Environment Template'] = '✅ All required variables present';
    } else {
        $checks['Environment Template'] = '⚠️ Missing: ' . implode(', ', $missingVars);
    }
} else {
    $checks['Environment Template'] = '❌ .env.example missing';
}

// 8. Check composer dependencies
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $checks['Composer Dependencies'] = '✅ Installed';
} else {
    $checks['Composer Dependencies'] = '❌ Run composer install';
}

// Display results
echo "COMPONENT CHECKS:\n";
echo "================\n";
foreach ($checks as $component => $status) {
    echo str_pad($component, 40, '.') . ' ' . $status . "\n";
}

// Calculate readiness percentage
$total = count($checks);
$passed = count(array_filter($checks, function($status) {
    return strpos($status, '✅') === 0;
}));
$percentage = ($passed / $total) * 100;

echo "\n";
echo "READINESS SUMMARY:\n";
echo "==================\n";
echo "Components Checked: $total\n";
echo "Components Passed: $passed\n";
echo "Readiness Level: " . number_format($percentage, 1) . "%\n\n";

if ($percentage >= 95) {
    echo "🎉 PRODUCTION READY!\n";
    echo "Your application is ready for production deployment.\n";
} elseif ($percentage >= 85) {
    echo "⚠️ MOSTLY READY\n";
    echo "Fix the failed checks above before deploying.\n";
} else {
    echo "❌ NOT READY\n";
    echo "Critical components are missing. Complete development first.\n";
}

echo "\nNext Steps:\n";
echo "1. Review deployment checklist: deploy/PRODUCTION_CHECKLIST.md\n";
echo "2. Configure production environment: .env\n";
echo "3. Run deployment script: bash deploy/production-deploy.sh\n";
echo "4. Validate deployment: bash deploy/validate-production.sh\n";

echo "\n🔗 Documentation: README.md\n";
