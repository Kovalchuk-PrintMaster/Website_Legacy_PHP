<?php

declare(strict_types=1);

/**
 * ForPrint search precision/card-title smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$paths = [
    'helper' =>
        $root . '/base/libraries/ProductSearch.php',
    'endpoint' =>
        $root . '/base/search-suggestions.php',
    'cards_css' =>
        $root
        . '/base/templates/default/assets/css/'
        . 'forprint-product-cards.css',
    'search_css' =>
        $root
        . '/base/templates/default/assets/css/'
        . 'forprint-search-suggestions.css',
    'search_js' =>
        $root
        . '/base/templates/default/assets/js/'
        . 'forprint-search-submit.js',
    'header' =>
        $root
        . '/base/templates/default/include/header.php',
];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] Missing {$label}: {$path}\n"
        );
        exit(1);
    }
}

$helper = (string)file_get_contents($paths['helper']);
$endpoint = (string)file_get_contents($paths['endpoint']);
$cardsCss = (string)file_get_contents($paths['cards_css']);
$searchCss = (string)file_get_contents($paths['search_css']);
$searchJs = (string)file_get_contents($paths['search_js']);
$header = (string)file_get_contents($paths['header']);

$checks = [
    'service words are excluded' =>
        str_contains(
            $helper,
            'private const STOP_WORDS'
        )
        && str_contains(
            $helper,
            "'з' => true"
        )
        && str_contains(
            $helper,
            "'на' => true"
        ),
    'significant terms use AND groups' =>
        str_contains(
            $helper,
            "implode(' AND ', \$tokenGroups)"
        ),
    'search helper selects product image' =>
        str_contains(
            $helper,
            'g.img,'
        )
        && str_contains(
            $helper,
            "'img' => \$img"
        ),
    'endpoint uses current image resolver' =>
        str_contains(
            $endpoint,
            "'image' => fp_search_suggestions_public_asset_url("
        )
        && str_contains(
            $endpoint,
            ": 'userfiles/';"
        ),
    'all card titles use one line' =>
        str_contains(
            $cardsCss,
            'FP one-line product-card titles v0.6.26'
        )
        && str_contains(
            $cardsCss,
            'white-space: nowrap !important;'
        )
        && str_contains(
            $cardsCss,
            'text-overflow: ellipsis !important;'
        ),
    'recent arrow points right' =>
        str_contains(
            $searchCss,
            "content: '→';"
        )
        && !str_contains(
            $searchCss,
            "content: '↶';"
        ),
    'product thumbnail style exists' =>
        str_contains(
            $searchCss,
            '.fp-search-suggestions__product-image'
        ),
    'fallback marker is neutral grey' =>
        str_contains(
            $searchCss,
            '.fp-search-suggestions__product-fallback'
        )
        && str_contains(
            $searchCss,
            'background: #a8b2b8;'
        ),
    'JavaScript renders item image' =>
        str_contains(
            $searchJs,
            'settings.image'
        )
        && str_contains(
            $searchJs,
            'fp-search-suggestions__product-image'
        ),
    'history maximum remains eight' =>
        str_contains(
            $searchJs,
            'var historyLimit = 8;'
        ),
    'asset versions refreshed' =>
        str_contains(
            $header,
            'forprint-product-cards.css?v=20260717-0693'
        )
        && str_contains(
            $header,
            'forprint-search-suggestions.css?v=20260717-0694'
        )
        && str_contains(
            $header,
            'forprint-search-submit.js?v=20260717-0695'
        ),
];

echo "== ForPrint search precision/card-title smoke ==\n";

foreach ($checks as $label => $passed) {
    printf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

define('VG_ACCESS', true);
require_once $root . '/base/config.php';
require_once $paths['helper'];

mysqli_report(MYSQLI_REPORT_OFF);

$db = @new mysqli(
    (string)HOST,
    (string)USER,
    (string)PASSWORD,
    (string)DB_NAME
);

if ($db->connect_errno) {
    fwrite(STDERR, "[FAIL] Database connection failed.\n");
    exit(3);
}

$totalResult = $db->query(
    'SELECT COUNT(*) AS total '
    . 'FROM goods WHERE visible = 1'
);
$totalRow = $totalResult
    ? $totalResult->fetch_assoc()
    : null;
$totalVisible = (int)($totalRow['total'] ?? 0);
$db->close();

$queries = [
    'plural' => 'візитки',
    'plastic' => 'Візитки на пластиковій основі',
    'designer' => 'Візитка з дизайнерського картону',
];

$counts = [];

foreach ($queries as $label => $query) {
    $ids = ForPrintProductSearch::searchIds($query);
    $counts[$label] = count($ids);

    printf(
        "[INFO] %-9s matches=%d ids=%s\n",
        $label,
        count($ids),
        implode(',', array_slice($ids, 0, 12))
    );
}

if ($counts['plural'] < 2) {
    fwrite(
        STDERR,
        "[FAIL] Plural query returned too few products.\n"
    );
    exit(4);
}

foreach (['plastic', 'designer'] as $label) {
    if (
        $counts[$label] < 1
        || $counts[$label] >= $totalVisible
    ) {
        fwrite(
            STDERR,
            "[FAIL] Specific query {$label} is not selective.\n"
        );
        exit(5);
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
    'suggest_designer' =>
        '/search-suggestions.php?q='
        . rawurlencode(
            'Візитка з дизайнерського картону'
        ),
    'search_designer' =>
        '/search/?search='
        . rawurlencode(
            'Візитка з дизайнерського картону'
        ),
];

foreach ($routes as $label => $route) {
    $bodyPath = sys_get_temp_dir()
        . '/fp_search_precision_' . $label . '.body';

    $command = sprintf(
        "curl -sS --max-time 20 "
        . "-o %s "
        . "-w '%%{http_code} %%{size_download}' %s",
        escapeshellarg($bodyPath),
        escapeshellarg($baseUrl . $route)
    );

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    $lastLine = $output
        ? trim((string)$output[count($output) - 1])
        : '';
    [$status, $size] = array_pad(
        preg_split('/\s+/', $lastLine, 2) ?: [],
        2,
        ''
    );

    $passed = $exitCode === 0
        && $status === '200'
        && (int)$size > 0;

    printf(
        "[%s] %-17s status=%s size=%s\n",
        $passed ? 'OK' : 'FAIL',
        $label,
        $status !== '' ? $status : '-',
        $size !== '' ? $size : '-'
    );

    if (!$passed) {
        echo implode("\n", $output) . "\n";
        exit(6);
    }
}

$suggestionBody = (string)file_get_contents(
    sys_get_temp_dir()
    . '/fp_search_precision_suggest_designer.body'
);
$payload = json_decode($suggestionBody, true);

if (
    !is_array($payload)
    || ($payload['ok'] ?? false) !== true
    || empty($payload['items'])
) {
    fwrite(
        STDERR,
        "[FAIL] Suggestion endpoint returned no precise items.\n"
    );
    exit(7);
}

$first = $payload['items'][0];

printf(
    "[INFO] first suggestion=%s image=%s\n",
    (string)($first['name'] ?? ''),
    !empty($first['image']) ? 'yes' : 'no'
);

if (!array_key_exists('image', $first)) {
    fwrite(
        STDERR,
        "[FAIL] Suggestion payload has no image field.\n"
    );
    exit(8);
}

echo "All search precision/card-title checks passed.\n";
