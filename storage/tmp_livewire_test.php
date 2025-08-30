<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$id = \App\Models\ServerPlan::where('is_active',1)->value('id') ?? 1;
try {
    \Livewire\Livewire::test(\App\Livewire\ProductsPage::class)->call('addToCart', $id);
    echo "LIVEWIRE CALL: OK\n";
} catch (\Throwable $e) {
    echo "LIVEWIRE CALL: ERROR: ".get_class($e).": ".$e->getMessage()."\n";
}
