# ForPrint Website — Gallery Upload Base Fix v0.6.9

## Status

`gallery_upload_base_fix_v0_6_9_ready`

## Purpose

Fix legacy gallery upload handling before adding gallery image optimization.

## Changes

- `FileEdit` now skips empty uploads and records upload errors instead of treating failed uploads as normal files.
- Multiple uploaded gallery files are preserved as an ordered array.
- Failed gallery upload attempts do not wipe the existing gallery.
- Upload failures caused by PHP limits are now isolated from normal save flow.

## Runtime note

For local PHP built-in server testing, upload limits must be passed with `php -d`, for example:

```bash
php \
  -d upload_max_filesize=32M \
  -d post_max_size=128M \
  -d max_file_uploads=50 \
  -d memory_limit=512M \
  -S 0.0.0.0:8098 \
  -t base
