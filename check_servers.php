<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $servers = App\Models\Server::take(3)->get(['id', 'name', 'host', 'port', 'username']);
    echo 'Servers in database: ' . App\Models\Server::count() . PHP_EOL;

    if ($servers->count() > 0) {
        echo "Sample servers:" . PHP_EOL;
        foreach($servers as $server) {
            echo "ID: {$server->id}, Name: {$server->name}, Host: {$server->host}:{$server->port}, User: {$server->username}" . PHP_EOL;
        }
    } else {
        echo "No servers found in database" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
