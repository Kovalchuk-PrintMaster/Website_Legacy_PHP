# ForPrint Website — Editor Media Upload and Product Tab Spacing v0.6.16 Report

## Status

`editor_media_upload_and_spacing_v0_6_16_ready_for_manual_test`

## Completed

- Added local admin editor upload endpoint.
- Added TinyMCE image/media file picker integration.
- Added token-based upload protection.
- Reduced product tab frontend spacing.
- Kept uploads local under `base/userfiles/editor/YYYY/MM/`.

## Manual test required

- Open a goods editor field.
- Use Insert/Edit Image and the file picker button.
- Upload an image and confirm it appears in the editor.
- Save product and confirm image renders on frontend.
- Check product tab spacing on frontend.

## Follow-up fix

- Fixed upload endpoint path resolution.
- Fixed TinyMCE upload URL/token exposure through `window`.
- Centered product tab labels and narrowed product tab content adaptively.

## Follow-up correction

- Corrected editor upload endpoint filesystem paths.
- Removed the extra empty visual segment in product tab headers.
- Product tab headers now use equal-width layout for currently enabled tabs.

## Product-aware editor upload storage

- Goods editor uploads now use product-aware folders.
- Product tab header layout now uses equal grid columns without the extra right visual tail.
- Product tab content width is adaptive and wider on large screens.

## Visible graphite polish

- Added stronger full-width product tab header override.
- Widened rich-text product tab content on large screens.
- Applied graphite color experiment to product tabs and likely right-side fixed action/menu elements.

## Graphite tabs final visual correction

- Added final visual correction layer for graphite product tabs.
- Adjusted inactive tab text color for readability.
- Rebalanced product tab content width across different monitor resolutions.
- Added graphite overrides for right-side fixed menu/feedback elements.

## Product page layout and graphite sidebar correction

- Rebalanced wide-screen product hero layout.
- Added final flex-based product tab equal columns correction.
- Darkened inactive tab labels.
- Added broader graphite overrides for the right-side floating service strip.

## v0.6.16h rollback

- Rolled back broken v0.6.16h hero override.
- Kept editor upload and earlier product tab improvements.
- Added safe tab-only graphite color polish.

## Exact sidebar and tab tail polish

- Targeted `header__sidebar` for the graphite right-side panel.
- Removed remaining tab-strip tail via tab-only layout overrides.
- Did not touch product hero/gallery layout.
