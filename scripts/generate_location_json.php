<?php
/**
 * Converts CSV location data to static JSON files for frontend use.
 * Run: php scripts/generate_location_json.php
 */

$baseDir = dirname(__DIR__);
$dataDir = $baseDir . '/database/data';
$outDir = $baseDir . '/public/data';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$files = [
    [
        'csv' => 'refregion.csv',
        'json' => 'regions.json',
        'fields' => ['regCode', 'regDesc'],
    ],
    [
        'csv' => 'refprovince.csv',
        'json' => 'provinces.json',
        'fields' => ['provCode', 'provDesc', 'regCode'],
    ],
    [
        'csv' => 'refcitymun.csv',
        'json' => 'cities.json',
        'fields' => ['citymunCode', 'citymunDesc', 'provCode'],
    ],
    [
        'csv' => 'refbrgy.csv',
        'json' => 'barangays.json',
        'fields' => ['brgyCode', 'brgyDesc', 'citymunCode'],
    ],
];

foreach ($files as $spec) {
    $csvPath = $dataDir . '/' . $spec['csv'];
    if (!file_exists($csvPath)) {
        echo "SKIP: {$spec['csv']} not found.\n";
        continue;
    }

    $handle = fopen($csvPath, 'r');
    $header = fgetcsv($handle);
    $header = array_map('trim', $header);

    // Remove BOM from first header
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

    $data = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($header))
            continue;
        $assoc = array_combine($header, $row);

        $filtered = [];
        foreach ($spec['fields'] as $field) {
            $filtered[$field] = $assoc[$field] ?? '';
        }
        $data[] = $filtered;
    }
    fclose($handle);

    $jsonPath = $outDir . '/' . $spec['json'];
    file_put_contents($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE));

    $size = round(filesize($jsonPath) / 1024, 1);
    echo "{$spec['json']}: " . count($data) . " records ({$size} KB)\n";
}

echo "\nDone! JSON files written to public/data/\n";
