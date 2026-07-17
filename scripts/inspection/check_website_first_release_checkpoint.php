<?php

declare(strict_types=1);

/**
 * ForPrint Website — first-release frontend checkpoint smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$files = [
    'add' =>
        $root . '/base/core/admin/controllers/AddController.php',
    'model' =>
        $root . '/base/core/user/models/Model.php',
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
    'endpoint' =>
        $root . '/base/search-suggestions.php',
    'search_helper' =>
        $root . '/base/libraries/ProductSearch.php',
    'phone_helper' =>
        $root . '/base/libraries/InternationalPhoneValidator.php',
];

foreach ($files as $label => $path) {
    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] Missing {$label}: {$path}\n"
        );
        exit(1);
    }
}

$content = [];

foreach ($files as $label => $path) {
    $content[$label] =
        (string)file_get_contents($path);
}

$checks = [
    'new goods default to visible' =>
        str_contains(
            $content['add'],
            "'visible' => 1"
        ),
    'promotion flags default to no' =>
        str_contains(
            $content['add'],
            "'hit' => 0"
        )
        && str_contains(
            $content['add'],
            "'sale' => 0"
        )
        && str_contains(
            $content['add'],
            "'new' => 0"
        )
        && str_contains(
            $content['add'],
            "'hot' => 0"
        ),
    'stable menu-position ordering exists' =>
        str_contains(
            $content['model'],
            'menu_position'
        ),
    'stable shared card rhythm exists' =>
        str_contains(
            $content['cards_css'],
            'FP product grid/related compact rhythm v0.6.24'
        ),
    'card titles are one line' =>
        str_contains(
            $content['cards_css'],
            'FP one-line product-card titles v0.6.26'
        )
        && str_contains(
            $content['cards_css'],
            'text-overflow: ellipsis !important;'
        ),
    'search suggestions use current CSS' =>
        str_contains(
            $content['search_css'],
            'ForPrint product search suggestions v0.6.26'
        ),
    'search list is attached to document body' =>
        str_contains(
            $content['search_js'],
            'document.body.appendChild(list);'
        ),
    'recent search history keeps eight entries' =>
        str_contains(
            $content['search_js'],
            'var historyLimit = 8;'
        ),
    'product suggestion renders image' =>
        str_contains(
            $content['search_js'],
            'settings.image'
        )
        && str_contains(
            $content['search_js'],
            'fp-search-suggestions__product-image'
        )
        && str_contains(
            $content['search_js'],
            'fp-search-suggestions__product-fallback'
        ),
    'shared product search uses stop words' =>
        str_contains(
            $content['search_helper'],
            'private const STOP_WORDS'
        ),
    'significant search terms use AND' =>
        str_contains(
            $content['search_helper'],
            "implode(' AND ', \$tokenGroups)"
        ),
    'suggestion endpoint returns image field' =>
        str_contains(
            $content['endpoint'],
            "'image' =>"
        ),
    'international phone helper exists' =>
        str_contains(
            $content['phone_helper'],
            'InternationalPhoneValidator'
        ),
    'current assets are loaded' =>
        str_contains(
            $content['header'],
            'forprint-product-cards.css?v=20260717-0693'
        )
        && str_contains(
            $content['header'],
            'forprint-search-suggestions.css?v=20260717-0694'
        )
        && str_contains(
            $content['header'],
            'forprint-search-submit.js?v=20260717-0695'
        ),
];

echo "== ForPrint first-release frontend checkpoint smoke ==\n";

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
require_once $files['search_helper'];

$pluralIds =
    ForPrintProductSearch::searchIds('візитки');
$specificIds =
    ForPrintProductSearch::searchIds(
        'Візитка з дизайнерського картону'
    );

printf(
    "[INFO] plural matches=%d specific matches=%d\n",
    count($pluralIds),
    count($specificIds)
);

if (count($pluralIds) < 2) {
    fwrite(
        STDERR,
        "[FAIL] Plural search returned too few products.\n"
    );
    exit(3);
}

if (count($specificIds) < 1) {
    fwrite(
        STDERR,
        "[FAIL] Specific search returned no products.\n"
    );
    exit(4);
}

$baseUrl = rtrim(
    (string)(
        getenv('FP_WEB_BASE_URL')
        ?: 'http://127.0.0.1:8098'
    ),
    '/'
);

$routes = [
    'home' => '/',
    'catalog' => '/catalog/?qty=12',
    'search_plural' =>
        '/search/?search='
        . rawurlencode('візитки'),
    'search_specific' =>
        '/search/?search='
        . rawurlencode(
            'Візитка з дизайнерського картону'
        ),
    'suggestions' =>
        '/search-suggestions.php?q='
        . rawurlencode('візитки'),
];

foreach ($routes as $label => $route) {
    $bodyPath = sys_get_temp_dir()
        . '/fp_first_release_' . $label . '.body';

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
        "[%s] %-16s status=%s size=%s\n",
        $passed ? 'OK' : 'FAIL',
        $label,
        $status !== '' ? $status : '-',
        $size !== '' ? $size : '-'
    );

    if (!$passed) {
        echo implode("\n", $output) . "\n";
        exit(5);
    }
}

$suggestionBody = (string)file_get_contents(
    sys_get_temp_dir()
    . '/fp_first_release_suggestions.body'
);
$payload = json_decode($suggestionBody, true);

if (
    !is_array($payload)
    || ($payload['ok'] ?? false) !== true
    || !isset($payload['items'])
    || !is_array($payload['items'])
    || count($payload['items']) < 1
) {
    fwrite(
        STDERR,
        "[FAIL] Suggestion endpoint payload is invalid.\n"
    );
    exit(6);
}

$firstItem = $payload['items'][0];

if (!array_key_exists('image', $firstItem)) {
    fwrite(
        STDERR,
        "[FAIL] Suggestion payload has no image field.\n"
    );
    exit(7);
}

$imageItem = null;

foreach ($payload['items'] as $item) {
    if (!empty($item['image'])) {
        $imageItem = $item;
        break;
    }
}

if (!is_array($imageItem)) {
    fwrite(
        STDERR,
        "[FAIL] Suggestion payload has no product image URL.\n"
    );
    exit(8);
}

$imageUrl = (string)$imageItem['image'];
$absoluteImageUrl = preg_match(
    '~^https?://~i',
    $imageUrl
) === 1
    ? $imageUrl
    : $baseUrl . '/' . ltrim($imageUrl, '/');

$imageCommand = sprintf(
    "curl -sS --max-time 20 "
    . "-o /tmp/fp_first_release_thumbnail.body "
    . "-w '%%{http_code} %%{size_download}' %s",
    escapeshellarg($absoluteImageUrl)
);

$imageOutput = [];
$imageExitCode = 0;

exec(
    $imageCommand . ' 2>&1',
    $imageOutput,
    $imageExitCode
);

$imageLastLine = $imageOutput
    ? trim(
        (string)$imageOutput[count($imageOutput) - 1]
    )
    : '';

[$imageStatus, $imageSize] = array_pad(
    preg_split('/\s+/', $imageLastLine, 2) ?: [],
    2,
    ''
);

$imagePassed = $imageExitCode === 0
    && $imageStatus === '200'
    && (int)$imageSize > 0;

printf(
    "[%s] thumbnail        status=%s size=%s url=%s\n",
    $imagePassed ? 'OK' : 'FAIL',
    $imageStatus !== '' ? $imageStatus : '-',
    $imageSize !== '' ? $imageSize : '-',
    $imageUrl
);

if (!$imagePassed) {
    echo implode("\n", $imageOutput) . "\n";
    exit(9);
}

echo "All first-release frontend checkpoint checks passed.\n";
