<?php

return [
    // Modes: 'tax_free' or 'regional'
    'mode' => env('TAX_MODE', 'tax_free'),

    // If using 'regional' mode, you can configure default and per-region rates
    'default_rate' => (float) env('TAX_DEFAULT_RATE', 0.0), // percent e.g., 20 => 20%

    // Whether shipping is taxable (not used currently)
    'shipping_taxable' => (bool) env('TAX_SHIPPING_TAXABLE', false),

    // Whether prices include tax (affects how you display totals; not applied in code yet)
    'prices_include_tax' => (bool) env('TAX_PRICES_INCLUDE_TAX', false),

    // Optional per-region overrides. Most specific match wins.
    // Keys can be: 'country:US', 'country:US|state:CA', 'country:GB', etc.
    'rates' => [
        // 'country:US' => 0.0,
        // 'country:US|state:CA' => 7.25,
        // 'country:GB' => 20.0,
    ],
];
