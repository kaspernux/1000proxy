<?php
// Apply localized Telegram branding and commands using the service within Laravel.
// We bootstrap the Console kernel to ensure all providers and aliases are registered (e.g., 'files').

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';

try {
	/** @var ConsoleKernel $kernel */
	$kernel = $app->make(ConsoleKernel::class);
	$kernel->bootstrap();

	/** @var \App\Services\TelegramBotService $svc */
	$svc = $app->make(\App\Services\TelegramBotService::class);
	$ok = $svc->setBrandingLocalized();
	// Also set per-locale commands so Telegram shows localized /help etc.
	$ok2 = $svc->setCommands();

	echo ($ok && $ok2) ? "OK\n" : "FAILED\n";
} catch (\Throwable $e) {
	fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
	exit(1);
}
