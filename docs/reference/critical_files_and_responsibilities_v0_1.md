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
- `tmp.php`, `tmp.py` — temporary execution only.
