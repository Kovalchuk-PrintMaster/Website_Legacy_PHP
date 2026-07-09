# ForPrint Website — Local HTTP Smoke v0.5.8

## Status

`local_http_smoke_v0_5_8_completed`

## Purpose

Confirm that the legacy PHP website can be started locally through PHP built-in server and can serve the public frontend through HTTP.

This is a local-only smoke checkpoint.

It is not public deployment.

## Runtime command

```text
php -S 127.0.0.1:8098 -t base scripts/inspection/local_http_smoke_router.php
Smoke script
scripts/inspection/run_website_local_http_smoke.py
Compatibility fix

The legacy DB charset SQL was updated:

SET NAME UTF-8

to:

SET NAMES utf8

in:

base/core/base/models/BaseModel.php
Smoke result
LOCAL_WEBSITE_HTTP_SMOKE_OK
Route smoke
/        -> 200
/catalog -> 301
/search  -> 301
Current frontend observation

The website starts and the public frontend is generally usable.

Known early issue:

cart / корзина requires separate investigation.
Safety boundary
Local only.
Bound to 127.0.0.1.
No public deployment.
No production DB.
No secrets committed.
Admin/public exposure still not approved.
Next checkpoint

ForPrint Website — Local Operator Startup Workflow v0.5.9
