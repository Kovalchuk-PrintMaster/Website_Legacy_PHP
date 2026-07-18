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
    'feedback' =>
        $root
        . '/base/templates/default/surfaces/home/feedback.php',
    'news' =>
        $root
        . '/base/templates/default/surfaces/home/news.php',
    'functional_smoke' =>
        $root
        . '/scripts/inspection/check_website_home_functional_contract.php',
    /* FP_HOME_SEARCH_COMPONENT_PATHS */
    'search' =>
        $root
        . '/base/templates/default/surfaces/home/search.php',
    'header' =>
        $root
        . '/base/templates/default/include/header.php',
    'search_js' =>
        $root
        . '/base/templates/default/assets/js/forprint-search-submit.js',
    'search_controller' =>
        $root
        . '/base/core/user/controllers/SearchController.php',
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
        )
        && str_contains(
            $content['index'],
            "/surfaces/home/feedback.php"
        )
        && str_contains(
            $content['index'],
            "\$this->frontendProfile !== 'controlled_v1'"
        ),
    /* FP_HOME_NEWS_COMPONENT_COMPOSITION */
    'news component is composed from home index' =>
        str_contains(
            $content['index'],
            "/surfaces/home/news.php"
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
        )
        && !str_contains(
            $content['index'],
            '<section class="feedback'
        ),
    /* FP_HOME_NEWS_COMPONENT_OWNERSHIP */
    'home index no longer owns news section markup' =>
        !str_contains(
            $content['index'],
            '<section class="news"'
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
    'advantages component retains condition and records' =>
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
    /* FP_HOME_NEWS_COMPONENT_CONTRACT */
    'news component retains conditional rendering and navigation' =>
        str_contains(
            $content['news'],
            'if (!empty($news))'
        )
        && str_contains(
            $content['news'],
            '<section class="news">'
        )
        && str_contains(
            $content['news'],
            'foreach ($news as $item)'
        )
        && str_contains(
            $content['news'],
            "'newsItem'"
        )
        && str_contains(
            $content['news'],
            "\$this->alias('news')"
        )
        && str_contains(
            $content['news'],
            'Переглянути все'
        ),
    'feedback component retains legacy presentation contract' =>
        str_contains(
            $content['feedback'],
            '<section class="feedback'
        )
        && str_contains(
            $content['feedback'],
            '<form action="index.html" class="feedback__form">'
        )
        && str_contains(
            $content['feedback'],
            'feedback__input'
        )
        && str_contains(
            $content['feedback'],
            'feedback__textarea'
        )
        && str_contains(
            $content['feedback'],
            'feedback__privacy'
        )
        && str_contains(
            $content['feedback'],
            'feedback__submit'
        ),
    'feedback component does not claim a native payload contract' =>
        preg_match(
            '/<(input|textarea|select)\b[^>]*\bname\s*=/i',
            $content['feedback']
        ) !== 1,
    /* FP_HOME_NEWS_FUNCTIONAL_SMOKE_COMPOSITION */
    'functional smoke composes news component' =>
        str_contains(
            $content['functional_smoke'],
            "\$content['news']"
        )
        && str_contains(
            $content['functional_smoke'],
            "/surfaces/home/news.php"
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
    /* FP_HOME_SEARCH_COMPONENT_COMPOSITION */
    'home search component is composed from home index' =>
        str_contains(
            $content['index'],
            "/surfaces/home/search.php"
        ),

    /* FP_HOME_SEARCH_COMPONENT_OWNERSHIP */
    'home index no longer owns home search form markup' =>
        !str_contains(
            $content['index'],
            '<form class="search "'
        ),

    /* FP_HOME_SEARCH_COMPONENT_CONTRACT */
    'home search component retains shared search contract' =>
        str_contains(
            $content['search'],
            '<form class="search "'
        )
        && str_contains(
            $content['search'],
            "\$this->alias('search')"
        )
        && str_contains(
            $content['search'],
            'data-fp-search-suggestions'
        )
        && str_contains(
            $content['search'],
            'name="search"'
        )
        && str_contains(
            $content['search'],
            'type="search"'
        )
        && str_contains(
            $content['search'],
            '<button>'
        ),

    /* FP_HEADER_SEARCH_SHARED_INSTANCE */
    'header search remains a separate shared instance' =>
        str_contains(
            $content['header'],
            'class="search search-internal"'
        )
        && str_contains(
            $content['header'],
            "\$this->alias('search')"
        )
        && str_contains(
            $content['header'],
            'data-fp-search-suggestions'
        )
        && str_contains(
            $content['header'],
            'name="search"'
        ),

    /* FP_SHARED_SEARCH_RUNTIME_CONTRACT */
    'shared search JavaScript remains form-scoped' =>
        str_contains(
            $content['search_js'],
            'form.search[data-fp-search-suggestions]'
        )
        && str_contains(
            $content['search_js'],
            'querySelectorAll(formSelector)'
        )
        && str_contains(
            $content['search_js'],
            'form.querySelector'
        )
        && (
            str_contains(
                $content['search_js'],
                'fpSearchSuggestions'
            )
            || str_contains(
                $content['search_js'],
                'data-fp-search-suggestions'
            )
        )
        && str_contains(
            $content['search_js'],
            'form.requestSubmit()'
        )
        && str_contains(
            $content['search_js'],
            'window.location.assign(url)'
        ),

    /* FP_SEARCH_CONTROLLER_OWNER */
    'SearchController remains full-results owner' =>
        str_contains(
            $content['search_controller'],
            'class SearchController'
        )
        && str_contains(
            $content['search_controller'],
            "\$_GET['search']"
        )
        && str_contains(
            $content['search_controller'],
            'searchGoodsIds'
        )
        && str_contains(
            $content['search_controller'],
            "get('goods'"
        ),

    /* FP_HOME_SEARCH_FUNCTIONAL_SMOKE_COMPOSITION */
    'functional smoke composes home search component' =>
        str_contains(
            $content['functional_smoke'],
            "\$content['search']"
        )
        && str_contains(
            $content['functional_smoke'],
            "/surfaces/home/search.php"
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
    'legacy feedback remains rendered' =>
        is_string($html)
        && str_contains(
            $html,
            '<section class="feedback '
        )
        && str_contains(
            $html,
            '<form action="index.html" class="feedback__form">'
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
    /* FP_HOME_NEWS_RUNTIME */
    'runtime news remains rendered' =>
        str_contains(
            $html,
            '<section class="news">'
        )
        && str_contains(
            $html,
            '>Новини<'
        )
        && str_contains(
            $html,
            '/news/'
        ),
    /* FP_HOME_SEARCH_RUNTIME */
    'home search component remains rendered' =>
        is_string($html)
        && str_contains(
            $html,
            '<form class="search "'
        )
        && str_contains(
            $html,
            'data-fp-search-suggestions'
        )
        && str_contains(
            $html,
            'name="search"'
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