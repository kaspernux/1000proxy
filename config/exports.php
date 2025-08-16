<?php

return [
    // Storage disk to write export files (override in tests)
    'disk' => env('EXPORTS_DISK', 'local'),

    // Base path (directory) inside the disk to write exports
    'path' => env('EXPORTS_PATH', 'exports/orders'),

    'retention_days' => env('EXPORT_RETENTION_DAYS', 7),
];
