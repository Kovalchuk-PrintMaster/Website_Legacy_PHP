# ForPrint Website — Staging Runtime Requirements v0.5.2

## Status

`staging_runtime_requirements_v0_5_2_prepared`

## Purpose

Define the minimal runtime requirements for launching the inherited PHP website in a local or staging environment.

This is not a refactoring checkpoint.

## Runtime role

The website is treated as:

```text
temporary legacy PHP public website / landing channel

It is not the canonical owner of products, clients, orders, payments, warehouse, accounting or pricing.

Required PHP runtime

Minimum practical runtime:

PHP 8.2 currently passes syntax check
Apache with .htaccess support OR Nginx with equivalent server rules
MySQL/MariaDB-compatible database

Required PHP extensions:

mysqli
json
session

Recommended PHP extensions:

mbstring
curl
gd
fileinfo
openssl
zip
Required local/staging files

Tracked source:

base/index.php
base/.htaccess
base/config.example.php
base/mail.example.php
base/composer.json
base/composer.lock

Local runtime files, not committed:

base/config.php
base/vendor/autoload.php
Runtime writable directories

Likely required:

base/log/
base/temp/
base/userfiles/

These directories remain ignored by Git.

Permissions must be narrow and deployment-specific.

Database import position

SQL dump is expected later.

Rules:

do not commit SQL dump;
place SQL dump in ignored local directory;
import into local/staging DB first;
configure base/config.php locally;
validate site behavior before public launch.
Admin restriction

Admin is not approved for public exposure.

Temporary launch rule:

admin must be restricted by web server/IP/basic auth or not exposed publicly.
Inspector

Added lightweight runtime inspector:

scripts/inspection/check_website_staging_runtime.py

It checks:

PHP binary;
required/recommended PHP extensions;
required tracked files;
local runtime files;
runtime directories;
.htaccess hardening marker;
git ignore protection for config/env/sql dumps.
Expected result before staging

The inspector should return:

READY

or:

READY_WITH_WARNINGS

Warnings are acceptable for early local checks if they are understood.

NOT_READY_FOR_STAGING means critical requirements are missing.

Next recommended checkpoint

ForPrint_Web_Site_Base — Local Database Import Preparation v0.5.3
