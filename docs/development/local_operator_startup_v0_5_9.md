# ForPrint Website — Local Operator Startup v0.5.9

## Status

`local_operator_startup_v0_5_9_completed`

## Purpose

Provide short operator commands for starting the legacy PHP website locally and opening it from a Windows workstation through SSH tunnel.

## Server-side start

Run on `s01`:

```bash
cd /srv/software_development/forprint-project/forprint_website
make site-start

This starts:

http://127.0.0.1:8098/

The PHP server is intentionally bound to 127.0.0.1.

Do not expose it through 0.0.0.0 during local review.

Smoke check
make site-smoke

Expected result:

LOCAL_WEBSITE_HTTP_SMOKE_OK
Windows tunnel helper
scripts/windows/start_website_tunnel.bat

The helper opens an SSH tunnel:

127.0.0.1:8098 on Windows -> 127.0.0.1:8098 on s01

and then opens:

http://127.0.0.1:8098/
Manual tunnel command
ssh -N -L 8098:127.0.0.1:8098 s01
Safety boundary
Local review only.
No public launch.
No admin exposure approval.
No production DB.
No secrets committed.
Next checkpoint

ForPrint Website — Frontend Refresh Working Plan v0.6.0
