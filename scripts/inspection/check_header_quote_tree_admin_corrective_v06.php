<?php
/* FP_HEADER_QUOTE_TREE_ADMIN_CORRECTIVE_V06_CHECKER */

$root = dirname(__DIR__, 2);

$paths = [
    'shell' => $root . '/base/templates/default/assets/css/forprint-shell.css',
    'tech_template' => $root . '/base/templates/default/technicalrequirements.php',
    'tech_js' => $root . '/base/templates/default/assets/js/forprint-technical-requirements.js',
    'admin_css' => $root . '/base/core/admin/views/css/forprint-admin-technical-requirements.css',
    'admin_js' => $root . '/base/core/admin/views/js/forprint-admin-technical-requirements.js',
    'admin_header' => $root . '/base/core/admin/views/include/header.php',
    'admin_footer' => $root . '/base/core/admin/views/include/footer.php',
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
        'single header v3 owner' =>
            substr_count(
                $src['shell'],
                'FP_HEADER_CANONICAL_GEOMETRY_V3'
            ) === 1
            && !str_contains(
                $src['shell'],
                'FP_HEADER_CANONICAL_GEOMETRY_V2'
            ),
        'quote utility recovery present' =>
            substr_count(
                $src['shell'],
                'FP_HEADER_QUOTE_UTILITY_RECOVERY_V1'
            ) === 1,
        'technical tree has canonical drop scope' =>
            str_contains(
                $src['tech_template'],
                'catalog-aside-block__content catalog-aside-block__drop is-open'
            )
            && str_contains(
                $src['tech_template'],
                'data-fp-techreq-tree'
            ),
        'technical tree controller present' =>
            str_contains(
                $src['tech_js'],
                'FP_TECHREQ_TREE_CONTROLLER_V1'
            )
            && str_contains(
                $src['tech_js'],
                'aria-expanded'
            )
            && str_contains(
                $src['tech_js'],
                'catalog-filter-item_open'
            ),
        'admin v4 MutationObserver' =>
            str_contains(
                $src['admin_js'],
                'FP_ADMIN_TECHREQ_LAYOUT_JS_V4'
            )
            && str_contains(
                $src['admin_js'],
                'MutationObserver'
            )
            && !str_contains(
                $src['admin_js'],
                'closestCard'
            )
            && !str_contains(
                $src['admin_js'],
                'markByText'
            ),
        'admin v4 geometry' =>
            str_contains(
                $src['admin_css'],
                'FP_ADMIN_TECHREQ_LAYOUT_V4'
            )
            && str_contains(
                $src['admin_css'],
                '.fp-admin-techreq-media-grid'
            ),
        'admin css token bumped' =>
            preg_match(
                '/forprint-admin-technical-requirements\.css\?v=20260913-2135/',
                $src['admin_header']
            ) === 1,
        'admin js token bumped' =>
            preg_match(
                '/forprint-admin-technical-requirements\.js\?v=20260913-2135/',
                $src['admin_footer']
            ) === 1,
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
        "FP_HEADER_QUOTE_TREE_ADMIN_CORRECTIVE_V06=FAIL\n"
    );

    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(2);
}

echo "FP_HEADER_QUOTE_TREE_ADMIN_CORRECTIVE_V06=PASS\n";
