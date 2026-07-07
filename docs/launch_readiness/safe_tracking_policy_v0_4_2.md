# ForPrint_Web_Site_Base — Safe Tracking Policy and Config Split v0.4.2

## Module

`forprint_website`

## Website base

`base/`

## Status

`safe_tracking_policy_v0_4_2_prepared`

## Purpose

Define what parts of the inherited PHP website base may be considered for git tracking and what must remain local, ignored, generated, runtime-only, or subject to later review.

This policy does not approve a broad commit of `base/`.

## Hard rule

Do not run:

```text
git add base/

Broad tracking of the inherited base remains forbidden.

Only explicitly approved file groups may be staged in later checkpoints.

Current inventory reference

Inventory file:

coordination/inventory/base_inventory_v0_4_1.tsv

Inventory report:

coordination/reports/forprint_website_legacy_base_inventory_v0_4_1.md

Summary from inventory:

total files scanned: 525;
total size scanned: 170.04 MB;
source_code: 247 files;
runtime_uploads: 186 files;
vendor_dependencies: 83 files;
runtime_logs: 5 files;
generated_files: 3 files;
local_config: 1 file.
Tracking categories
1. Candidate source files to track later

These may be considered for selected tracking after review:

base/index.php
base/.htaccess
base/core/
base/libraries/
base/templates/
base/composer.json
base/composer.lock
base/mail.example.php

Conditions before tracking:

no secrets;
PHP syntax check passes;
web-root exposure policy is understood;
selected files only;
no broad git add base/.
2. Local config / secret-bearing files

These must remain untracked:

base/config.php
base/config.local.php
base/mail.local.php

Policy:

base/config.php is treated as local config until config split is completed.
Real DB credentials must never be committed.
Real SMTP credentials must only live in base/mail.local.php or another explicitly ignored local-only config.
A future base/config.example.php may be created with fake/example values only.
3. Runtime logs

These must remain ignored:

base/log/

Policy:

logs are runtime artifacts;
logs may contain operational or sensitive information;
logs must not be committed.
4. Temporary/generated files

These must remain ignored:

base/temp/
base/sitemap.xml

Policy:

generated files should be recreated by the application or deployment process;
generated files are not source-of-truth.
5. Runtime uploads / media

These must remain ignored by default:

base/userfiles/

Policy:

userfiles/ is currently treated as runtime upload/media storage;
selected seed media may be reviewed later;
no broad tracking of uploaded/runtime media;
PHP execution must be blocked in upload/media directories before public launch.
6. Vendor dependencies

These must remain ignored by default:

base/vendor/
base/composer.phar

Policy:

preferred future path is dependency restoration from Composer metadata;
base/composer.json and base/composer.lock may be tracked after review;
direct public access to vendor/ must be blocked before launch;
PHPMailer helper scripts must not be publicly reachable.
Config split plan

Next config step should create or prepare:

base/config.example.php

Rules:

fake/example values only;
no production DB host/user/password/name;
no production SMTP credentials;
no API tokens;
no real secret placeholders that look active.

Local runtime config should remain:

base/config.php
base/config.local.php
base/mail.local.php

and must stay ignored.

Selected base source checkpoint strategy

A later selected source checkpoint may stage only approved source files, for example:

base/index.php
base/.htaccess
base/core/
base/libraries/
base/templates/
base/composer.json
base/composer.lock
base/mail.example.php

But only after:

config split is complete;
local config is ignored;
runtime directories are ignored;
vendor policy is accepted;
web-root hardening plan is ready;
git diff --check passes;
make check passes.
Explicit non-goals

This policy does not:

deploy the website;
connect production DB;
connect production SMTP;
approve public admin;
approve uploads;
approve order/cart as canonical ForPrint data;
approve broad git add base/;
connect Integration Gateway or other ForPrint core modules.
Current launch status

Public launch remains blocked.

Next recommended step

ForPrint_Web_Site_Base — Config Example and Secret Scan v0.4.3

Recommended scope:

inspect base/config.php locally with redaction;
create base/config.example.php with fake values only;
keep base/config.php ignored;
run grep-based secret scan;
do not commit real config;
do not commit broad base/.
