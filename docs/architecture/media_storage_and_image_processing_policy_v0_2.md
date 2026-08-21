# Media Storage and Image Processing Policy v0.2

**Document ID:** `FP-WEB-MEDIA-002`
**Version:** `v0.2`
**Date:** `2026-08-21`
**Status:** `active`
**Supersedes:** `media_storage_and_image_processing_policy_v0_1.md`
**Scope:** uploaded, canonical, derived, and search-visible website images

## 1. Purpose

This policy defines the current product-media ownership model after the canonical product search-rendition pipeline, lifecycle cleanup, production runtime-root portability fix, and historical backfill were completed.

The central rule is:

> Product media has one canonical application owner, one canonical storage root per runtime, explicit database-owned source paths, and deterministic derived files that do not compete with database authority.

The previous v0.1 document remains historical evidence. This v0.2 document is the current policy.

## 2. Canonical product-media owner

Canonical implementation:

```text
base/libraries/GoodsImageUploadOptimizer.php
```

`GoodsImageUploadOptimizer` owns product image normalization, naming, final product-media paths, search-rendition generation, verification, and deterministic derivative cleanup.

Primary callers and consumers:

```text
base/core/admin/controllers/BaseAdmin.php
base/core/admin/controllers/DeleteController.php
base/templates/default/include/structuredData.php
scripts/inspection/check_website_product_image_runtime.php
```

Other classes may call this owner, but they must not duplicate its path or rendition rules.

## 3. Runtime storage-root contract

The optimizer resolves product media from the runtime webroot rather than from a repository-only layout assumption:

```php
$this->userfilesRoot = dirname(__DIR__) . '/userfiles';
```

This intentionally supports both layouts:

```text
Local repository runtime:
base/libraries/GoodsImageUploadOptimizer.php
base/userfiles/

Production runtime:
libraries/GoodsImageUploadOptimizer.php
userfiles/
```

Local project webroot remains `base/`. Production deployment strips the local `base/` prefix, so `libraries/` and `userfiles/` are siblings beneath the production webroot.

The production runtime acceptance on 2026-08-21 confirmed:

```text
/var/www/825163-nikolay.k/data/www/forprint.net.ua/userfiles
```

as the resolved writable `userfiles` root.

## 4. Database authority

Canonical product source fields remain database-owned:

```text
goods.img
goods.gallery_img
```

Paths are stored relative to `userfiles/`, for example:

```text
goods/operatuvna-poligrafiya/product-name_01.jpg
```

Search renditions do **not** introduce database columns. Their paths are deterministically derived from `goods.img` through `GoodsImageUploadOptimizer`.

This avoids creating a second product-image catalog in the database.

## 5. Canonical source images

The main image and gallery are canonical product media. They remain the authoritative files referenced by database fields.

Current product output remains JPEG. The optimizer owns normalization, fit behavior, naming, and safe final storage inside:

```text
userfiles/goods/<catalog-alias>/
```

Historical canonical files are not rewritten merely because search renditions exist.

## 6. Canonical product search renditions

For each canonical main `goods.img`, the optimizer defines one deterministic search family:

| Profile | Dimensions | Output | Behavior |
|---|---:|---|---|
| `1x1` | `700 × 700` | JPEG | centered fit/pad, white canvas, no upscale |
| `4x3` | `700 × 525` | JPEG | centered fit/pad, white canvas, no upscale |
| `16x9` | `704 × 396` | JPEG | centered fit/pad, white canvas, no upscale |

JPEG quality:

```text
94
```

Target namespace:

```text
userfiles/goods/<catalog-alias>/search/
```

Deterministic naming:

```text
<canonical-main-stem>_1x1.jpg
<canonical-main-stem>_4x3.jpg
<canonical-main-stem>_16x9.jpg
```

Search renditions are derivatives. They never replace the canonical main image.

## 7. Complete-family rule

A search family is considered usable only when all three expected files exist and match the exact expected dimensions.

`existingSearchRenditions()` therefore acts as a complete-family gate. A partial family is not exposed as a valid search family.

`structuredData.php` may add search renditions to `Product.image` only after the optimizer verifies the complete family.

This prevents partially generated derivative state from leaking into structured data.

## 8. Generation flow for future uploads

Future product main-image uploads use the normal admin flow:

```text
admin upload
→ BaseAdmin.php
→ GoodsImageUploadOptimizer::optimizeMainImage()
→ canonical main image
→ ensureSearchRenditions()
→ complete verified 1x1 / 4x3 / 16x9 family
→ record save continues only after required image processing succeeds
```

Historical backfill is not required for newly uploaded product main images after this architecture is active.

## 9. Failure and cleanup lifecycle

Cleanup is part of the same media ownership model.

### Failed current upload

If the current request cannot complete its main image/rendition family, current-request files are removed and the save is aborted rather than persisting a false-success image state.

### Main-image replacement

Before an old main image is superseded, its deterministic search family is removed through the optimizer owner.

### Single main-image deletion

`DeleteController.php` removes the deterministic search family when the `goods.img` field itself is deleted.

### Full product-record deletion

`DeleteController.php` removes the main-image search family during product-record file lifecycle cleanup before the database record deletion continues.

### Gallery scope

Search renditions are currently generated for the canonical main image, not for gallery images. Gallery cleanup semantics therefore remain unchanged.

## 10. Structured-data integration

Primary consumer:

```text
base/templates/default/include/structuredData.php
```

For eligible Product schema pages:

1. canonical main image remains first-class media;
2. a verified complete search family is added after the canonical main image;
3. gallery images remain independent product imagery;
4. a partial or invalid search family is suppressed;
5. request-price pages remain governed by the separate price/schema eligibility contract.

Search renditions increase aspect-ratio availability for search systems. They do not guarantee a particular Google SERP layout and are not presented as three distinct product photographs when they are aspect variants of the same canonical image.

## 11. Historical production backfill — 2026-08-21

The controlled historical migration used the existing canonical `goods.img` files as read-only sources.

Accepted production result:

```text
canonical source images:        164
created search renditions:      492
created rendition bytes:        66,307,706
search directories:             15
temporary files after run:      0
profiles:                       1x1 / 4x3 / 16x9
```

Validation completed successfully:

```text
492 / 492 generated files: exact hash validation PASS
492 / 492 generated files: exact dimension validation PASS
492 / 492 public image URLs: HTTP 200 JPEG
191 sitemap URLs: HTTP 200
118 Product schema pages: unchanged
118 Product pages: exact three verified search-rendition refs
354 Product search-image refs total
```

No product database rows were mutated by the backfill.

Inventory fingerprint:

```text
5340deea3b536272441e737ad6e896fbee01eb0e173731b8da0f8abfccb54d1e
```

Fresh canonical-source off-host backup:

```text
.runtime/backups/hosting/20260821_161207/canonical_product_search_rendition_backfill/
```

## 12. Production deployment boundary

Production product-media application files involved in the architecture are:

```text
core/admin/controllers/BaseAdmin.php
core/admin/controllers/DeleteController.php
libraries/GoodsImageUploadOptimizer.php
templates/default/include/structuredData.php
```

The accepted runtime-root production optimizer SHA-256 is:

```text
9dfb75e42a5788db2361a745e7ba0729be2129f0132890554a5cadf98cf7bee5
```

Accepted Git checkpoint after the runtime-root portability fix:

```text
a5a88b5a6842576cae5629de31c19352735235fc
```

## 13. Operational inspection owner

Persistent runtime contract inspection:

```text
scripts/inspection/check_website_product_image_runtime.php
```

It verifies the canonical upload/runtime contract, including the runtime-webroot media-root rule.

Historical migration scripts and dated reports remain evidence. They are not the runtime owner.

## 14. Rules for future media work

1. Do not duplicate product-media path construction outside `GoodsImageUploadOptimizer`.
2. Do not add database columns for deterministic search derivatives unless a future architecture decision explicitly changes authority.
3. Do not generate partial public search families as accepted state.
4. Do not crop blindly when the accepted profile is fit/pad.
5. Do not introduce ad-hoc WebP/AVIF output into this product pipeline without a separate format decision.
6. Do not delete canonical source media as part of derivative cleanup.
7. Keep historical mass generation as a guarded maintenance task with inventory, backup, exact output accounting, and rollback ownership.
8. Verify PHP syntax and product-image runtime contracts after owner changes.
9. Update this policy when ownership, storage semantics, output profiles, or lifecycle rules materially change.
