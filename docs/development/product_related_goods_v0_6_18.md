# v0.6.18 Related goods selector

This patch replaces the hardcoded product-page related-offer placeholders with a managed related goods selector.

## Scope

- Adds `goods.related_goods_ids` as lightweight legacy storage for selected related product IDs.
- Adds an admin form widget for selecting related goods by local catalog search.
- Loads related goods in `ProductController`.
- Renders the product-page “Доречі, разом з цим ще беруть і це:” block only when related goods exist.
- Removes hardcoded `additional_offer.png` cards from the live related goods section.

## Notes

This is intentionally lightweight for the legacy PHP site. A normalized relation table can be introduced later if product relationships become complex.

## Fixes after first manual check

- Related goods loading was switched from legacy `getGoods(where id => array)` to a direct safe integer `IN (...)` query because the legacy query builder does not support arrays in `where`.
- Admin product text editors were made more compact.
- Related goods selector was moved to the bottom of the form and constrained to half width on desktop.

## Admin/frontend polish

- Related goods selector is kept near the bottom of the product form, before the bottom save/delete buttons.
- Product text editor fields are compacted into two columns on wide admin screens.
- Related goods storefront block background image is disabled for now.
- Related goods slider title and navigation buttons were visually aligned with the current product page style.

## Admin tab panel polish

- Product service tab controls are grouped into compact two-column admin panels.
- Product page title and related goods title share the same visual style.
- Related goods slider arrows were restyled as overlay controls without changing the existing slider behavior.

## v0.6.18d admin tab layout
- Product service tab fields regrouped into one global two-column admin grid.
- Product gallery arrows reuse the same visual language as related-goods arrows.
- Product tabs strip and fixed right-side strip aligned to footer graphite tone.
