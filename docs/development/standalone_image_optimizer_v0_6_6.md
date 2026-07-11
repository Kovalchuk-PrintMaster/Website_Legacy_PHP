# ForPrint Website — Standalone Image Optimizer v0.6.6

## Status

`standalone_image_optimizer_v0_6_6_ready_for_manual_test`

## Purpose

This checkpoint adds a standalone image optimization tool without changing admin upload behavior.

## Tool

`scripts/maintenance/optimize_one_uploaded_image.php`

## Safety boundary

- No admin upload integration.
- No database writes.
- Source image is preserved.
- Output is written as `.optimized.jpg` unless an explicit output path is provided.
- Only files under `base/userfiles/` are accepted.

## Example

```bash
php scripts/maintenance/optimize_one_uploaded_image.php \
  --source=base/userfiles/goods/example.png \
  --profile=goods_card
```
