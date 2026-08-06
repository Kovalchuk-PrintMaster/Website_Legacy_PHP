# Критичні файли та відповідальність v0.1

**ID:** `FP-WEB-REF-002`

## Runtime

| Path | Responsibility |
|---|---|
| `base/index.php` | Bootstrap |
| `base/config.php` | Runtime/DB config |
| `base/core/base/controllers/` | Routing/base mechanics |
| `base/core/base/models/` | Base DB behavior |
| `base/core/base/settings/Settings.php` | Managed fields/entities |
| `base/templates/default/include/header.php` | Head/navigation/assets |
| `base/templates/default/include/footer.php` | Footer/global scripts |

## Product frontend

| Path | Responsibility |
|---|---|
| `templates/default/product.php` | Product page/gallery/tabs/related |
| `include/goodsItem.php` | Legacy card |
| `assets/css/style.css` | Global legacy CSS |
| `assets/js/script.js` | Global legacy JS |
| `forprint-product-detail.css/js` | Isolated detail component |

## Communication

| Path | Responsibility |
|---|---|
| `productCommunicationButtons.php` | Buttons/modal/form markup |
| `forprint-product-communication.css` | Form/modal styles |
| `forprint-product-communication.js` | UX/fetch |
| `communication-request.php` | Validation/storage/delivery |
| `ValidationHelper.php` | Legacy Login/Orders helper |
| `composer.json/lock` | Phone dependency після patch |

## Media

- `GoodsImageUploadOptimizer.php` — goods/gallery optimization;
- `BaseAdmin.php` — admin integration;
- `scripts/maintenance/*image*` — controlled tools;
- `userfiles/` — actual media.

## Operations

- `Makefile` — repeatable commands;
- `run_website_local_http_smoke.py` — route smoke;
- `check_website_staging_runtime.py` — prerequisites;
- `tmp/work/tmp.php`, `tmp/work/tmp.py` — temporary execution only.

<!-- FP_FRONTEND_CRITICAL_REFERENCE_V0_1 -->
## Frontend critical reference — 2026-07-18

Legacy runtime:

- `base/core/user/controllers/BaseUser.php` — shared user presentation helpers and frontend profile resolver;
- `base/core/user/controllers/IndexController.php` — current home data preparation;
- `base/core/user/controllers/SearchController.php` — full-result search ownership;
- `base/templates/default/index.php` — current legacy home composition;
- `base/templates/default/include/header.php` — shared header and internal search instance;
- `base/templates/default/surfaces/home/` — seven extracted legacy home components;
- `base/templates/default/assets/css/style.css` — inherited global visual owner;
- `base/templates/default/assets/css/surfaces/home.css` — controlled legacy home scope;
- `base/templates/default/assets/js/surfaces/home.js` — controlled legacy home JavaScript scope.

Canonical strategy references:

- `docs/reference/legacy_frontend_current_state_v0_1.md`;
- `docs/reference/inspection_and_maintenance_tools_v0_1.md`;
- `docs/decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md`;
- `docs/plans/legacy_publication_and_modern_frontend_plan_v0_1.md`.

<!-- FP-FRONTEND-DOCS-V02-START -->
## Frontend ownership addendum — 2026-07-20

| Path | Responsibility |
|---|---|
| `base/templates/default/assets/css/forprint-layout.css` | global usable width, side space, content ceiling, fixed rail reservation, shared containers |
| `base/templates/default/assets/css/forprint-shell.css` | shared public header and responsive shell |
| `base/templates/default/assets/css/forprint-home.css` | homepage-only presentation and hero slider geometry |
| `base/templates/default/assets/css/forprint-product-cards.css` | reusable product cards and grids |
| `base/templates/default/assets/css/forprint-product-detail.css` | single-product presentation |
| `base/templates/default/assets/css/forprint-product-communication.css` | product enquiry UI |
| `base/templates/default/assets/css/forprint-search-suggestions.css` | search suggestion presentation |
| `base/templates/default/surfaces/home/heroSlider.php` | hero slider markup and data rendering |
| `base/templates/default/assets/js/script.js` | inherited/global Swiper initialization including homepage hero runtime |
| `/etc/systemd/system/forprint-website-preview.service` | external local preview process; not repository-owned deployment configuration |
<!-- FP-FRONTEND-DOCS-V02-END -->
