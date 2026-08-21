# ForPrint canonical price contract production release v0.1

**ID:** `FP-WEB-PRICE-PROD-2026-08-21-01`
**Version:** `v0.1`
**Date:** `2026-08-21`
**Status:** `accepted`

## Scope

Production release of the canonical product price-state contract from Git checkpoint `1b90064529e4484d3f4824a376c0705e223a75ac`.

Released canonical states:

- `exact` — exact positive price;
- `starting` — truthful positive minimum (`price_from`);
- `range` — two truthful positive bounds (`price_from` + `price_to`);
- `request` — quote / individual calculation without a numeric Product offer.

No full hosting sync was performed.

## Production code release

Exactly five PHP files were selectively published:

- `base/core/admin/controllers/BaseAdmin.php` — `ac9fbeb2683ee747d573a47e4e1a9dd8a65f75c1d2ad124bd139855fc1ba0a4d` → `3238f15ef6bb157c7d91c1d6da8b4ba97e1773f6e05d5449a9b3cc7ffa0cefc9`.
- `base/core/admin/views/include/form_templates/price_mode.php` — `f9ca404d79a039c908c016f6ec87edee759541593ee82b80ac2fce91160b8713` → `a3efc7596d7b8919cbe0ba2dc8661cb157246626845ccfeddacf36340cd34b46`.
- `base/core/base/settings/Settings.php` — `a2a32171af38fbf984399ae6b7daf9c360f113ea8873f98fee3d928f80f9f389` → `2cc731e723d73b5530539804feeca3b4bfa1755d19b8c551869aa1a699616c6b`.
- `base/templates/default/include/productCardHelpers.php` — `d221d603f712923db8e5cf123528f225ab428f828c182f473779b75387889cca` → `6cb6c71b0c23d50ac61ed1ac11e6635c9e6725dd09895620f7c2d838fd5b0a99`.
- `base/templates/default/include/structuredData.php` — `119afad79990762d122fd8d8a849feda2e36e872f0d1b1164ddc689e76439797` → `e8f57fb44a72483d835c82d2386953956882a24b05baf6c18734daab3ef1cc77`.

All five uploaded temporary files passed remote `php -l` and SHA-256 verification before replacement.

## Production database migration

Exactly **42** previously reviewed visible `goods` rows were migrated:

```text
range -> request
```

`price_from` and `price_to` were preserved as dormant historical values.

Visible price-mode counts:

```text
before: exact=1, range=161, request=2, starting=0
after:  exact=1, range=119, request=44, starting=0
```

The remaining 119 two-bound ranges were not modified automatically.

## Production acceptance

- home page returned HTTP 200;
- contacts page returned HTTP 200;
- all 42 migrated product routes returned HTTP 200;
- all 42 migrated product routes rendered request state;
- all 42 migrated product routes emitted no numeric Product offer;
- representative remaining range page preserved matching `AggregateOffer.lowPrice/highPrice`;
- representative exact-price page preserved matching `Offer.price`;
- production sitemap returned HTTP 200 with 191 URLs;
- final production hashes matched the accepted five release files.

## Rollback evidence

Fresh full off-host production backup: `/srv/software_development/forprint-project/forprint_website/.runtime/backups/hosting/20260821_105541`.

The compact production release report also contains exact pre-release copies of the five replaced PHP files.

## Contamination boundary

Batch B remained outside this release. Hash guards were unchanged before and after release:

- `base/templates/default/include/header.php` — `864b2e45fa6696006880c0584ff14669514f934c6bb5a722efb4116cea56eebb`.
- `base/templates/default/include/productCommunicationButtons.php` — `66152ee4515f214a0f270054e7cad906b9dad51ea32fca1456dbb9f21bfe10b6`.

No Google Ads mutation and no Git source mutation were performed by the production release procedure.

## Evidence

- `marketing/reports/2026-08-21__canonical_price_contract_production_release_20260821_105511/summary.md`
- `marketing/reports/2026-08-21__canonical_price_contract_production_release_20260821_105511/result.json`

## Next stage

Continue the separate SERP image-aspect work through the canonical `GoodsImageUploadOptimizer` media pipeline. Do not mix image-profile work with the completed price-contract release.
