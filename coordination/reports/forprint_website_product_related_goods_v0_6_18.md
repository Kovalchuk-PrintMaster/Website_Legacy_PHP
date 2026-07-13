# ForPrint Website — v0.6.18 related goods selector completion

## Completed

- Added DB field `goods.related_goods_ids`.
- Added admin related-goods selector template.
- Registered the selector in Settings.
- Loaded related goods in product controller.
- Replaced product-page hardcoded related goods placeholders with real selected products.
- Added CSS for related goods slider images and links.

## Validation

Pending manual UI check:
- edit a product in admin;
- select related goods;
- save product;
- open product page and verify related goods slider.

## Manual check fix

- Fixed product page HTTP 500 caused by passing an array to the legacy `getGoods()` where builder.
- Added compact admin layout adjustments for product text editors and related goods selector.

## Admin/frontend polish

- Bottom save/delete controls are kept after the related goods selector.
- Product editor text areas are compacted on wide admin screens.
- Related goods block background was removed.
- Related goods slider arrows were restyled while preserving the existing slider click functionality.

## Admin tab panel polish

- Service tab fields are grouped into two-column admin panels.
- Storefront product and related-block headings now use the same style.
- Related slider arrows are rendered as isolated overlay controls to avoid old pseudo-element artifacts.

## v0.6.18d admin tab layout
- Rebuilt admin service tabs as one two-column layout.
- Unified gallery/related slider arrow styling.
- Unified right fixed strip and tabs strip color with footer graphite.
