<?php
// List untranslated values across locales compared to English baseline, with an allowlist

$baseDir = __DIR__ . '/../resources/lang';
$locales = include __DIR__ . '/../config/locales.php';
$supported = $locales['supported'] ?? ['en'];

function flatten_arr(array $arr, string $prefix = ''): array {
	$out = [];
	foreach ($arr as $k => $v) {
		$key = $prefix === '' ? (string)$k : $prefix . '.' . $k;
		if (is_array($v)) { $out += flatten_arr($v, $key); }
		else { $out[$key] = $v; }
	}
	return $out;
}

$en = include $baseDir . '/en/telegram.php';
$enF = flatten_arr($en);

$allowUntranslated = [
	'bot.name',
	'topup.method_paypal',
	'topup.method_bitcoin',
	'topup.method_monero',
	'topup.method_solana',
	'admin.email',
	'admin.telegram',
];

foreach ($supported as $lc) {
	if ($lc === 'en') continue;
	$file = "$baseDir/$lc/telegram.php";
	if (!file_exists($file)) { echo "$lc: missing file\n"; continue; }
	$arr = include $file;
	$flat = flatten_arr($arr);
	$un = [];
	foreach ($enF as $k => $v) {
		if (!array_key_exists($k, $flat)) continue;
		$val = $flat[$k];
		if (is_string($val) && ($val === $v || $val === $k)) {
			if (in_array($k, $allowUntranslated, true)) continue;
			$un[] = $k;
		}
	}
	echo $lc . ': ' . count($un) . " untranslated\n";
	if ($un) {
		echo "  - " . implode("\n  - ", array_slice($un, 0, 50)) . "\n";
		if (count($un) > 50) echo "  ...(+" . (count($un)-50) . ")\n";
	}
}

?>
