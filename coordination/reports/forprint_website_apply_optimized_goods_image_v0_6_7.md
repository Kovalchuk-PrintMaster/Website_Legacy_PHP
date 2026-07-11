# ForPrint Website — Apply Optimized Goods Image v0.6.7 Report

## Status

`apply_optimized_goods_image_v0_6_7_ready_for_single_product_test`

## Completed

- Added one-product optimized image apply script.
- Reuses standalone optimizer.
- Places optimized goods images under `goods/<catalog-alias>/<product-name>_NN.jpg`.
- Keeps old image files untouched.
- Requires explicit `--apply` for DB update.
