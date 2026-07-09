<?php

define('VG_ACCESS', true);

$root = realpath(__DIR__ . '/../..');
$base = $root . '/base';

require $base . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);

$db = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

if ($db->connect_errno) {
    echo "[FAIL] DB connection failed. errno=" . $db->connect_errno . PHP_EOL;
    exit(1);
}

$db->set_charset('utf8mb4');

echo "== ForPrint Website navigation source inspection ==" . PHP_EOL;
echo "root: " . $root . PHP_EOL;
echo "header_template: base/templates/default/include/header.php" . PHP_EOL;
echo PHP_EOL;

$tables = [
    'catalog',
    'sales',
    'delivery',
    'information',
    'news',
    'settings',
];

foreach ($tables as $table) {
    echo "== Table: {$table} ==" . PHP_EOL;

    $exists = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");

    if (!$exists || !$exists->num_rows) {
        echo "[MISSING]" . PHP_EOL . PHP_EOL;
        continue;
    }

    $columnsResult = $db->query("SHOW COLUMNS FROM `{$table}`");

    $columns = [];

    while ($row = $columnsResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    echo "columns: " . implode(', ', $columns) . PHP_EOL;

    $countResult = $db->query("SELECT COUNT(*) AS count FROM `{$table}`");
    $countRow = $countResult ? $countResult->fetch_assoc() : ['count' => '?'];

    echo "count: " . $countRow['count'] . PHP_EOL;

    $publicColumns = array_values(array_intersect(
        ['id', 'name', 'title', 'alias', 'visible', 'menu_position', 'menu_name'],
        $columns
    ));

    if ($publicColumns) {
        $select = implode(', ', array_map(fn($c) => "`{$c}`", $publicColumns));
        $rows = $db->query("SELECT {$select} FROM `{$table}` LIMIT 10");

        if ($rows) {
            echo "sample_public_rows:" . PHP_EOL;

            while ($row = $rows->fetch_assoc()) {
                echo "  - " . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            }
        }
    }

    echo PHP_EOL;
}

echo "== Header hardcoded menu hints ==" . PHP_EOL;

$headerPath = $base . '/templates/default/include/header.php';
$header = file_exists($headerPath) ? file($headerPath) : [];

foreach ($header as $lineNo => $line) {
    if (
        stripos($line, '<nav') !== false ||
        stripos($line, 'href') !== false ||
        stripos($line, 'catalog') !== false ||
        stripos($line, 'delivery') !== false ||
        stripos($line, 'news') !== false ||
        stripos($line, 'contacts') !== false ||
        stripos($line, 'knoweleges') !== false
    ) {
        $safe = trim(strip_tags($line));
        $safe = preg_replace('/\s+/', ' ', $safe);

        if ($safe !== '') {
            echo ($lineNo + 1) . ': ' . $safe . PHP_EOL;
        } else {
            echo ($lineNo + 1) . ': ' . trim($line) . PHP_EOL;
        }
    }
}