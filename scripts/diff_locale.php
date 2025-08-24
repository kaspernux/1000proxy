<?php
$lc = $argv[1] ?? null;
if (!$lc) { fwrite(STDERR, "Usage: php scripts/diff_locale.php <locale>\n"); exit(1);} 
$baseDir = __DIR__ . '/../resources/lang';
$en = include $baseDir . '/en/telegram.php';
$locFile = $baseDir . "/$lc/telegram.php";
if (!file_exists($locFile)) { fwrite(STDERR, "Missing file for $lc\n"); exit(2);} 
$loc = include $locFile;

function flatten(array $arr, string $prefix = ''): array {
    $out = [];
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? (string)$k : $prefix . '.' . $k;
        if (is_array($v)) { $out += flatten($v, $key); }
        else { $out[$key] = $v; }
    }
    return $out;
}
$enF = flatten($en); $lcF = flatten($loc);
$miss = array_values(array_diff(array_keys($enF), array_keys($lcF)));
$extra = array_values(array_diff(array_keys($lcF), array_keys($enF)));

echo "Locale $lc: missing=".count($miss)." extra=".count($extra)."\n";
if ($miss) { echo "-- Missing keys --\n".implode("\n", $miss)."\n"; }
if ($extra) { echo "-- Extra keys --\n".implode("\n", $extra)."\n"; }
