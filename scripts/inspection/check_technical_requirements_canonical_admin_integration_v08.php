<?php
/* FP_TECHREQ_CANONICAL_ADMIN_INTEGRATION_V08_CHECKER */

$root = dirname(__DIR__, 2);

$paths = [
    'add' =>
        $root . '/base/core/admin/views/add.php',
    'header' =>
        $root . '/base/core/admin/views/include/header.php',
    'footer' =>
        $root . '/base/core/admin/views/include/footer.php',
    'surface' =>
        $root . '/base/core/admin/views/include/technical_requirements_form.php',
    'css' =>
        $root . '/base/core/admin/views/css/forprint-admin-technical-requirements.css',
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
        'no early short-circuit branch' =>
            !str_contains(
                $src['add'],
                'FP_ADMIN_TECHREQ_SERVER_SURFACE_V1'
            )
            && !preg_match(
                '/technical_requirements_form\.php\s*[\x27\x22];\s*return\s*;/s',
                $src['add']
            ),

        'canonical surface identity' =>
            str_contains(
                $src['add'],
                " fp-admin-technical-requirements-surface"
            )
            && str_contains(
                $src['add'],
                "technical-requirements"
            ),

        'default renderer excludes knoweleges' =>
            preg_match(
                '/\$forprintRenderDefaultBlocks\s*=\s*\(\s*'
                . '\$this->table\s*!==\s*[\x27\x22]footer_settings[\x27\x22]\s*'
                . '&&\s*\$this->table\s*!==\s*[\x27\x22]knoweleges[\x27\x22]/s',
                $src['add']
            ) === 1,

        'canonical content branch' =>
            str_contains(
                $src['add'],
                'FP_ADMIN_TECHREQ_CANONICAL_CONTENT_BRANCH_V1'
            )
            && substr_count(
                $src['add'],
                "include __DIR__ . '/include/technical_requirements_form.php';"
            ) === 1,

        'content-only include' =>
            str_contains(
                $src['surface'],
                'FP_ADMIN_TECHREQ_CANONICAL_CONTENT_V2'
            )
            && !preg_match(
                '/<form\b/i',
                $src['surface']
            )
            && !str_contains(
                $src['surface'],
                'fp-admin-action-bar'
            )
            && str_contains(
                $src['surface'],
                'fp-admin-techreq-settings-grid'
            )
            && str_contains(
                $src['surface'],
                'fp-admin-techreq-media-grid'
            )
            && str_contains(
                $src['surface'],
                'fp-admin-techreq-content-grid'
            ),

        'route-scoped CSS registration' =>
            preg_match(
                '/if\s*\(\s*\(\$this->table\s*\?\?\s*[\x27\x22][\x27\x22]\s*\)'
                . '\s*===\s*[\x27\x22]knoweleges[\x27\x22]\s*\).*?'
                . 'forprint-admin-technical-requirements\.css\?v=20260913-2315/s',
                $src['header']
            ) === 1,

        'old layout JS not registered' =>
            !str_contains(
                $src['header'],
                'forprint-admin-technical-requirements.js'
            )
            && !str_contains(
                $src['footer'],
                'forprint-admin-technical-requirements.js'
            ),

        'surface CSS remains bounded' =>
            str_contains(
                $src['css'],
                'FP_ADMIN_TECHREQ_SERVER_SURFACE_CSS_V1'
            )
            && str_contains(
                $src['css'],
                'form#main-form.fp-admin-technical-requirements-surface'
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
        "FP_TECHREQ_CANONICAL_ADMIN_INTEGRATION_V08=FAIL\n"
    );

    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(2);
}

echo "FP_TECHREQ_CANONICAL_ADMIN_INTEGRATION_V08=PASS\n";
