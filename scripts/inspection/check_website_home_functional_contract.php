<?php

declare(strict_types=1);

/**
 * ForPrint home functional-contract smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$paths = [
    'controller' =>
        $root
        . '/base/core/user/controllers/IndexController.php',
    'base_user' =>
        $root
        . '/base/core/user/controllers/BaseUser.php',
    'template' =>
        $root
        . '/base/templates/default/index.php',
    'card' =>
        $root
        . '/base/templates/default/include/goodsGridItem.php',
    'contract_md' =>
        $root
        . '/docs/reference/home_frontend_functional_contract_v0_1.md',
    'contract_yaml' =>
        $root
        . '/docs/reference/home_frontend_functional_contract_v0_1.yaml',
    'block_map' =>
        $root
        . '/docs/architecture/home_frontend_block_map_v0_1.md',
    'capability_yaml' =>
        $root
        . '/docs/reference/interface_capability_registry_v0_1.yaml',
    'docs_readme' =>
        $root . '/docs/README.md',
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

/**
 * Check a class token in legacy source markup without depending on
 * exact whitespace or on the class being the only class value.
 */
function fp_home_source_has_class(
    string $source,
    string $tag,
    string $className
): bool {
    $tagPattern = preg_quote($tag, '/');
    $classPattern = preg_quote($className, '/');

    return preg_match(
        '/<'
        . $tagPattern
        . '\b[^>]*\bclass\s*=\s*'
        . '(["\'])'
        . '[^"\']*\b'
        . $classPattern
        . '\b[^"\']*'
        . '\1'
        . '[^>]*>/i',
        $source
    ) === 1;
}
$componentPaths = glob(
    $root
    . '/base/templates/default/surfaces/home/*.php'
) ?: [];

sort($componentPaths);

foreach ($componentPaths as $componentPath) {
    $componentSource =
        file_get_contents($componentPath);

    if (!is_string($componentSource)) {
        fwrite(
            STDERR,
            "[FAIL] Could not read home component: {$componentPath}\n"
        );
        exit(1);
    }

    /*
     * Existing contract assertions continue to inspect one composed
     * home-template source even as sections move into owned files.
     */
    $content['template'] .=
        "\n"
        . $componentSource;
}
$checks = [
    'sales query remains visible and ordered' =>
        str_contains(
            $content['controller'],
            "\$this->model->get('sales'"
        )
        && str_contains(
            $content['controller'],
            "'where' => ['visible' => 1]"
        )
        && str_contains(
            $content['controller'],
            "'order' => ['menu_position']"
        ),
    'advantages query remains limited to six' =>
        str_contains(
            $content['controller'],
            "\$this->model->get('advantages'"
        )
        && str_contains(
            $content['controller'],
            "'limit' => 6"
        ),
    'news query remains limited to three' =>
        str_contains(
            $content['controller'],
            "\$this->model->get('news'"
        )
        && str_contains(
            $content['controller'],
            "'limit' => 3"
        ),
    'four home product groups remain declared' =>
        str_contains(
            $content['controller'],
            "'hit' =>"
        )
        && str_contains(
            $content['controller'],
            "'hot'=>"
        )
        && str_contains(
            $content['controller'],
            "'new' =>"
        )
        && str_contains(
            $content['controller'],
            "'sale'=>"
        ),
    'home products remain stable and limited' =>
        str_contains(
            $content['controller'],
            "'order' => ['menu_position', 'id']"
        )
        && str_contains(
            $content['controller'],
            "'order_direction' => ['ASC', 'ASC']"
        )
        && str_contains(
            $content['controller'],
            "'limit' => 6"
        ),
    'shared shell render chain remains visible' =>
        str_contains(
            $content['base_user'],
            "TEMPLATE . 'include/header'"
        )
        && str_contains(
            $content['base_user'],
            "TEMPLATE . 'include/footer'"
        )
        && str_contains(
            $content['base_user'],
            "TEMPLATE . 'layout/default'"
        ),
    'home template retains core blocks' =>
        fp_home_source_has_class(
            $content['template'],
            'section',
            'slider'
        )
        && fp_home_source_has_class(
            $content['template'],
            'section',
            'offers'
        )
        && fp_home_source_has_class(
            $content['template'],
            'section',
            'about'
        )
        && fp_home_source_has_class(
            $content['template'],
            'section',
            'advantages'
        )
        && fp_home_source_has_class(
            $content['template'],
            'section',
            'feedback'
        )
        && fp_home_source_has_class(
            $content['template'],
            'section',
            'news'
        )
        && fp_home_source_has_class(
            $content['template'],
            'form',
            'search'
        ),
    'home product groups use shared grid card' =>
        str_contains(
            $content['template'],
            "'goodsGridItem'"
        )
        && str_contains(
            $content['card'],
            'fp-product-card'
        ),
    'human contract records approved feedback hiding' =>
        str_contains(
            $content['contract_md'],
            'approved_to_hide'
        )
        && str_contains(
            $content['contract_md'],
            'legacy_presentation_only'
        )
        && str_contains(
            $content['contract_md'],
            '`controlled_v1`: hide the form from the public interface'
        ),
    'machine contract records all seven blocks' =>
        substr_count(
            $content['contract_yaml'],
            '  - id: '
        ) >= 7
        && str_contains(
            $content['contract_yaml'],
            'id: home_search'
        ),
    'capability registry approves feedback hiding' =>
        str_contains(
            $content['capability_yaml'],
            'id: home_feedback_form'
        )
        && str_contains(
            $content['capability_yaml'],
            'status: approved_to_hide'
        )
        && str_contains(
            $content['capability_yaml'],
            'assessment: legacy_presentation_only'
        )
        && str_contains(
            $content['capability_yaml'],
            'controlled_v1: hidden'
        ),
    'block map defines controlled components' =>
        str_contains(
            $content['block_map'],
            'HomeHeroSlider'
        )
        && str_contains(
            $content['block_map'],
            'HomeProductGroups'
        )
        && str_contains(
            $content['block_map'],
            'HomeAdvantages'
        ),
    'documentation index references home contract' =>
        str_contains(
            $content['docs_readme'],
            '## Home frontend contract'
        )
        && str_contains(
            $content['docs_readme'],
            'Home Frontend Block Map v0.1'
        ),
];

echo "== ForPrint home functional-contract smoke ==\n";

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

$url = 'http://127.0.0.1:8098/';

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'ignore_errors' => true,
    ],
]);

$html = @file_get_contents(
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

printf(
    "[%s] home HTTP status=%d bytes=%d\n",
    $status === 200 ? 'OK' : 'FAIL',
    $status,
    is_string($html) ? strlen($html) : 0
);

if ($status !== 200 || !is_string($html)) {
    exit(3);
}

$runtimeChecks = [
    'main landmark' =>
        str_contains($html, '<main'),
    'shared product card' =>
        str_contains($html, 'fp-product-card'),
    'controlled search suggestions' =>
        str_contains(
            $html,
            'data-fp-search-suggestions'
        ),
    'legacy feedback form remains discoverable' =>
        str_contains($html, 'feedback__form'),
];

foreach ($runtimeChecks as $label => $passed) {
    printf(
        "[%s] runtime %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(4);
    }
}

echo "All home functional-contract checks passed.\n";
