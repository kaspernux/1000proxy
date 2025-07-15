<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProgressiveWebAppService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * PWA Management Command
 *
 * Command for installing, updating, and managing Progressive Web App functionality.
 */
class ManagePWACommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pwa:manage
                           {action : Action to perform (install|update|status|test|clear)}
                           {--force : Force the action without confirmation}
                           {--debug : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Progressive Web App functionality';

    private ProgressiveWebAppService $pwaService;

    /**
     * Create a new command instance.
     */
    public function __construct(ProgressiveWebAppService $pwaService)
    {
        parent::__construct();
        $this->pwaService = $pwaService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        $this->info("🚀 PWA Management Tool");
        $this->info("Action: " . ucfirst($action));
        $this->newLine();

        try {
            switch ($action) {
                case 'install':
                    return $this->installPWA();

                case 'update':
                    return $this->updatePWA();

                case 'status':
                    return $this->showStatus();

                case 'test':
                    return $this->testPWA();

                case 'clear':
                    return $this->clearPWA();

                default:
                    $this->error("Unknown action: {$action}");
                    $this->info("Available actions: install, update, status, test, clear");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("PWA management failed: " . $e->getMessage());

            if ($this->option('debug')) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Install PWA files and configuration
     */
    private function installPWA(): int
    {
        $this->info("📦 Installing PWA files...");

        if (!$this->option('force') && !$this->confirm('This will create/overwrite PWA files. Continue?')) {
            $this->info('Installation cancelled.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar(6);
        $progressBar->start();

        // Install PWA files
        $results = $this->pwaService->installPWAFiles();
        $progressBar->advance();

        // Create public directories
        $this->createPublicDirectories();
        $progressBar->advance();

        // Generate sample icons
        $this->generateSampleIcons();
        $progressBar->advance();

        // Create browser config
        $this->createBrowserConfig();
        $progressBar->advance();

        // Update routes
        $this->updateRoutes();
        $progressBar->advance();

        // Test installation
        $this->testInstallation();
        $progressBar->advance();

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info("✅ PWA installation completed!");
        $this->table(['Component', 'Status'], [
            ['Manifest', $results['manifest'] ?? 'Created'],
            ['Service Worker', $results['service_worker'] ?? 'Created'],
            ['Offline Page', $results['offline_page'] ?? 'Created'],
            ['Icons Directory', $results['icons_dir'] ?? 'Exists'],
            ['Screenshots Directory', $results['screenshots_dir'] ?? 'Exists'],
        ]);

        $this->newLine();
        $this->info("🎯 Next steps:");
        $this->line("1. Include PWA meta tags in your layout: @include('components.pwa-meta')");
        $this->line("2. Add PWA routes to your web.php: require __DIR__.'/pwa.php'");
        $this->line("3. Generate app icons and place them in public/images/icons/");
        $this->line("4. Test PWA functionality: php artisan pwa:manage test");

        return 0;
    }

    /**
     * Update PWA cache and files
     */
    private function updatePWA(): int
    {
        $this->info("🔄 Updating PWA...");

        // Update cache version
        $newVersion = $this->pwaService->updateCacheVersion();
        $this->info("Cache version updated to: {$newVersion}");

        // Regenerate service worker
        $serviceWorker = $this->pwaService->generateServiceWorker();
        File::put(public_path('sw.js'), $serviceWorker);
        $this->info("Service worker updated");

        // Clear caches
        $this->call('cache:clear');
        $this->info("Application cache cleared");

        $this->info("✅ PWA update completed!");
        return 0;
    }

    /**
     * Show PWA status
     */
    private function showStatus(): int
    {
        $this->info("📊 PWA Status Report");
        $this->newLine();

        $stats = $this->pwaService->getInstallationStats();

        // Installation status
        $this->info("📦 Installation Status:");
        $this->table(['Component', 'Status'], [
            ['Manifest File', $stats['manifest_exists'] ? '✅ Exists' : '❌ Missing'],
            ['Service Worker', $stats['service_worker_exists'] ? '✅ Exists' : '❌ Missing'],
            ['Offline Page', $stats['offline_page_exists'] ? '✅ Exists' : '❌ Missing'],
            ['Icons Directory', $stats['icons_directory_exists'] ? '✅ Exists' : '❌ Missing'],
            ['Screenshots Directory', $stats['screenshots_directory_exists'] ? '✅ Exists' : '❌ Missing'],
        ]);

        $this->newLine();

        // Features status
        $this->info("🚀 Supported Features:");
        $features = $stats['supported_features'];
        foreach ($features as $feature => $supported) {
            $icon = $supported ? '✅' : '❌';
            $this->line("{$icon} " . ucwords(str_replace('_', ' ', $feature)));
        }

        $this->newLine();

        // Technical details
        $this->info("🔧 Technical Details:");
        $this->line("Cache Version: {$stats['cache_version']}");
        $this->line("Last Updated: {$stats['last_updated']}");

        // File sizes
        if ($stats['manifest_exists']) {
            $manifestSize = File::size(public_path('manifest.json'));
            $this->line("Manifest Size: " . $this->formatBytes($manifestSize));
        }

        if ($stats['service_worker_exists']) {
            $swSize = File::size(public_path('sw.js'));
            $this->line("Service Worker Size: " . $this->formatBytes($swSize));
        }

        return 0;
    }

    /**
     * Test PWA functionality
     */
    private function testPWA(): int
    {
        $this->info("🧪 Testing PWA functionality...");
        $this->newLine();

        $tests = [
            'Manifest Generation' => function() {
                $manifest = $this->pwaService->generateManifest();
                return !empty($manifest['name']) && !empty($manifest['icons']);
            },
            'Service Worker Generation' => function() {
                $sw = $this->pwaService->generateServiceWorker();
                return str_contains($sw, 'Service Worker') && str_contains($sw, 'addEventListener');
            },
            'File Permissions' => function() {
                return is_writable(public_path()) && is_readable(public_path('manifest.json'));
            },
            'Icons Directory' => function() {
                return File::isDirectory(public_path('images/icons'));
            },
            'Route Configuration' => function() {
                return File::exists(base_path('routes/pwa.php'));
            }
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $testName => $testFunc) {
            try {
                $result = $testFunc();
                $icon = $result ? '✅' : '❌';
                $this->line("{$icon} {$testName}");

                if ($result) {
                    $passed++;
                }
            } catch (\Exception $e) {
                $this->line("❌ {$testName} - Error: " . $e->getMessage());
            }
        }

        $this->newLine();

        if ($passed === $total) {
            $this->info("🎉 All tests passed! ({$passed}/{$total})");
            return 0;
        } else {
            $this->warn("⚠️  Some tests failed. ({$passed}/{$total} passed)");
            return 1;
        }
    }

    /**
     * Clear PWA files and cache
     */
    private function clearPWA(): int
    {
        $this->info("🧹 Clearing PWA files and cache...");

        if (!$this->option('force') && !$this->confirm('This will remove PWA files. Continue?')) {
            $this->info('Clear operation cancelled.');
            return 0;
        }

        $files = [
            public_path('manifest.json'),
            public_path('sw.js'),
            public_path('browserconfig.xml')
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->line("Deleted: " . basename($file));
            }
        }

        // Clear PWA cache
        $this->call('cache:clear');

        $this->info("✅ PWA files and cache cleared!");
        return 0;
    }

    /**
     * Create public directories
     */
    private function createPublicDirectories(): void
    {
        $directories = [
            'images/icons',
            'images/screenshots',
            'images/splash'
        ];

        foreach ($directories as $dir) {
            $path = public_path($dir);
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);

                if ($this->option('debug')) {
                    $this->line("Created directory: {$dir}");
                }
            }
        }
    }

    /**
     * Generate sample icons
     */
    private function generateSampleIcons(): void
    {
        // Create simple placeholder icons using SVG
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];

        foreach ($sizes as $size) {
            $iconPath = public_path("images/icons/icon-{$size}x{$size}.png");

            if (!File::exists($iconPath)) {
                $svg = $this->generateSVGIcon($size);
                File::put(str_replace('.png', '.svg', $iconPath), $svg);

                if ($this->option('debug')) {
                    $this->line("Generated icon: icon-{$size}x{$size}.svg");
                }
            }
        }
    }

    /**
     * Generate SVG icon
     */
    private function generateSVGIcon(int $size): string
    {
        return "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 {$size} {$size}\">
            <defs>
                <linearGradient id=\"grad\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">
                    <stop offset=\"0%\" style=\"stop-color:#3b82f6;stop-opacity:1\" />
                    <stop offset=\"100%\" style=\"stop-color:#1e40af;stop-opacity:1\" />
                </linearGradient>
            </defs>
            <rect width=\"{$size}\" height=\"{$size}\" rx=\"" . ($size * 0.1) . "\" fill=\"url(#grad)\"/>
            <text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"white\" font-family=\"Arial, sans-serif\" font-size=\"" . ($size * 0.3) . "\" font-weight=\"bold\">1K</text>
        </svg>";
    }

    /**
     * Create browser config
     */
    private function createBrowserConfig(): void
    {
        $configPath = public_path('browserconfig.xml');

        if (!File::exists($configPath)) {
            $config = '<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square70x70logo src="/images/icons/icon-72x72.png"/>
            <square150x150logo src="/images/icons/icon-152x152.png"/>
            <square310x310logo src="/images/icons/icon-384x384.png"/>
            <TileColor>#1f2937</TileColor>
        </tile>
    </msapplication>
</browserconfig>';

            File::put($configPath, $config);

            if ($this->option('debug')) {
                $this->line("Created browserconfig.xml");
            }
        }
    }

    /**
     * Update routes
     */
    private function updateRoutes(): void
    {
        $webRoutesPath = base_path('routes/web.php');
        $webRoutes = File::get($webRoutesPath);

        if (!str_contains($webRoutes, "require __DIR__.'/pwa.php'")) {
            $pwaRequire = "\n// PWA Routes\nrequire __DIR__.'/pwa.php';\n";
            File::append($webRoutesPath, $pwaRequire);

            if ($this->option('debug')) {
                $this->line("Added PWA routes to web.php");
            }
        }
    }

    /**
     * Test installation
     */
    private function testInstallation(): void
    {
        $stats = $this->pwaService->getInstallationStats();

        if (!$stats['manifest_exists'] || !$stats['service_worker_exists']) {
            throw new \Exception('PWA installation incomplete');
        }
    }

    /**
     * Format bytes
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}
