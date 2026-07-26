<?php

declare(strict_types=1);

/**
 * ForPrint frontend architecture documentation inspection.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$required = [
    'css strategy' =>
        'docs/architecture/frontend_css_ownership_and_layout_strategy_v0_3.md',
    'home architecture' =>
        'docs/architecture/home_frontend_structure_and_slider_architecture_v0_1.md',
    'architecture decision' =>
        'docs/decisions/2026-07-20__canonical_frontend_css_ownership_and_homepage_layout.md',
    'preview service record' =>
        'docs/development/local_preview_systemd_service_v0_6_20.md',
    'next-stage plan' =>
        'docs/plans/frontend_next_stage_plan_v0_2.md',
    'working snapshot' =>
        'docs/status/snapshots/2026-07-20_frontend_working_state_v0_1.md',
    'package manifest' =>
        'docs/documentation/package_manifest_v0_2.md',
];

$failed = false;

echo "== ForPrint frontend architecture docs ==\n";

foreach ($required as $label => $relativePath) {
    $path = $root . '/' . $relativePath;

    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] {$label}: missing {$relativePath}\n"
        );
        $failed = true;
        continue;
    }

    $content = (string)file_get_contents($path);

    if (trim($content) === '') {
        fwrite(
            STDERR,
            "[FAIL] {$label}: empty {$relativePath}\n"
        );
        $failed = true;
        continue;
    }

    echo "[OK] {$label}: {$relativePath}\n";
}

$markerFiles = [
    'docs/README.md',
    'docs/decisions/README.md',
    'docs/decisions/architecture_decision_register_v0_1.md',
    'docs/status/README.md',
    'docs/reference/repository_map_v0_1.md',
    'docs/reference/critical_files_and_responsibilities_v0_1.md',
    'docs/documentation/documentation_versioning_policy_v0_1.md',
];

$startMarker = '<!-- FP-FRONTEND-DOCS-V02-START -->';
$endMarker = '<!-- FP-FRONTEND-DOCS-V02-END -->';

echo "\n== Index markers ==\n";

foreach ($markerFiles as $relativePath) {
    $path = $root . '/' . $relativePath;

    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] marker file missing: {$relativePath}\n"
        );
        $failed = true;
        continue;
    }

    $content = (string)file_get_contents($path);

    $startCount = substr_count($content, $startMarker);
    $endCount = substr_count($content, $endMarker);

    if ($startCount !== 1 || $endCount !== 1) {
        fwrite(
            STDERR,
            "[FAIL] marker mismatch: {$relativePath} "
            . "start={$startCount} end={$endCount}\n"
        );
        $failed = true;
        continue;
    }

    echo "[OK] marker block: {$relativePath}\n";
}

echo "\n== Architecture content markers ==\n";

$contentChecks = [
    [
        'docs/architecture/frontend_css_ownership_and_layout_strategy_v0_3.md',
        [
            'forprint-layout.css',
            'forprint-shell.css',
            'forprint-home.css',
            'one active project-owned geometry contract',
        ],
    ],
    [
        'docs/architecture/home_frontend_structure_and_slider_architecture_v0_1.md',
        [
            'base/userfiles/frontend/home/slider/',
            'object-position: center center',
            '6000 ms',
            'sales',
        ],
    ],
    [
        'docs/status/snapshots/2026-07-20_frontend_working_state_v0_1.md',
        [
            'exact',
            'range',
            'request',
            'forprint-website-preview.service',
            'not performed',
        ],
    ],
];

foreach ($contentChecks as [$relativePath, $needles]) {
    $content = (string)file_get_contents($root . '/' . $relativePath);

    foreach ($needles as $needle) {
        if (!str_contains($content, $needle)) {
            fwrite(
                STDERR,
                "[FAIL] {$relativePath}: missing {$needle}\n"
            );
            $failed = true;
        }
    }

    if (!$failed) {
        echo "[OK] content markers: {$relativePath}\n";
    }
}

echo "\n";

if ($failed) {
    fwrite(STDERR, "FRONTEND ARCHITECTURE DOCS FAILED\n");
    exit(1);
}

echo "FRONTEND ARCHITECTURE DOCS PASSED\n";
