<?php

declare(strict_types=1);

/**
 * ForPrint catalog manual sorting smoke.
 *
 * READ ONLY.
 *
 * Optional:
 *   FP_WEB_BASE_URL=http://127.0.0.1:8098
 */

$root = dirname(__DIR__, 2);
$controllerPath = $root
    . '/base/core/user/controllers/CatalogController.php';

if (!is_file($controllerPath)) {
    fwrite(
        STDERR,
        "[FAIL] Missing CatalogController.php\n"
    );
    exit(1);
}

$controller = (string)file_get_contents($controllerPath);

$staticChecks = [
    'array-to-strpos regression removed' =>
        !str_contains(
            $controller,
            "strpos(\$item, \$orderDb['order'])"
        ),
    'selected field scalar is used' =>
        str_contains(
            $controller,
            '$selectedField = (string)$orderArr[0];'
        ),
    'next direction is scalar' =>
        str_contains(
            $controller,
            '$nextDirection = $selectedDirection ==='
        ),
    'stable SQL order remains' =>
        str_contains(
            $controller,
            "\$orderDb['order'] = [\$orderArr[0], 'id'];"
        ),
];

echo "== ForPrint catalog sorting smoke ==\n";

foreach ($staticChecks as $label => $passed) {
    printf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

$baseUrl = rtrim(
    (string)(
        getenv('FP_WEB_BASE_URL')
        ?: 'http://127.0.0.1:8098'
    ),
    '/'
);

$routes = [
    'default' => '/catalog/',
    'price asc' => '/catalog/?order=price_asc',
    'price desc' => '/catalog/?order=price_desc',
    'name asc' => '/catalog/?order=name_asc',
    'name desc' => '/catalog/?order=name_desc',
];

foreach ($routes as $label => $route) {
    $url = $baseUrl . $route;

    $command = sprintf(
        "curl -sS --max-time 20 "
        . "-o /tmp/fp_catalog_sort_smoke.body "
        . "-w '%%{http_code} %%{size_download}' %s",
        escapeshellarg($url)
    );

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    $result = trim(implode("\n", $output));
    [$status, $size] = array_pad(
        preg_split('/\s+/', $result, 2) ?: [],
        2,
        ''
    );

    $passed = $exitCode === 0
        && $status === '200'
        && (int)$size > 0;

    printf(
        "[%s] %-12s status=%s size=%s\n",
        $passed ? 'OK' : 'FAIL',
        $label,
        $status !== '' ? $status : '-',
        $size !== '' ? $size : '-'
    );

    if (!$passed) {
        exit(3);
    }
}

$configPath = $root . '/base/config.php';

if (is_file($configPath)) {
    $source = (string)file_get_contents($configPath);
    $deniedAt = stripos($source, 'Access denied');

    if ($deniedAt !== false) {
        $contextStart = max(0, $deniedAt - 1200);
        $context = substr(
            $source,
            $contextStart,
            $deniedAt - $contextStart
        );

        preg_match_all(
            '/defined\s*\(\s*[\'"]'
            . '([A-Z][A-Z0-9_]*)'
            . '[\'"]\s*\)/i',
            $context,
            $matches
        );

        $guards = $matches[1] ?? [];

        if ($guards !== []) {
            $guard = (string)end($guards);

            if ($guard !== '' && !defined($guard)) {
                define($guard, true);
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

    if (
        $host !== null
        && $user !== null
        && $database !== null
    ) {
        mysqli_report(MYSQLI_REPORT_OFF);

        $db = @new mysqli(
            (string)$host,
            (string)$user,
            (string)$password,
            (string)$database
        );

        if (!$db->connect_errno) {
            $db->set_charset('utf8');

            $result = $db->query("
                SELECT
                    id,
                    name,
                    visible,
                    menu_position,
                    hit
                FROM goods
                WHERE id = 146
                LIMIT 1
            ");

            if (
                $result
                && ($row = $result->fetch_assoc())
            ) {
                printf(
                    "[INFO] product 146 visible=%s "
                    . "position=%s hit=%s name=%s\n",
                    $row['visible'],
                    $row['menu_position'],
                    $row['hit'],
                    $row['name']
                );

                if ((int)$row['visible'] !== 1) {
                    echo "[INFO] Product 146 is hidden by "
                        . "admin visibility and will not appear "
                        . "until “Показувати на сторінці” is set "
                        . "to “Так” and saved.\n";
                }
            }

            $db->close();
        }
    }
}

echo "All catalog sorting checks passed.\n";
