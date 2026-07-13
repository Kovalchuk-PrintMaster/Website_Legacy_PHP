# ForPrint Website — Editor Media Upload and Product Tab Spacing v0.6.16

## Status

`editor_media_upload_and_spacing_v0_6_16_ready_for_manual_test`

## Purpose

Improve product tab content editing and rendering.

## Changes

- Added admin TinyMCE media upload endpoint:
  - `base/core/admin/editor_upload.php`
- Added session upload token for editor uploads.
- TinyMCE image/media picker now opens a local file picker and uploads files into:
  - `base/userfiles/editor/YYYY/MM/`
- Supported upload types:
  - JPG, PNG, WebP, GIF
  - MP4, WebM, OGG
- Reduced frontend product tab vertical padding and rich-text spacing.

## Upload endpoint path fix

- Fixed editor upload endpoint paths from `base/core/admin/` to project webroot resources.
- Exported upload URL/token onto `window` so TinyMCE can access them.
- Centered product tab navigation and made product tab content width adaptive.

## Upload path correction and equal-width tabs

- Corrected editor upload endpoint paths back to `base/config.php` and `base/userfiles/`.
- Product tab headers now split the full row equally between enabled tabs.
- Product tab content width was widened slightly from the first compact layout.

## Product-aware editor storage

- Goods editor uploads are now stored under `base/userfiles/editor/goods/<catalog-alias>/<product-slug>/`.
- Non-goods editor uploads keep the generic date-based fallback under `base/userfiles/editor/general/YYYY/MM/`.
- Product tab headers now use CSS grid equal columns and product tab text width is adaptive up to 1500px.

## Visible graphite full-width polish

- Added stronger full-width product tab CSS to remove the remaining right-side visual tail.
- Product tab content width now expands more on large monitors.
- Started graphite color experiment for product tabs and right fixed menu/action area.

## Graphite tabs final visual correction

- Added final high-specificity CSS layer for product tabs.
- Inactive product tab labels are now light gray on graphite.
- Product tab content width uses a clamp-based adaptive middle ground.
- Right fixed menu/feedback strip receives graphite color overrides.

## Product page layout and graphite sidebar correction

- Added a stronger layout correction for the product hero block so image, price, and cart areas stay grouped on wide screens.
- Switched tab header equal columns back to flex to avoid the remaining visual right-side tail.
- Darkened inactive tab labels slightly for better readability on graphite.
- Added more aggressive graphite selectors for the right floating feedback/menu strip.

## Rollback of broken v0.6.16h hero override

- Removed the aggressive v0.6.16h CSS block because it broke the product hero/gallery area.
- Added a safer tab-only graphite polish layer that does not touch the product hero layout.

## Exact sidebar and tab tail polish

- Added exact graphite styling for the real right sidebar selector: `header__sidebar`.
- Added tab header container/background correction to remove the remaining right-side tail.
- Darkened inactive product tab labels while leaving the product hero/gallery untouched.
