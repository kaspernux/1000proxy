<?php

require_once __DIR__ . '/vendor/autoload.php';

use SimpleSoftwareIO\QrCode\Facades\QrCode;

// Test if QR code generation works
try {
    $qrCode = QrCode::size(300)->generate('Test QR Code');
    echo "QR Code generation successful!\n";
    echo "QR Code type: " . gettype($qrCode) . "\n";
    echo "QR Code length: " . strlen($qrCode) . " characters\n";
} catch (Exception $e) {
    echo "QR Code generation failed: " . $e->getMessage() . "\n";
}
