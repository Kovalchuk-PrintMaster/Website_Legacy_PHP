<?php

declare(strict_types=1);

/**
 * ForPrint controlled home surface-boundary smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$paths = [
    'base_user' =>
        $root . '/base/core/user/controllers/BaseUser.php',
    'index_controller' =>
        $root . '/base/core/user/controllers/IndexController.php',
    'header' =>
        $root . '/base/templates/default/include/header.php',
    'layout' =>
        $root . '/base/templates/default/layout/default.php',
    'home_css' =>
        $root . '/base/templates/default/assets/css/surfaces/home.css',
    'home_js' =>
        $root . '/base/templates/default/assets/js/surfaces/home.js',
];

$content = [];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] Missing {$label}: {$path}\n"
        );
        exit(1);
    }

    $content[$label] =
        (string)file_get_contents($path);
}

$mainSource =
    $content['header']
    . "\n"
    . $content['layout'];

$checks = [
    'BaseUser owns presentation metadata' =>
        str_contains(
            $content['base_user'],
            'protected $frontendSurface'
        )
        && str_contains(
            $content['base_user'],
            'protected $frontendProfile'
        ),
    'home controller selects legacy profile' =>
        str_contains(
            $content['index_controller'],
            "\$this->frontendSurface = 'home';"
        )
        && str_contains(
            $content['index_controller'],
            "\$this->frontendProfile = 'legacy';"
        ),
    'home controller owns CSS entrypoint' =>
        str_contains(
            $content['index_controller'],
            'assets/css/surfaces/home.css?v=20260717-0001'
        ),
    'home controller owns JavaScript entrypoint' =>
        str_contains(
            $content['index_controller'],
            'assets/js/surfaces/home.js?v=20260717-0001'
        ),
    'existing main receives conditional markers' =>
        str_contains(
            $mainSource,
            'data-fp-surface='
        )
        && str_contains(
            $mainSource,
            'data-fp-frontend-profile='
        ),
    'home CSS is surface scoped' =>
        str_contains(
            $content['home_css'],
            '[data-fp-surface="home"]'
        )
        && !str_contains(
            $content['home_css'],
            '!important'
        ),
    'home JavaScript is optional and scoped' =>
        str_contains(
            $content['home_js'],
            '[data-fp-surface="home"]'
        )
        && str_contains(
            $content['home_js'],
            'if (!homeRoot)'
        )
        && str_contains(
            $content['home_js'],
            'data-fp-home-script'
        ),
];

echo "== ForPrint controlled home surface-boundary smoke ==\n";

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

/**
 * @return array{status:int, body:string}
 */
function fp_home_boundary_fetch(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents(
        $url,
        false,
        $context
    );

    $status = 0;

    foreach ($http_response_header ?? [] as $header) {
        if (
            preg_match(
                '#^HTTP/\S+\s+(\d{3})#',
                $header,
                $matches
            ) === 1
        ) {
            $status = (int)$matches[1];
        }
    }

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
    ];
}

$home = fp_home_boundary_fetch(
    'http://127.0.0.1:8098/'
);

$catalog = fp_home_boundary_fetch(
    'http://127.0.0.1:8098/catalog'
);

$runtimeChecks = [
    'home HTTP 200' =>
        $home['status'] === 200,
    'home has surface marker' =>
        str_contains(
            $home['body'],
            'data-fp-surface="home"'
        ),
    'home has legacy profile marker' =>
        str_contains(
            $home['body'],
            'data-fp-frontend-profile="legacy"'
        ),
    'home loads owned CSS' =>
        str_contains(
            $home['body'],
            'assets/css/surfaces/home.css?v=20260717-0001'
        ),
    'home loads owned JavaScript' =>
        str_contains(
            $home['body'],
            'assets/js/surfaces/home.js?v=20260717-0001'
        ),
    'home still renders shared product cards' =>
        str_contains(
            $home['body'],
            'fp-product-card'
        ),
    'home still renders controlled search' =>
        str_contains(
            $home['body'],
            'data-fp-search-suggestions'
        ),
    'catalog HTTP 200' =>
        $catalog['status'] === 200,
    'catalog does not load home CSS' =>
        !str_contains(
            $catalog['body'],
            'assets/css/surfaces/home.css'
        ),
    'catalog does not load home JavaScript' =>
        !str_contains(
            $catalog['body'],
            'assets/js/surfaces/home.js'
        ),
    'catalog is not marked as home' =>
        !str_contains(
            $catalog['body'],
            'data-fp-surface="home"'
        ),
];

foreach ($runtimeChecks as $label => $passed) {
    printf(
        "[%s] runtime %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(3);
    }
}

echo "All controlled home surface-boundary checks passed.\n";
