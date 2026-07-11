# ForPrint Website — Apply Optimized Goods Image v0.6.7

## Status

`apply_optimized_goods_image_v0_6_7_ready_for_single_product_test`

## Purpose

Adds a safe one-product apply tool for optimized product images.

## Tool

`scripts/maintenance/apply_optimized_goods_image.php`

## Safety

- Legacy admin upload is unchanged.
- Default mode is dry-run.
- DB update requires explicit `--apply`.
- Old source image is not deleted.
- Writes are limited to the expected local DB.

## Example

```bash
php scripts/maintenance/apply_optimized_goods_image.php --goods-id=138
php scripts/maintenance/apply_optimized_goods_image.php --goods-id=138 --apply
```
