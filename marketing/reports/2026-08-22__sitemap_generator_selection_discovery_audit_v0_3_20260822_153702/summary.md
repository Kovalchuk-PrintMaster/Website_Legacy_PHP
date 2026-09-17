# Sitemap generator selection + discovery audit v0.3

Generated: `2026-08-22T15:37:14.292763+03:00`
Git checkpoint: `286727adb0b2f111df702943f95788dd83dcb49c`

## Owner

- Canonical source owner: `base/core/admin/controllers/CreatesitemapController.php`.
- Detected model: `LINK_CRAWLER_GENERATOR`.
- Static production sitemap equals live HTTP sitemap: `True`.

## Known missing products

- `269` → `https://forprint.net.ua/product/resepshn-styki-z-brenduvannyam/`
- `270` → `https://forprint.net.ua/product/reklamn-konstrukts-dlya-vtrin/`
- `273` → `https://forprint.net.ua/product/standartn-poshtov-konverti-z-drukom/`

## Discovery evidence

- Missing-product referrer counts: `{'269': 0, '270': 0, '273': 0}`.
- Included-neighbor referrer counts: `{'268': 4, '271': 1, '272': 1, '274': 1}`.

## Decision

- Classification: `ROOT_CAUSE_LINK_DISCOVERY_GAP_IN_CRAWLER_BASED_SITEMAP`.
- Recommended fix direction: `REPLACE_PRODUCT_SELECTION_WITH_CANONICAL_VISIBLE_INDEXABLE_CATALOG_SOURCE_OR_ADD_A_GENERATOR_DATA_SEED`.

No application source, production source, DB, Git, Search Console, or other Google mutation was performed.
