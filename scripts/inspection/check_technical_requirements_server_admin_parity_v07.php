<?php
/* FP_TECHREQ_SERVER_ADMIN_PARITY_V07_CHECKER */

$root = dirname(__DIR__, 2);

$files = [
    'add' =>
        $root . '/base/core/admin/views/add.php',
    'surface' =>
        $root . '/base/core/admin/views/include/technical_requirements_form.php',
    'css' =>
        $root . '/base/core/admin/views/css/forprint-admin-technical-requirements.css',
    'header' =>
        $root . '/base/core/admin/views/include/header.php',
    'footer' =>
        $root . '/base/core/admin/views/include/footer.php',
];

$failures = [];
$src = [];

foreach ($files as $key => $path) {
    if (!is_file($path)) {
        $failures[] = "{$key}: missing file";
        continue;
    }

    $src[$key] = (string)file_get_contents($path);
}

if (!$failures) {
    $checks = [
        'server branch in add.php' =>
            substr_count(
                $src['add'],
                'FP_ADMIN_TECHREQ_SERVER_SURFACE_V1'
            ) === 1
            && str_contains(
                $src['add'],
                "technical_requirements_form.php"
            ),
        'dedicated surface form' =>
            str_contains(
                $src['surface'],
                'fp-admin-technical-requirements-surface'
            )
            && str_contains(
                $src['surface'],
                'fp-admin-content-card'
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
                'fp-admin-action-bar'
            ),
        'all known fields rendered' =>
            str_contains($src['surface'], "'name'")
            && str_contains($src['surface'], "'alias'")
            && str_contains($src['surface'], "'visible'")
            && str_contains($src['surface'], "'parent_id'")
            && str_contains($src['surface'], "'menu_position'")
            && str_contains($src['surface'], "'img'")
            && str_contains($src['surface'], "'gallery_img'")
            && str_contains($src['surface'], "'short_content'")
            && str_contains($src['surface'], "'content'"),
        'canonical surface css' =>
            substr_count(
                $src['css'],
                'FP_ADMIN_TECHREQ_SERVER_SURFACE_CSS_V1'
            ) === 1
            && !str_contains(
                $src['css'],
                'FP_ADMIN_TECHREQ_LAYOUT_V4'
            ),
        'css cache token' =>
            preg_match(
                '/forprint-admin-technical-requirements\.css\?v=20260913-2230/',
                $src['header']
            ) === 1,
        'old layout js unregistered' =>
            !str_contains(
                $src['footer'],
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
        "FP_TECHREQ_SERVER_ADMIN_PARITY_V07=FAIL\n"
    );

    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(2);
}

echo "FP_TECHREQ_SERVER_ADMIN_PARITY_V07=PASS\n";
