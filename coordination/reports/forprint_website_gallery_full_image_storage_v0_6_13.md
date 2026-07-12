# ForPrint Website — Gallery Full Image Storage v0.6.13 Report

## Status

`gallery_full_image_storage_v0_6_13_ready_for_manual_test`

## Completed

- Changed gallery optimizer from physical cover crop to proportional fit resize.
- Aligned main product image storage with gallery full-image storage.
- Kept gallery output path strategy under `goods/<catalog-alias>/`.
- Added larger semi-transparent blue decorative up/down indicators for thumbnail overflow.

## Manual test required

Upload a new portrait/vertical gallery image and confirm:

- Product page preview still fills its block.
- Clicking the image opens a non-cropped proportional image.
- New gallery image dimensions are proportional, with max side no larger than 1600px.