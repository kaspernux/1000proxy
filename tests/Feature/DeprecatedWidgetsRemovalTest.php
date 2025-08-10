<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeprecatedWidgetsRemovalTest extends TestCase
{
    /**
     * Ensure deprecated widget classes have been fully removed and cannot be autoloaded.
     */
    public function test_deprecated_widget_classes_do_not_exist(): void
    {
        $deprecatedClasses = [
            'App\\Filament\\Widgets\\AdminStatsOverview',
            'App\\Filament\\Widgets\\ComprehensiveSystemStatsWidget',
            'App\\Filament\\Widgets\\ServerHealthMonitoringWidget',
            'App\\Filament\\Widgets\\SystemHealthIndicatorsWidget',
        ];

        foreach ($deprecatedClasses as $class) {
            $this->assertFalse(class_exists($class), "Deprecated widget class still exists or is autoloadable: {$class}");
        }
    }

    /**
     * Assert the physical files for deprecated widgets are gone to prevent accidental re-discovery.
     */
    public function test_deprecated_widget_files_removed(): void
    {
        $widgetDir = app_path('Filament/Widgets');
        $files = [
            'AdminStatsOverview.php',
            'ComprehensiveSystemStatsWidget.php',
            'ServerHealthMonitoringWidget.php',
            'SystemHealthIndicatorsWidget.php',
        ];

        foreach ($files as $file) {
            $this->assertFileDoesNotExist($widgetDir . DIRECTORY_SEPARATOR . $file, "Deprecated widget file still present: {$file}");
        }
    }
}
