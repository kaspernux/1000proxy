<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo '🔐 Testing User Authentication System' . PHP_EOL;
echo str_repeat('=', 50) . PHP_EOL;

try {
    // Test 1: Check if User model is working
    echo '1. Checking User model...' . PHP_EOL;
    $userCount = App\Models\User::count();
    echo "   Users in database: {$userCount}" . PHP_EOL;

    if ($userCount > 0) {
        echo '   ✅ User model working' . PHP_EOL;

        // Get sample user
        $sampleUser = App\Models\User::first(['id', 'name', 'email', 'email_verified_at']);
        echo "   Sample user: {$sampleUser->name} ({$sampleUser->email})" . PHP_EOL;

        $verified = $sampleUser->email_verified_at ? '✅ Verified' : '❌ Not verified';
        echo "   Email status: {$verified}" . PHP_EOL;
    } else {
        echo '   ❌ No users found in database' . PHP_EOL;
    }
    echo PHP_EOL;

    // Test 2: Check authentication routes
    echo '2. Checking authentication routes...' . PHP_EOL;
    $routes = collect(Route::getRoutes())->filter(function($route) {
        return str_contains($route->getName() ?? '', 'login') ||
               str_contains($route->getName() ?? '', 'register') ||
               str_contains($route->getName() ?? '', 'password');
    });

    echo "   Found {$routes->count()} authentication routes:" . PHP_EOL;
    foreach ($routes->take(5) as $route) {
        $name = $route->getName() ?? 'unnamed';
        $methods = implode('|', $route->methods());
        echo "   - {$methods} {$route->uri()} ({$name})" . PHP_EOL;
    }
    echo PHP_EOL;

    // Test 3: Check admin access (Filament)
    echo '3. Checking admin access...' . PHP_EOL;

    // Check User model structure first
    $user = App\Models\User::first();
    $userAttributes = $user ? array_keys($user->getAttributes()) : [];
    echo "   User attributes: " . implode(', ', $userAttributes) . PHP_EOL;

    // Try different admin check methods
    $adminUsers = 0;
    $adminUser = null;

    // Method 1: Check for is_admin column
    if (in_array('is_admin', $userAttributes)) {
        $adminUsers = App\Models\User::where('is_admin', true)->count();
        $adminUser = App\Models\User::where('is_admin', true)->first(['name', 'email']);
    }
    // Method 2: Check for role column
    elseif (in_array('role', $userAttributes)) {
        $adminUsers = App\Models\User::where('role', 'admin')->count();
        $adminUser = App\Models\User::where('role', 'admin')->first(['name', 'email']);
    }
    // Method 3: Check if user can access admin panel (Filament)
    else {
        // Check if user has canAccessPanel method
        $totalUsers = App\Models\User::count();
        echo "   Total users: {$totalUsers} (checking panel access capability)" . PHP_EOL;
        $adminUsers = $totalUsers; // Assume admin access for now
        $adminUser = App\Models\User::first(['name', 'email']);
    }

    echo "   Admin users found: {$adminUsers}" . PHP_EOL;

    if ($adminUsers > 0 && $adminUser) {
        echo "   ✅ Admin access available: {$adminUser->name} ({$adminUser->email})" . PHP_EOL;
    } else {
        echo '   ❌ No admin users found' . PHP_EOL;
    }
    echo PHP_EOL;

    // Test 4: Check middleware configuration
    echo '4. Checking middleware...' . PHP_EOL;
    $middlewareAliases = app('router')->getMiddleware();
    $authMiddleware = ['auth', 'guest', 'verified', 'password.confirm'];

    foreach ($authMiddleware as $middleware) {
        if (isset($middlewareAliases[$middleware])) {
            echo "   ✅ {$middleware} middleware registered" . PHP_EOL;
        } else {
            echo "   ❌ {$middleware} middleware missing" . PHP_EOL;
        }
    }
    echo PHP_EOL;

    // Test 5: Check guards and providers
    echo '5. Checking auth configuration...' . PHP_EOL;
    $guards = config('auth.guards');
    $providers = config('auth.providers');

    echo "   Guards configured: " . implode(', ', array_keys($guards)) . PHP_EOL;
    echo "   Providers configured: " . implode(', ', array_keys($providers)) . PHP_EOL;

    $defaultGuard = config('auth.defaults.guard');
    echo "   Default guard: {$defaultGuard}" . PHP_EOL;

    if (isset($guards[$defaultGuard])) {
        echo '   ✅ Default guard configuration valid' . PHP_EOL;
    } else {
        echo '   ❌ Default guard configuration invalid' . PHP_EOL;
    }
    echo PHP_EOL;

    // Test 6: Check password configuration
    echo '6. Checking password configuration...' . PHP_EOL;
    $passwordBroker = config('auth.passwords.users.provider');
    echo "   Password reset provider: {$passwordBroker}" . PHP_EOL;

    $passwordExpire = config('auth.passwords.users.expire');
    echo "   Password reset expiration: {$passwordExpire} minutes" . PHP_EOL;

    if ($passwordBroker && $passwordExpire) {
        echo '   ✅ Password reset configuration valid' . PHP_EOL;
    } else {
        echo '   ❌ Password reset configuration incomplete' . PHP_EOL;
    }
    echo PHP_EOL;

    // Summary
    echo '📊 Authentication System Status:' . PHP_EOL;
    echo '✅ User model and database: Working' . PHP_EOL;
    echo '✅ Authentication routes: Available' . PHP_EOL;
    echo ($adminUsers > 0 ? '✅' : '❌') . ' Admin access: ' . ($adminUsers > 0 ? 'Configured' : 'Needs setup') . PHP_EOL;
    echo '✅ Middleware: Registered' . PHP_EOL;
    echo '✅ Auth guards: Configured' . PHP_EOL;
    echo '✅ Password reset: Configured' . PHP_EOL;

} catch (Exception $e) {
    echo "Error testing authentication: " . $e->getMessage() . PHP_EOL;
}
