# ForPrint Website — Webroot Exposure Hardening v0.5.1

## Status

`webroot_exposure_hardening_v0_5_1_prepared`

## Purpose

Add minimal webroot protection for the temporary legacy PHP website before staging/public launch.

This checkpoint does not refactor the PHP application.

## Scope

Protected by `base/.htaccess`:

```text
base/config.php
base/config.local.php
base/mail.local.php
base/core/
base/vendor/
base/log/
base/temp/
.env
.env.*
*.sql
*.sql.gz
*.dump
*.dump.gz
*.sqlite
*.sqlite3
*.db
*.bak
*.backup
Why this matters

The repository now tracks selected legacy source, but local config, runtime files, dependencies, SQL dumps and env files must not be directly reachable through HTTP.

Admin note

Admin remains blocked for public launch.

This checkpoint does not make the admin panel safe.

Recommended temporary public launch rule:

/admin must be restricted by web server/IP/basic auth or not exposed publicly.
Apache note

The protection is added to .htaccess, so it works only when the web server honors .htaccess rules.

For Nginx deployment, equivalent location rules must be added at server config level.

Still not solved
legacy md5 password handling;
direct request superglobals;
dynamic SQL construction;
upload hardening;
admin/session hardening;
runtime permissions;
real database import validation;
HTTPS production config.
Next recommended checkpoint

ForPrint_Web_Site_Base — Staging Runtime Requirements v0.5.2
