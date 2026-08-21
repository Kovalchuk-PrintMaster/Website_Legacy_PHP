# Documentation Package Manifest v0.3

**Package:** `forprint_website_documentation_pack_v0_3`
**Date:** `2026-08-21`
**Status:** `accepted architecture package`
**Scope:** canonical product media pipeline, search renditions, lifecycle, production runtime state

## Purpose

This package updates project documentation after a major product-media architecture change and completed production backfill.

It follows the documentation policy that architecture explains the model, decisions preserve accepted choices, snapshots preserve dated facts, references explain current operation, and reports remain evidence rather than the only explanation.

## New canonical documents

```text
docs/architecture/media_storage_and_image_processing_policy_v0_2.md
docs/reference/product_media_pipeline_v0_1.md
docs/decisions/2026-08-21__canonical_product_media_owner_and_search_renditions.md
docs/status/snapshots/2026-08-21_product_media_search_rendition_state_v0_1.md
docs/documentation/package_manifest_v0_3.md
scripts/inspection/check_website_product_media_architecture_docs.php
```

## Updated mutable indexes

```text
docs/README.md
docs/decisions/architecture_decision_register_v0_1.md
docs/reference/repository_map_v0_1.md
docs/documentation/documentation_versioning_policy_v0_1.md
```

Updates are bounded by package-specific marker blocks and do not replace unrelated index content.

## Architecture captured

- `GoodsImageUploadOptimizer.php` as canonical product-media owner;
- local/production runtime-root portability;
- database authority for `goods.img` / `gallery_img`;
- deterministic `1x1`, `4x3`, `16x9` search families;
- no derivative database columns;
- complete-family verification;
- future upload generation;
- failed-upload, replacement, single-image delete, and full-record delete cleanup;
- `structuredData.php` integration;
- persistent product-image runtime inspection;
- historical production backfill result and backup evidence.

## Validation

```bash
php scripts/inspection/check_website_product_media_architecture_docs.php
php scripts/inspection/check_website_product_image_runtime.php
.venv_website/bin/python3 scripts/inspection/check_website_structured_data_contract.py
git diff --check -- docs scripts/inspection/check_website_product_media_architecture_docs.php
```

The package checkpoint stages only its exact documentation/inspection file set.

## Merge-aware checkpoint boundary

At installation time, two mutable indexes already contained unrelated local work:

```text
docs/decisions/architecture_decision_register_v0_1.md
docs/reference/repository_map_v0_1.md
```

The package installer therefore stages only the new bounded product-media marker blocks for those files. The pre-existing worktree edits remain byte-preserved and unstaged after the product-media documentation commit.
