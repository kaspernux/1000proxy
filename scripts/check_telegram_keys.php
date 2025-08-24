<?php
// Compare keys across resources/lang/*/telegram.php and print diffs vs en
$baseDir = __DIR__ . '/../resources/lang';
$locales = include __DIR__ . '/../config/locales.php';
$supported = $locales['supported'] ?? ['en'];

function loadLocaleArray($path) {
    $arr = include $path;
    if (!is_array($arr)) throw new RuntimeException("File does not return array: $path");
    return $arr;
}

function flatten(array $arr, string $prefix = ''): array {
    $out = [];
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? (string)$k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out += flatten($v, $key);
        } else {
            $out[$key] = $v;
        }
    }
    return $out;
}

$en = loadLocaleArray($baseDir . '/en/telegram.php');
$allowUntranslated = [
    // Brand/product or proper nouns that are expected to remain identical
    'bot.name',
    'topup.method_paypal',
    'topup.method_bitcoin',
    'topup.method_monero',
    'topup.method_solana',
    'admin.email',
    'admin.telegram',
];
$enFlat = flatten($en);
$summary = [];
foreach ($supported as $lc) {
    $file = $baseDir . "/$lc/telegram.php";
    if (!file_exists($file)) {
        $summary[$lc] = ['missing_file' => true];
        continue;
    }
    $arr = loadLocaleArray($file);
    $flat = flatten($arr);
    $missing = array_values(array_diff(array_keys($enFlat), array_keys($flat)));
    $extra = array_values(array_diff(array_keys($flat), array_keys($enFlat)));
    $untranslated = [];
    foreach ($enFlat as $k => $v) {
        if (!array_key_exists($k, $flat)) continue;
        $val = $flat[$k];
        if ($lc !== 'en' && is_string($val) && ($val === $k || $val === $v)) {
            if (in_array($k, $allowUntranslated, true)) continue; // skip allowlisted keys
            $untranslated[] = $k;
        }
    }
    $summary[$lc] = [
        'missing_count' => count($missing),
        'extra_count' => count($extra),
        'untranslated_count' => count($untranslated),
        'missing' => $missing,
        'extra' => $extra,
        'untranslated' => $untranslated,
    ];
}

// Print concise report
foreach ($summary as $lc => $info) {
    if (!empty($info['missing_file'])) {
        echo "$lc: MISSING FILE\n";
        continue;
    }
    echo "$lc: missing=".$info['missing_count'].", extra=".$info['extra_count'].", untranslated=".$info['untranslated_count']."\n";
    if ($info['missing_count']>0) {
        echo "  missing keys:\n    - ".implode("\n    - ", array_slice($info['missing'],0,20))."\n";
        if ($info['missing_count']>20) echo "    ...(+".($info['missing_count']-20).")\n";
    }
    if (!empty($info['untranslated_count'])) {
        echo "  untranslated keys (first 40):\n    - ".implode("\n    - ", array_slice($info['untranslated'],0,40))."\n";
        if ($info['untranslated_count']>40) echo "    ...(+".($info['untranslated_count']-40).")\n";
    }
}

?>
