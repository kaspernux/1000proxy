<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $args = collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ]);

        // ensure unique user-data-dir to avoid "already in use" when tests run in parallel
        $tmpDir = sys_get_temp_dir() . '/dusk_profile_' . uniqid();
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0700, true);
        }
        $args->push('--user-data-dir=' . $tmpDir);
        // extra flags commonly needed in CI/container environments
        $args->push('--no-sandbox');
        $args->push('--disable-dev-shm-usage');
        $args->push('--disable-setuid-sandbox');

        if (! $this->hasHeadlessDisabled()) {
            $args = $args->merge(['--disable-gpu', '--headless=new']);
        }

        $options = (new ChromeOptions)->addArguments($args->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
