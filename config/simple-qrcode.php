<?php

return [
    'default' => [
        'writer' => 'svg', // Use SVG instead of PNG to avoid Imagick
        'size' => 300,
        'margin' => 0,
        'foreground_color' => [
            'r' => 0,
            'g' => 0,
            'b' => 0,
            'a' => 0,
        ],
        'background_color' => [
            'r' => 255,
            'g' => 255,
            'b' => 255,
            'a' => 0,
        ],
    ],
    'svg' => [
        'writer' => 'svg',
        'size' => 300,
        'margin' => 0,
        'foreground_color' => [
            'r' => 0,
            'g' => 0,
            'b' => 0,
            'a' => 0,
        ],
        'background_color' => [
            'r' => 255,
            'g' => 255,
            'b' => 255,
            'a' => 0,
        ],
    ],
];
