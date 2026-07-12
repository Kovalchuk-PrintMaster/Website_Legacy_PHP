# ForPrint Website — Local Dev Server Upload Limits v0.6.10

## Status

`local_dev_server_upload_limits_v0_6_10_ready`

## Purpose

Provide a stable Makefile command for running the local PHP built-in server with upload limits suitable for admin product image uploads.

## Command

```bash
make site-serve

Default runtime settings:

FP_WEB_LOCAL_HTTP_HOST=0.0.0.0
FP_WEB_LOCAL_HTTP_PORT=8098
FP_WEB_UPLOAD_MAX_FILESIZE=32M
FP_WEB_POST_MAX_SIZE=128M
FP_WEB_MAX_FILE_UPLOADS=50
FP_WEB_MEMORY_LIMIT=512M
Notes

These limits apply to the PHP built-in server started by php -S.

For production or staging under nginx/apache + PHP-FPM, upload limits must be configured in server/PHP-FPM configuration, not in application business code.