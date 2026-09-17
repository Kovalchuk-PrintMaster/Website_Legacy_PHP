<?php
/* FP_COMPONENT_OWNERSHIP_CONSOLIDATION_V04_CHECKER */

$root = dirname(__DIR__, 2);

$paths = [
    'shell' => $root . '/base/templates/default/assets/css/forprint-shell.css',
    'template' => $root . '/base/templates/default/technicalrequirements.php',
    'tech_css' => $root . '/base/templates/default/assets/css/forprint-technical-requirements.css',
    'app_js' => $root . '/base/templates/default/assets/js/app.js',
    'admin_css' => $root . '/base/core/admin/views/css/forprint-admin-technical-requirements.css',
    'admin_js' => $root . '/base/core/admin/views/js/forprint-admin-technical-requirements.js',
    'admin_footer' => $root . '/base/core/admin/views/include/footer.php',
];

$failures = [];
$src = [];

foreach ($paths as $key => $path) {
    if (!is_file($path)) {
        $failures[] = "{$key}: missing";
        continue;
    }

    $src[$key] = (string)file_get_contents($path);
}

if (!$failures) {
    $checks = [
        'single canonical header marker' =>
            substr_count(
                $src['shell'],
                'FP_HEADER_CANONICAL_GEOMETRY_V1'
            ) === 1
            && !str_contains(
                $src['shell'],
                'FP_TECHREQ_HEADER_REBALANCE_'
            ),
        'slogan selector matches semantic class directly' =>
            str_contains(
                $src['shell'],
                '.fp-site-header .fp-site-header__slogan'
            ),
        'technical requirements uses catalog tree contract' =>
            str_contains(
                $src['template'],
                'FP_TECHREQ_TEMPLATE_V3_CATALOG_TREE'
            )
            && str_contains(
                $src['template'],
                'catalog-filter-item__toggle'
            )
            && str_contains(
                $src['template'],
                'catalog-filter-item__count'
            )
            && str_contains(
                $src['template'],
                'catalog-filter-item_open'
            ),
        'technical requirements does not own parallel nav typography' =>
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
        'admin adapter uses exact inherited wrapper' =>
            str_contains(
                $src['admin_js'],
                "field.closest('.vg-element')"
            )
            && !str_contains(
                $src['admin_js'],
                'closestCard'
            )
            && !str_contains(
                $src['admin_js'],
                'markByText'
            ),
        'admin adapter is layout only' =>
            str_contains(
                $src['admin_css'],
                'FP_ADMIN_TECHREQ_LAYOUT_V2'
            )
            && !str_contains(
                $src['admin_css'],
                'border-radius: 0.55rem'
            )
            && !str_contains(
                $src['admin_css'],
                '--fp-techreq-admin-'
            ),
        'admin js is registered' =>
            str_contains(
                $src['admin_footer'],
                'forprint-admin-technical-requirements.js'
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
        "FP_COMPONENT_OWNERSHIP_CONSOLIDATION_V04=FAIL\n"
    );

    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(2);
}

echo "FP_COMPONENT_OWNERSHIP_CONSOLIDATION_V04=PASS\n";
