<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$files = [
    'policy' => $root . '/docs/architecture/media_storage_and_image_processing_policy_v0_2.md',
    'reference' => $root . '/docs/reference/product_media_pipeline_v0_1.md',
    'decision' => $root . '/docs/decisions/2026-08-21__canonical_product_media_owner_and_search_renditions.md',
    'snapshot' => $root . '/docs/status/snapshots/2026-08-21_product_media_search_rendition_state_v0_1.md',
    'manifest' => $root . '/docs/documentation/package_manifest_v0_3.md',
    'docs_index' => $root . '/docs/README.md',
    'decision_register' => $root . '/docs/decisions/architecture_decision_register_v0_1.md',
    'repo_map' => $root . '/docs/reference/repository_map_v0_1.md',
    'doc_policy' => $root . '/docs/documentation/documentation_versioning_policy_v0_1.md',
    'optimizer' => $root . '/base/libraries/GoodsImageUploadOptimizer.php',
    'base_admin' => $root . '/base/core/admin/controllers/BaseAdmin.php',
    'delete_controller' => $root . '/base/core/admin/controllers/DeleteController.php',
    'structured_data' => $root . '/base/templates/default/include/structuredData.php',
];

$content = [];

foreach ($files as $key => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "[FAIL] missing {$key}: {$path}\n");
        exit(1);
    }

    $value = file_get_contents($path);

    if ($value === false) {
        fwrite(STDERR, "[FAIL] unreadable {$key}: {$path}\n");
        exit(1);
    }

    $content[$key] = $value;
}

$checks = [
    'policy supersedes v0.1' =>
        str_contains($content['policy'], 'Supersedes:** `media_storage_and_image_processing_policy_v0_1.md`'),
    'policy names canonical optimizer owner' =>
        str_contains($content['policy'], 'base/libraries/GoodsImageUploadOptimizer.php'),
    'policy records runtime-root portability' =>
        str_contains($content['policy'], "dirname(__DIR__) . '/userfiles'"),
    'policy records exact search profiles' =>
        str_contains($content['policy'], '`700 × 700`')
        && str_contains($content['policy'], '`700 × 525`')
        && str_contains($content['policy'], '`704 × 396`'),
    'policy records no derivative DB columns' =>
        str_contains($content['policy'], 'do **not** introduce database columns'),
    'reference maps all runtime owners' =>
        str_contains($content['reference'], 'BaseAdmin.php')
        && str_contains($content['reference'], 'DeleteController.php')
        && str_contains($content['reference'], 'structuredData.php'),
    'decision accepted' =>
        str_contains($content['decision'], '**Status:** `accepted`'),
    'snapshot records exact production output' =>
        str_contains($content['snapshot'], 'created search renditions: 492')
        && str_contains($content['snapshot'], 'created rendition bytes:   66,307,706')
        && str_contains($content['snapshot'], 'total search-rendition refs:            354'),
    'snapshot records inventory fingerprint' =>
        str_contains($content['snapshot'], '5340deea3b536272441e737ad6e896fbee01eb0e173731b8da0f8abfccb54d1e'),
    'docs index marker present' =>
        str_contains($content['docs_index'], 'FP-PRODUCT-MEDIA-DOCS-V03-START'),
    'decision register marker present' =>
        str_contains($content['decision_register'], 'FP-PRODUCT-MEDIA-ADR-2026-08-21-START'),
    'repository map marker present' =>
        str_contains($content['repo_map'], 'FP-PRODUCT-MEDIA-REPOSITORY-MAP-V01-START'),
    'documentation policy marker present' =>
        str_contains($content['doc_policy'], 'FP-PRODUCT-MEDIA-DOC-POLICY-V03-START'),
    'optimizer owns runtime sibling userfiles root' =>
        str_contains(
            $content['optimizer'],
            "\$this->userfilesRoot = dirname(__DIR__) . '/userfiles';"
        ),
    'optimizer exposes search-rendition API' =>
        str_contains($content['optimizer'], 'ensureSearchRenditions')
        && str_contains($content['optimizer'], 'existingSearchRenditions')
        && str_contains($content['optimizer'], 'removeSearchRenditions'),
    'BaseAdmin uses search cleanup lifecycle' =>
        str_contains($content['base_admin'], 'removeSearchRenditions'),
    'DeleteController uses search cleanup lifecycle' =>
        str_contains($content['delete_controller'], 'removeSearchRenditions'),
    'structuredData consumes verified search renditions' =>
        str_contains($content['structured_data'], 'existingSearchRenditions'),
];

$failed = 0;

foreach ($checks as $label => $passed) {
    if ($passed) {
        echo "[OK] {$label}\n";
        continue;
    }

    fwrite(STDERR, "[FAIL] {$label}\n");
    $failed++;
}

if ($failed > 0) {
    fwrite(STDERR, "Product media architecture documentation checks failed: {$failed}.\n");
    exit(1);
}

echo "All product media architecture documentation checks passed.\n";
