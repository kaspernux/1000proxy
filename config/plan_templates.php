<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plan templates
    |--------------------------------------------------------------------------
    |
    | Define plan templates per site/region. Templates are arrays of plan
    | definitions consumed by App\Services\ServerPlanProvisioningService.
    |
    */

    'chicago' => [
        [
            'name' => 'Chicago - General - 10GB',
            'server_category_slug' => 'general',
            'price_monthly' => 4.99,
            'max_concurrent_connections' => 3,
            'bandwidth_limit_gb' => 10,
            'auto_provision' => true,
            'provisioning_type' => 'shared',
            'is_active' => true,
        ],
        [
            'name' => 'Chicago - Streaming - 100GB',
            'server_category_slug' => 'streaming',
            'price_monthly' => 12.99,
            'max_concurrent_connections' => 3,
            'bandwidth_limit_gb' => 100,
            'auto_provision' => true,
            'provisioning_type' => 'shared',
            'is_active' => true,
        ],
        [
            'name' => 'Chicago - Gaming - 50GB',
            'server_category_slug' => 'gaming',
            'price_monthly' => 10.99,
            'max_concurrent_connections' => 5,
            'bandwidth_limit_gb' => 50,
            'auto_provision' => true,
            'provisioning_type' => 'dedicated',
            'is_active' => true,
        ],
    ],

    'default' => [
        // Placeholder template — you can copy/extend per-region
    ],
];
