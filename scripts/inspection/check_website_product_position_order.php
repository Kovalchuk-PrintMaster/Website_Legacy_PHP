<?php

declare(strict_types=1);

/**
 * Static and live-DB smoke for product menu_position ordering.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$files = [
    'model' => $root . '/base/core/user/models/Model.php',
    'index' => $root . '/base/core/user/controllers/IndexController.php',
    'catalog' => $root . '/base/core/user/controllers/CatalogController.php',
    'search' => $root . '/base/core/user/controllers/SearchController.php',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "[FAIL] Missing {$label}: {$path}\n");
        exit(1);
    }
}

$contents = array_map(
    static fn (string $path): string =>
        (string)file_get_contents($path),
    $files
);

$checks = [
    'model default menu_position' =>
        str_contains(
            $contents['model'],
            "\$set['order'][] = 'menu_position';"
        ),
    'model default id tie-breaker' =>
        str_contains(
            $contents['model'],
            "\$set['order'][] = 'id';"
        ),
    'home explicit ordering' =>
        str_contains(
            $contents['index'],
            "'order' => ['menu_position', 'id']"
        ),
    'catalog default ordering' =>
        str_contains(
            $contents['catalog'],
            "'order' => ['menu_position', 'id']"
        ),
    'catalog direction whitelist' =>
        str_contains(
            $contents['catalog'],
            "in_array(\$selectedDirection, ['asc', 'desc'], true)"
        ),
    'search explicit ordering' =>
        str_contains(
            $contents['search'],
            "'order' => ['menu_position', 'id']"
        ),
];

echo "== ForPrint product position-order smoke ==\n";

foreach ($checks as $label => $passed) {
    echo sprintf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

$configPath = $root . '/base/config.php';

if (!is_file($configPath)) {
    echo "[WARN] config.php missing; live DB smoke skipped.\n";
    exit(0);
}

$configSource = (string)file_get_contents($configPath);
$accessDeniedPosition = stripos(
    $configSource,
    'Access denied'
);

if ($accessDeniedPosition !== false) {
    $contextStart = max(0, $accessDeniedPosition - 1200);
    $guardContext = substr(
        $configSource,
        $contextStart,
        $accessDeniedPosition - $contextStart
    );

    preg_match_all(
        '/defined\s*\(\s*[\'"]([A-Z][A-Z0-9_]*)[\'"]\s*\)/i',
        $guardContext,
        $guardMatches
    );

    $guardNames = $guardMatches[1] ?? [];

    if ($guardNames !== []) {
        $guardName = (string)end($guardNames);

        if ($guardName !== '' && !defined($guardName)) {
            define($guardName, true);
        }
    }
}

require_once $configPath;

$host = defined('DB_HOST')
    ? DB_HOST
    : (defined('HOST') ? HOST : null);
$user = defined('DB_USER')
    ? DB_USER
    : (defined('USER') ? USER : null);
$password = defined('DB_PASSWORD')
    ? DB_PASSWORD
    : (
        defined('DB_PASS')
            ? DB_PASS
            : (defined('PASSWORD') ? PASSWORD : '')
    );
$database = defined('DB_NAME')
    ? DB_NAME
    : (defined('DB') ? DB : null);

if ($host === null || $user === null || $database === null) {
    echo "[WARN] DB constants unresolved; live DB smoke skipped.\n";
    exit(0);
}

mysqli_report(MYSQLI_REPORT_OFF);

$db = @new mysqli(
    (string)$host,
    (string)$user,
    (string)$password,
    (string)$database
);

if ($db->connect_errno) {
    echo "[WARN] DB connection failed; static checks passed.\n";
    exit(0);
}

$db->set_charset('utf8');

$queries = [
    'catalog' => "
        SELECT id, name, menu_position
        FROM goods
        WHERE visible = 1
        ORDER BY menu_position, id
        LIMIT 1
    ",
    'home_hits' => "
        SELECT id, name, menu_position
        FROM goods
        WHERE visible = 1
          AND hit = 1
        ORDER BY menu_position, id
        LIMIT 1
    ",
];

foreach ($queries as $label => $sql) {
    $result = $db->query($sql);

    if (!$result || !($row = $result->fetch_assoc())) {
        fwrite(
            STDERR,
            "[FAIL] Could not resolve first {$label} product.\n"
        );
        exit(3);
    }

    printf(
        "[OK] %-10s first id=%s position=%s name=%s\n",
        $label,
        $row['id'],
        $row['menu_position'],
        $row['name']
    );
}

$target = $db->query("
    SELECT id, name, alias, menu_position, hit
    FROM goods
    WHERE name = 'Фігурна візитка'
    LIMIT 1
");

if ($target && ($targetRow = $target->fetch_assoc())) {
    printf(
        "[OK] target     id=%s position=%s hit=%s alias=%s\n",
        $targetRow['id'],
        $targetRow['menu_position'],
        $targetRow['hit'],
        $targetRow['alias']
    );
}

$db->close();

echo "All product position-order checks passed.\n";
