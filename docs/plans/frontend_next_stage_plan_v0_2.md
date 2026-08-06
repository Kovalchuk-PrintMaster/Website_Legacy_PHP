# ForPrint frontend next-stage plan v0.2

**Status:** active
**Date:** 2026-07-20

## Goal

Move from the current accepted working layout into a controlled next stage without reintroducing CSS ownership ambiguity.

## Stage 1 — documentation and checkpoint hygiene

- apply the frontend documentation package;
- run the documentation inspection;
- review generated diffs;
- keep unrelated worktree changes untouched;
- do not stage or commit until the full working set is accepted.

## Stage 2 — homepage slider media pipeline

Target path:

```text
base/userfiles/frontend/home/slider/
```

Required behavior:

- accepted raster input;
- orientation normalization;
- logical filename;
- web-safe conversion;
- compression;
- controlled dimensions/profile;
- database update only after successful write;
- safe replacement and cleanup;
- idempotent inspection.

## Stage 3 — responsive acceptance

Review:

- 1920 px;
- 1600 px;
- 1366 px;
- 1024 px;
- 768 px;
- approximately 390 px.

Check:

- header contacts;
- final navigation item;
- hero text overflow;
- hero controls and pagination;
- next-block separation;
- product-group tabs;
- card rhythm;
- fixed rail;
- search strip.

## Stage 4 — PHP 8.2 warning baseline

Classify and address repeated undefined-key warnings without broad behavioral rewrites.

Priority files:

```text
base/core/base/models/BaseModelMethods.php
base/core/base/models/BaseModel.php
base/core/base/controllers/BaseMethods.php
```

## Stage 5 — remaining product/catalog work

- sorting/filtering rules;
- price-mode-aware filtering;
- price-request fallback review;
- product-card responsive review;
- product-detail and related-goods review.

## Stage 6 — controlled checkpoint

- exact file list;
- syntax checks;
- focused inspections;
- browser review;
- `git diff --check`;
- exact staging;
- commit and push only after acceptance.

## Deferred

- public deployment;
- complete removal of legacy `style.css`;
- cart/checkout;
- mobile application work;
- large modern redesign switch.

<!-- FP_FRONTEND_PLAN_CHECKPOINT_2026_08_06_START -->
## Checkpoint update — 2026-08-06

Completed for the current local stage:

- design tokens, default theme and shared foundation layers;
- page-structure rhythm and fluid content width;
- shared shell, homepage, catalog, contacts, news, services and product detail;
- exact documentation and Git checkpoint boundary.

Next execution order:

1. separate logo pass duration from idle interval;
2. complete responsive acceptance;
3. consolidate remaining temporary bounded overrides into canonical owners;
4. continue catalog/product behavior;
5. handle SEO/Ads as an independently reviewed checkpoint.
<!-- FP_FRONTEND_PLAN_CHECKPOINT_2026_08_06_END -->
