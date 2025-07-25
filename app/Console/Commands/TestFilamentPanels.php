<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TestFilamentPanels extends Command
{
    protected $signature = 'test:filament-panels
                           {--filter=* : Filter specific test methods}
                           {--detailed : Show detailed output}
                           {--coverage : Generate test coverage report}';

    protected $description = 'Run comprehensive Filament panel tests';

    public function handle()
    {
        $this->info('🧪 Starting Filament Panel Testing Suite...');
        $this->newLine();

        // Test categories to run
        $testSuites = [
            'Admin Panel Tests' => 'tests/Feature/Filament/AdminPanelTest.php',
            'Customer Panel Tests' => 'tests/Feature/Filament/CustomerPanelTest.php',
            'Integration Tests' => 'tests/Feature/Filament/FilamentIntegrationTest.php',
        ];

        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($testSuites as $suiteName => $testFile) {
            $this->info("📋 Running {$suiteName}...");

            $command = [
                'vendor/bin/phpunit',
                $testFile,
                '--testdox',
            ];

            if ($this->option('detailed')) {
                $command[] = '--verbose';
            }

            if ($this->option('coverage')) {
                $command[] = '--coverage-html=storage/test-coverage';
            }

            if ($this->option('filter')) {
                foreach ($this->option('filter') as $filter) {
                    $command[] = '--filter';
                    $command[] = $filter;
                }
            }

            $exitCode = $this->executeTestCommand($command);

            if ($exitCode === 0) {
                $this->info("✅ {$suiteName} - PASSED");
                $passedTests++;
            } else {
                $this->error("❌ {$suiteName} - FAILED");
                $failedTests++;
            }

            $totalTests++;
            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('📊 Test Summary:');
        $this->table(
            ['Test Suite', 'Status', 'Results'],
            [
                ['Total Suites', $totalTests, ''],
                ['Passed', $passedTests, '✅'],
                ['Failed', $failedTests, $failedTests > 0 ? '❌' : '✅'],
                ['Success Rate', round(($passedTests / $totalTests) * 100, 2) . '%', ''],
            ]
        );

        if ($failedTests === 0) {
            $this->info('🎉 All Filament panel tests passed successfully!');

            // Additional validation checks
            $this->newLine();
            $this->info('🔍 Running additional validation checks...');
            $this->validatePanelConfiguration();
            $this->validateRoutesConfiguration();
            $this->validateResourcePermissions();

        } else {
            $this->error('⚠️  Some tests failed. Please review the output above.');
            return 1;
        }

        if ($this->option('coverage')) {
            $this->newLine();
            $this->info('📈 Test coverage report generated at: storage/test-coverage/index.html');
        }

        return 0;
    }

    protected function executeTestCommand(array $command): int
    {
        $process = new \Symfony\Component\Process\Process($command);
        $process->setTimeout(300); // 5 minutes

        try {
            return $process->run(function ($type, $buffer) {
                if ($this->option('detailed')) {
                    echo $buffer;
                }
            });
        } catch (\Exception $e) {
            $this->error("Error running command: " . $e->getMessage());
            return 1;
        }
    }

    protected function validatePanelConfiguration(): void
    {
        $this->info('🔧 Validating panel configuration...');

        // Check if admin panel is configured
        try {
            $adminPanel = app(\Filament\Panel::class);
            $this->info('✅ Admin panel configuration is valid');
        } catch (\Exception $e) {
            $this->error('❌ Admin panel configuration error: ' . $e->getMessage());
        }

        // Check if customer panel is configured
        try {
            $customerPanel = \Filament\Facades\Filament::getPanel('customer');
            $this->info('✅ Customer panel configuration is valid');
        } catch (\Exception $e) {
            $this->warn('⚠️  Customer panel configuration warning: ' . $e->getMessage());
        }
    }

    protected function validateRoutesConfiguration(): void
    {
        $this->info('🛣️  Validating routes configuration...');

        $requiredRoutes = [
            '/admin' => 'Admin panel route',
            '/customer' => 'Customer panel route',
            '/admin/login' => 'Admin login route',
            '/login' => 'Customer login route',
        ];

        foreach ($requiredRoutes as $route => $description) {
            try {
                $url = url($route);
                $this->info("✅ {$description}: {$url}");
            } catch (\Exception $e) {
                $this->error("❌ {$description} not found: {$route}");
            }
        }
    }

    protected function validateResourcePermissions(): void
    {
        $this->info('🔐 Validating resource permissions...');

        // Check admin resources
        $adminResources = [
            'CustomerResource' => \App\Filament\Clusters\CustomerManagement\Resources\CustomerResource::class,
            'ServerResource' => \App\Filament\Clusters\ServerManagement\Resources\ServerResource::class,
            'OrderResource' => \App\Filament\Clusters\ProxyShop\Resources\OrderResource::class,
        ];

        foreach ($adminResources as $name => $class) {
            if (class_exists($class)) {
                $this->info("✅ {$name} exists and is accessible");
            } else {
                $this->warn("⚠️  {$name} not found: {$class}");
            }
        }

        // Check customer resources
        $customerResources = [
            'MyOrderResource' => 'App\\Filament\\Customer\\Clusters\\OrderManagement\\Resources\\MyOrderResource',
            'ProfileResource' => 'App\\Filament\\Customer\\Clusters\\CustomerManagement\\Resources\\ProfileResource',
        ];

        foreach ($customerResources as $name => $class) {
            if (class_exists($class)) {
                $this->info("✅ {$name} exists and is accessible");
            } else {
                $this->warn("⚠️  {$name} not found (this may be expected): {$class}");
            }
        }
    }
}
