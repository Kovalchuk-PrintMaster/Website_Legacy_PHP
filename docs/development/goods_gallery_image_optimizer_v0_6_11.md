# ForPrint Website — Goods Gallery Image Optimizer v0.6.11

## Status

`goods_gallery_image_optimizer_v0_6_11_ready_for_manual_admin_test`

## Purpose

Optimize newly uploaded `goods.gallery_img` files and store them in the same catalog/product directory strategy as the main product image.

## Behavior

- Main image remains: `goods/<catalog-alias>/<product-slug>_NN.jpg`.
- New gallery images become: `goods/<catalog-alias>/<product-slug>-gallery_NN.jpg`.
- Existing gallery records are not reprocessed.
- If optimization fails, the legacy uploaded path is preserved.
- Original uploaded source files are not deleted yet.

## Image profile

- Output format: JPG.
- Size: `700x525`.
- Crop mode: cover crop.
- Quality: `98`.