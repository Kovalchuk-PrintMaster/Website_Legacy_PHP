<?php

declare(strict_types=1);

/**
 * ForPrint cumulative home component-extraction smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$paths = [
    'index' =>
        $root . '/base/templates/default/index.php',
    'hero' =>
        $root
        . '/base/templates/default/surfaces/home/heroSlider.php',
    'product_groups' =>
        $root
        . '/base/templates/default/surfaces/home/productGroups.php',
    'about' =>
        $root
        . '/base/templates/default/surfaces/home/about.php',
    'advantages' =>
        $root
        . '/base/templates/default/surfaces/home/advantages.php',
    'functional_smoke' =>
        $root
        . '/scripts/inspection/check_website_home_functional_contract.php',
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

$checks = [
    'home index includes all extracted components' =>
        str_contains(
            $content['index'],
            "/surfaces/home/heroSlider.php"
        )
        && str_contains(
            $content['index'],
            "/surfaces/home/productGroups.php"
        )
        && str_contains(
            $content['index'],
            "/surfaces/home/about.php"
        )
        && str_contains(
            $content['index'],
            "/surfaces/home/advantages.php"
        ),
    'home index no longer owns extracted sections' =>
        !str_contains(
            $content['index'],
            '<section class="slider">'
        )
        && !str_contains(
            $content['index'],
            '<section class="offers">'
        )
        && !str_contains(
            $content['index'],
            '<section class="about">'
        )
        && !str_contains(
            $content['index'],
            '<section class="advantages">'
        ),
    'hero component retains complete slider' =>
        str_contains(
            $content['hero'],
            '$sales'
        )
        && str_contains(
            $content['hero'],
            '<section class="slider">'
        )
        && str_contains(
            $content['hero'],
            'swiper-pagination'
        ),
    'product groups retain tabs and shared cards' =>
        str_contains(
            $content['product_groups'],
            '$goods'
        )
        && str_contains(
            $content['product_groups'],
            '$arrHits'
        )
        && str_contains(
            $content['product_groups'],
            '<section class="offers">'
        )
        && str_contains(
            $content['product_groups'],
            'goodsGridItem'
        ),
    'about component owns company information' =>
        str_contains(
            $content['about'],
            '<section class="about">'
        )
        && (
            str_contains(
                $content['about'],
                'short_content'
            )
            || str_contains(
                $content['about'],
                '$this->set'
            )
        ),
    'advantages component retains its condition and records' =>
        str_contains(
            $content['advantages'],
            '$advantages'
        )
        && str_contains(
            $content['advantages'],
            '<section class="advantages">'
        )
        && str_contains(
            $content['advantages'],
            'Наші переваги'
        ),
    'functional smoke composes extracted components' =>
        str_contains(
            $content['functional_smoke'],
            "surfaces/home/*.php"
        )
        && str_contains(
            $content['functional_smoke'],
            "\$content['template'] .="
        ),
];

echo "== ForPrint cumulative home component-extraction smoke ==\n";

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

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'ignore_errors' => true,
    ],
]);

$html = @file_get_contents(
    'http://127.0.0.1:8098/',
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

$runtimeChecks = [
    'home HTTP 200' =>
        $status === 200,
    'home surface remains marked' =>
        is_string($html)
        && str_contains(
            $html,
            'data-fp-surface="home"'
        ),
    'home legacy profile remains marked' =>
        is_string($html)
        && str_contains(
            $html,
            'data-fp-frontend-profile="legacy"'
        ),
    'slider remains rendered' =>
        is_string($html)
        && str_contains(
            $html,
            '<section class="slider">'
        ),
    'offers remain rendered' =>
        is_string($html)
        && str_contains(
            $html,
            '<section class="offers">'
        ),
    'about remains rendered' =>
        is_string($html)
        && str_contains(
            $html,
            '<section class="about">'
        ),
    'shared product cards remain rendered' =>
        is_string($html)
        && str_contains(
            $html,
            'fp-product-card'
        ),
    'controlled search remains rendered' =>
        is_string($html)
        && str_contains(
            $html,
            'data-fp-search-suggestions'
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

echo "All cumulative home component-extraction checks passed.\n";