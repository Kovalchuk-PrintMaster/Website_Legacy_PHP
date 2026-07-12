# ForPrint Website — Gallery Full Image Storage v0.6.13

## Status

`gallery_full_image_storage_v0_6_13_ready_for_manual_test`

## Purpose

Keep gallery images useful for full-size viewing while still allowing cropped preview display on the product page.

## Behavior

- Main product image now uses proportional full-image storage like gallery: max side `1600px`, JPG quality `94`.
- New gallery images are no longer physically cover-cropped.
- New gallery images are resized proportionally to max side `1600px`, JPG quality `94`.
- Product page previews can still use CSS `object-fit: cover`.
- Clicking a main or gallery image opens a proportional optimized full-view file.

## Important note

Existing gallery files that were already physically cropped cannot be restored from the cropped JPG. They need to be re-uploaded from source images if full view is required.