# ForPrint Website — Goods Upload Optimizer Pipeline v0.6.8

## Status

`goods_upload_optimizer_pipeline_v0_6_8_ready_for_manual_admin_test`

## Purpose

New goods main images uploaded through admin are post-processed into the structured goods image layout.

## Behavior

- Applies only to `goods.img` uploads.
- Keeps legacy upload flow intact.
- Creates optimized JPG images at `base/userfiles/goods/<catalog-alias>/<product-slug>_NN.jpg`.
- Stores DB path as `goods/<catalog-alias>/<product-slug>_NN.jpg`.
- Uses `700x525`, JPEG quality `98`.
- Does not delete the original uploaded source file yet.

## Manual test

Upload a new main image for one product in admin and verify that `goods.img` points to a structured JPG path.
