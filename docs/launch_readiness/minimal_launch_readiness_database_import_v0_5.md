# ForPrint Website — Minimal Launch Readiness and Database Import Plan v0.5

## Status

`minimal_launch_readiness_database_import_v0_5_prepared`

## Purpose

Prepare the inherited PHP website for a minimal practical launch without large refactoring.

The goal is to make the current legacy site run safely enough as a temporary public website while future ForPrint development continues separately.

## Strategic decision

This module is treated as:

```text
legacy PHP public website / landing channel

It is not being converted into a new ForPrint application architecture now.

What we are not doing now
large PHP refactoring;
migration to Python project structure;
deep CRM/order/payment/accounting integration;
production writes to ForPrint core modules;
new website architecture;
automatic database ownership changes.
What we are doing now
keep selected legacy source in Git;
keep local config out of Git;
keep vendor/runtime/uploads out of Git;
prepare safe local/staging database import;
document minimal server requirements;
identify launch blockers;
run the site as a temporary public presence.
Database dump policy

The website currently does not have a committed database dump.

The SQL export exists separately and may be provided later for local/staging import.

Database dumps must not be committed to Git.

Ignored examples:

*.sql
*.sql.gz
*.dump
*.dump.gz
database_dumps/
db_dumps/
dumps/
imports/
exports/

Reasons:

may contain customer data;
may contain admin users;
may contain password hashes;
may contain real product/order/contact data;
may contain operational history;
may expose business-private content.
Safe database import approach

Recommended flow when SQL dump is available:

1. Place dump in ignored local directory, e.g. database_dumps/local_legacy_site.sql.
2. Create local/staging database manually.
3. Import dump into local/staging DB only.
4. Create base/config.php locally from base/config.example.php.
5. Set local DB credentials in ignored base/config.php.
6. Run PHP syntax checks.
7. Open site in local/staging environment.
8. Change admin credentials immediately if legacy admin is usable.
9. Do not expose admin publicly before hardening.
Minimal PHP/server requirements to verify
PHP 8.2 currently passes syntax check;
Apache or Nginx + PHP-FPM;
MySQL/MariaDB-compatible database;
PHP mysqli extension;
PHP mbstring extension;
PHP curl extension if sitemap/parser features are used;
PHP gd/fileinfo recommended for uploads/images;
write access only for required runtime directories.
Runtime writable directories

Likely writable paths:

base/temp/
base/log/
base/userfiles/

These remain ignored by Git.

Before deployment, permissions must be set narrowly, not globally open.

Local config files

Ignored local files:

base/config.php
base/config.local.php
base/mail.local.php
.env
.env.*

Committed safe examples:

base/config.example.php
base/mail.example.php
Current launch blockers

Public launch is not yet approved.

Known blockers:

webroot exposure review;
admin access protection;
legacy md5 password handling;
direct $_GET/$_POST/$_COOKIE/$_FILES usage;
dynamic SQL construction;
upload handling;
runtime directory permissions;
database import validation;
real domain/server config;
HTTPS config;
robots/sitemap decision.
Minimal acceptable temporary launch idea

For a temporary public site, acceptable first launch may be:

public pages available;
admin path restricted by server-level access control;
uploads either disabled or restricted;
mail sending disabled or sandboxed until SMTP is configured safely;
database imported into staging/production copy;
HTTPS enabled;
runtime/log/config/vendor paths blocked from direct web access.
Blueprint alignment note

This checkpoint follows gradual adoption:

no destructive rewrite;
safe scoped changes only;
secrets protected;
SQL dumps ignored;
status/report updated;
legacy PHP deviation documented.
Next recommended checkpoint

ForPrint_Web_Site_Base — Webroot Exposure Hardening v0.5.1
