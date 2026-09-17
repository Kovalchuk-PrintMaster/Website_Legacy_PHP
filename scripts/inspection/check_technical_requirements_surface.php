<?php
/* FP_TECHREQ_CHECKER_V2 */

$root = dirname(__DIR__, 2);

$paths = [
    'settings' => $root . '/base/core/base/settings/Settings.php',
    'internal' => $root . '/base/core/base/settings/internal_settings.php',
    'header' => $root . '/base/templates/default/include/header.php',
    'shell' => $root . '/base/templates/default/assets/css/forprint-shell.css',
    'controller' => $root . '/base/core/user/controllers/TechnicalrequirementsController.php',
    'template' => $root . '/base/templates/default/technicalrequirements.php',
    'css' => $root . '/base/templates/default/assets/css/forprint-technical-requirements.css',
    'migration' => $root . '/database_dumps/migrations/2026_09_13_technical_requirements_media_v0_2.sql',
];

$src = [];
$failures = [];

foreach ($paths as $name => $path) {
    if (!is_file($path)) {
        $failures[] = "{$name}: missing";
        continue;
    }

    $src[$name] = (string)file_get_contents($path);
}

if ($failures === []) {
    $checks = [
        'admin entity label' =>
            str_contains($src['settings'], "'knoweleges'")
            && str_contains($src['settings'], 'Технічні вимоги'),
        'canonical image template mapping' =>
            preg_match(
                "/'img'\s*=>\s*\[[^\]]*'img'/s",
                $src['settings']
            ) === 1,
        'canonical gallery template mapping' =>
            preg_match(
                "/'gallery_img'\s*=>\s*\[[^\]]*'gallery_img'/s",
                $src['settings']
            ) === 1,
        'canonical image labels' =>
            str_contains(
                $src['settings'],
                "'img' => ['Основне зображення']"
            )
            && str_contains(
                $src['settings'],
                "'gallery_img' => ['Галерея зображень']"
            ),
        'asset cache v02' =>
            str_contains(
                $src['internal'],
                'forprint-technical-requirements.css?v=20260913-2235'
            ),
        'header v2 placements' =>
            substr_count(
                $src['header'],
                'FP_TECHREQ_NAV_V2'
            ) === 2
            && !str_contains(
                $src['header'],
                'FP_TECHREQ_NAV_V1'
            ),
        'slogan semantic class' =>
            str_contains(
                $src['header'],
                'class="fp-site-header__slogan"'
            ),
        'single header refinement owner' =>
            str_contains(
                $src['shell'],
                'FP_TECHREQ_HEADER_REBALANCE_V3'
            )
            && !str_contains(
                $src['shell'],
                'FP_TECHREQ_HEADER_REBALANCE_V1'
            ),
        'hierarchical controller' =>
            str_contains(
                $src['controller'],
                'FP_TECHREQ_CONTROLLER_V2'
            )
            && str_contains(
                $src['controller'],
                '$childrenByParent'
            )
            && str_contains(
                $src['controller'],
                '$currentTrail'
            ),
        'hierarchical template' =>
            str_contains(
                $src['template'],
                'FP_TECHREQ_TEMPLATE_V2'
            )
            && str_contains(
                $src['template'],
                '$fpTechRenderNav'
            ),
        'four-card owner' =>
            str_contains(
                $src['css'],
                'FP_TECHREQ_CSS_SURFACE_V2'
            )
            && str_contains(
                $src['css'],
                'grid-template-columns: repeat(4, minmax(0, 1fr));'
            ),
        'media migration' =>
            str_contains(
                $src['migration'],
                'ADD COLUMN `img`'
            )
            && str_contains(
                $src['migration'],
                'ADD COLUMN `gallery_img`'
            ),
        'legacy style untouched by feature' =>
            !str_contains(
                (string)@file_get_contents(
                    $root
                    . '/base/templates/default/assets/css/style.css'
                ),
                'FP_TECHREQ_'
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
        "TECHNICAL_REQUIREMENTS_SURFACE_CHECK=FAIL\n"
    );

    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(2);
}

echo "TECHNICAL_REQUIREMENTS_SURFACE_CHECK=PASS\n";
