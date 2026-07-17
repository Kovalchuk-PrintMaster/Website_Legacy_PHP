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
    'home index includes hero component' =>
        str_contains(
            $content['index'],
            "/surfaces/home/heroSlider.php"
        ),
    'home index includes product-groups component' =>
        str_contains(
            $content['index'],
            "/surfaces/home/productGroups.php"
        ),
    'home index no longer owns slider section' =>
        !str_contains(
            $content['index'],
            '<section class="slider">'
        ),
    'home index no longer owns offers section' =>
        !str_contains(
            $content['index'],
            '<section class="offers">'
        ),
    'hero component retains sales condition' =>
        str_contains(
            $content['hero'],
            '$sales'
        )
        && str_contains(
            $content['hero'],
            'if(!empty($sales))'
        ),
    'hero component retains complete slider controls' =>
        str_contains(
            $content['hero'],
            '<section class="slider">'
        )
        && str_contains(
            $content['hero'],
            'swiper-pagination'
        )
        && str_contains(
            $content['hero'],
            'swiper-button-prev'
        )
        && str_contains(
            $content['hero'],
            'swiper-button-next'
        ),
    'product-groups component retains outer condition' =>
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
        ),
    'product-groups component retains tabs and cards' =>
        str_contains(
            $content['product_groups'],
            'offers__tabs_header'
        )
        && str_contains(
            $content['product_groups'],
            'offers__tabs_content'
        )
        && str_contains(
            $content['product_groups'],
            'fp-home-grid'
        )
        && str_contains(
            $content['product_groups'],
            'goodsGridItem'
        ),
    'product-groups component retains catalogue navigation' =>
        str_contains(
            $content['product_groups'],
            'Переглянути каталог товарів'
        )
        && str_contains(
            $content['product_groups'],
            "\$this->alias('catalog')"
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
    'offers section remains rendered' =>
        is_string($html)
        && str_contains(
            $html,
            '<section class="offers">'
        ),
    'offer tabs remain rendered' =>
        is_string($html)
        && str_contains(
            $html,
            'offers__tabs_header'
        )
        && str_contains(
            $html,
            'offers__tabs_content'
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