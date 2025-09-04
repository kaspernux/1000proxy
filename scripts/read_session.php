<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

$id = $argv[1] ?? null;
if (! $id) {
    echo "Usage: php read_session.php <session-id>\n";
    exit(2);
}

$manager = app('session');
$driver = $manager->driver();
$handler = $driver->getHandler();
$payload = $handler->read($id);
echo "RAW:\n";
echo $payload . "\n\n";

// Attempt to unserialize JSON or PHP serialize
if (@unserialize($payload) !== false || $payload === 'b:0;') {
    echo "PHP unserialize:\n";
    var_dump(@unserialize($payload));
}

// If payload is JSON-like base64 parts, attempt to decode base64 and json
$decoded = @json_decode($payload, true);
if ($decoded) {
    echo "json decode:\n";
    var_dump($decoded);
}

// Look for typical Laravel session store keys
$keys = ['login_web','password_hash_web','user_id','_token','_previous'];
foreach ($keys as $k) {
    if (strpos($payload, $k) !== false) {
        echo "Found key substring: $k\n";
    }
}

// Try to parse known Laravel session serialized format: "key|serialized" pairs
$parts = preg_split('/\|/', $payload, 2);
if (count($parts) === 2) {
    echo "Looks like key|value format, key: {$parts[0]}\n";
}

// Print length
echo "LEN: " . strlen($payload) . "\n";

