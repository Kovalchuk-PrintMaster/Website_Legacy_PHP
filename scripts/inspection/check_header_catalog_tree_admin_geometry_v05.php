<?php
/* FP_HEADER_CATALOG_TREE_ADMIN_GEOMETRY_V05_CHECKER */

$root = dirname(__DIR__, 2);

$paths = [
    'shell' => $root . '/base/templates/default/assets/css/forprint-shell.css',
    'internal' => $root . '/base/core/base/settings/internal_settings.php',
    'tech_template' => $root . '/base/templates/default/technicalrequirements.php',
    'tech_css' => $root . '/base/templates/default/assets/css/forprint-technical-requirements.css',
    'app_js' => $root . '/base/templates/default/assets/js/app.js',
    'admin_css' => $root . '/base/core/admin/views/css/forprint-admin-technical-requirements.css',
    'admin_js' => $root . '/base/core/admin/views/js/forprint-admin-technical-requirements.js',
];

$src = [];
$failures = [];

foreach ($paths as $key => $path) {
    if (!is_file($path)) {
        $failures[] = "{$key}: missing";
        continue;
    }

    $src[$key] = (string)file_get_contents($path);
}

if (!$failures) {
    $checks = [
        'single header v2 owner' =>
            substr_count(
                $src['shell'],
                'FP_HEADER_CANONICAL_GEOMETRY_V2'
            ) === 1
            && !str_contains(
                $src['shell'],
                'FP_HEADER_CANONICAL_GEOMETRY_V1'
            )
            && !str_contains(
                $src['shell'],
                'FP_TECHREQ_HEADER_REBALANCE_'
            ),
        'slogan uses relative offset' =>
            str_contains(
                $src['shell'],
                '.fp-site-header .fp-site-header__wrapper .fp-site-header__slogan'
            )
            && str_contains(
                $src['shell'],
                'top: -1.1rem'
            )
            && str_contains(
                $src['shell'],
                'transform: none'
            ),
        'catalog stylesheet registered' =>
            str_contains(
                $src['internal'],
                'forprint-catalog.css'
            ),
        'technical tree uses catalog contract' =>
            str_contains(
                $src['tech_template'],
                'FP_TECHREQ_TEMPLATE_V3_CATALOG_TREE'
            )
            && str_contains(
                $src['tech_template'],
                'catalog-filter-item__toggle'
            )
            && str_contains(
                $src['tech_template'],
                'catalog-filter-item__count'
            ),
        'technical css does not own parallel nav typography' =>
            !str_contains(
                $src['tech_css'],
                '.fp-techreq-nav__link'
            )
            && !str_contains(
                $src['tech_css'],
                '.fp-techreq-nav__heading'
            ),
        'catalog toggle controller exists' =>
            str_contains(
                $src['app_js'],
                'catalog-filter-item_open'
            )
            && str_contains(
                $src['app_js'],
                'tweenToggle'
            ),
        'admin layout v3 full-span groups' =>
            str_contains(
                $src['admin_css'],
                'FP_ADMIN_TECHREQ_LAYOUT_V3'
            )
            && str_contains(
                $src['admin_css'],
                'grid-column: 1 / -1'
            ),
        'admin exact wrapper v3' =>
            str_contains(
                $src['admin_js'],
                'FP_ADMIN_TECHREQ_LAYOUT_JS_V3'
            )
            && str_contains(
                $src['admin_js'],
                "field.closest('.vg-element')"
            )
            && str_contains(
                $src['admin_js'],
                'fp-admin-techreq-field--'
            )
            && !str_contains(
                $src['admin_js'],
                'closestCard'
            )
            && !str_contains(
                $src['admin_js'],
                'markByText'
            ),
    ];

    foreach ($checks as $name => $ok) {
        echo sprintf(
            "[%s] %s\n",
            $ok ? 'OK' : 'FAIL',
            $name
        );

        if (!$ok) {
            $failures[] = $name;
        }
    }
}

if ($failures) {
    fwrite(
        STDERR,
        "FP_HEADER_CATALOG_TREE_ADMIN_GEOMETRY_V05=FAIL\n"
    );

    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(2);
}

echo "FP_HEADER_CATALOG_TREE_ADMIN_GEOMETRY_V05=PASS\n";
