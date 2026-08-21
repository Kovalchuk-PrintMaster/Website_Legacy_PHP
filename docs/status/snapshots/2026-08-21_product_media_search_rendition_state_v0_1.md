# Product media and search-rendition production state — 2026-08-21

**Snapshot ID:** `FP-WEB-STATUS-2026-08-21-PRODUCT-MEDIA-001`
**Date:** `2026-08-21`
**Status:** `historical snapshot`
**Production deployment:** completed for canonical search-rendition code and runtime-root fix
**Historical media backfill:** completed

## 1. Accepted Git state

Search-rendition lifecycle feature checkpoint:

```text
4aefe5abedb697f835f14cdca987ce059d0d4390
```

Runtime-root portability checkpoint:

```text
a5a88b5a6842576cae5629de31c19352735235fc
```

The portability checkpoint was pushed to `origin/main` with ahead/behind `0/0` before the historical backfill.

## 2. Production application state

Product-media application files active in production include:

```text
core/admin/controllers/BaseAdmin.php
core/admin/controllers/DeleteController.php
libraries/GoodsImageUploadOptimizer.php
templates/default/include/structuredData.php
```

Accepted production optimizer SHA-256 after runtime-root fix:

```text
9dfb75e42a5788db2361a745e7ba0729be2129f0132890554a5cadf98cf7bee5
```

Resolved runtime media root:

```text
/var/www/825163-nikolay.k/data/www/forprint.net.ua/userfiles
```

Production `userfiles/goods` was confirmed writable.

## 3. Historical inventory authority

Canonical historical inventory fingerprint:

```text
5340deea3b536272441e737ad6e896fbee01eb0e173731b8da0f8abfccb54d1e
```

Inventory result before mutation:

```text
goods rows:                     164
READY rows:                     164
unique canonical main images:  164
duplicate main references:      0
target collisions:              0
pre-existing rendition targets: 0
planned output files:           492
projected output bytes:         66,307,706
```

## 4. Fresh backfill backup

Before generation, all 164 canonical main images were copied to a fresh off-host backup and verified against the inventory SHA values.

Backup:

```text
.runtime/backups/hosting/20260821_161207/canonical_product_search_rendition_backfill/
```

Verified source backup:

```text
files:          164
unpacked bytes: 90,186,992
tar bytes:      90,316,800
```

## 5. Generation result

The historical additive backfill completed successfully:

```text
canonical source images:   164
created search renditions: 492
created rendition bytes:   66,307,706
search directories:        15
search temporary files:    0
```

Profiles:

```text
1x1  = 700 × 700
4x3  = 700 × 525
16x9 = 704 × 396
JPEG quality = 94
```

Generation and final validation both reported all `164` goods rows and all `492` expected files.

## 6. Public HTTP acceptance

Generated images:

```text
492 checked
492 HTTP 200
492 image/jpeg
```

Public site crawl:

```text
sitemap URLs:              191
all sitemap URLs HTTP 200: YES
Product schema pages:      118
```

## 7. Structured-data acceptance

After backfill:

```text
Product pages with 3+ search refs:      118
Product pages with exact 3 search refs: 118
total search-rendition refs:            354
```

The Product schema eligibility count remained `118`; the media migration did not broaden or shrink Product eligibility.

## 8. Mutation boundaries

Historical backfill changed only derivative media files.

```text
production PHP source mutation during backfill: NONE
database mutation:                         NONE
Git mutation during backfill:              NONE
Google Ads mutation:                       NONE
canonical source-image rewrite:            NONE
```

## 9. Current meaning

As of this snapshot:

- existing historical products have complete search-rendition families;
- future main-image uploads generate their family automatically through the canonical optimizer;
- Product JSON-LD can expose verified aspect variants;
- deletion/replacement lifecycle cleanup is wired for the main-image family;
- runtime media-root resolution works in both local repository and production layouts;
- the one-time historical migration is complete and must not be rerun blindly.
