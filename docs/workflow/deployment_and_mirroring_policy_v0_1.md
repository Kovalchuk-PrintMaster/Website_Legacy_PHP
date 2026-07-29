# Deployment and Mirroring Policy v0.1

**Document ID:** `FP-WEB-WORKFLOW-DEPLOY-MIRROR-001`
**Version:** `0.1`
**Date:** `2026-07-27`
**Status:** active baseline
**Scope:** local ForPrint server, shared hosting publication, backups, runtime data and repeat synchronization

## Purpose

Define a repeatable publication model that keeps the local project and the hosting copy compatible without overwriting production-owned data or environment-specific secrets.

The policy separates:

1. application code;
2. environment configuration and secrets;
3. production data and uploaded media;
4. logs, temporary files and sessions;
5. documentation and release evidence.

A single broad filesystem mirror is not an approved deployment method.

## Source-of-truth model

| Area | Source of truth | Normal direction |
|---|---|---|
| Application code and frontend assets | local project | local → hosting |
| Versioned documentation | local repository | local/Git only |
| Hosting secrets and environment configuration | hosting environment | never mirrored as application code |
| Local development secrets | local environment | never uploaded |
| Production database | production hosting | production backup → protected local backup |
| Production uploads in `base/userfiles/` | production hosting after launch | production backup → protected local backup |
| Logs, cache, temporary files and sessions | each runtime environment | not mirrored between environments |

The local project is the source of truth for deployable code. Production remains the source of truth for user-generated or operator-generated runtime data created after launch.

## Deployment unit

A user-visible function must be deployed as one compatible release unit.

For communication controls this includes, where applicable:

```text
button or form template
frontend JavaScript
stable HTTP endpoint
validation and dispatch code
transport adapter interfaces
non-secret default configuration
```

Do not maintain a manual “never update these PHP/JS files” list for business functionality. Such a list creates version drift between templates, JavaScript and controllers.

Instead, every release uses:

1. an explicit include manifest;
2. an explicit protected-path manifest;
3. local and remote checksums;
4. a remote backup;
5. staged installation;
6. post-deployment smoke tests;
7. automatic or documented rollback.

## Deployable application content

The normal candidate set may include:

```text
base/index.php
base/.htaccess
base/core/
base/templates/
base/libraries/
base/composer.json
base/composer.lock
base/vendor/
```

`base/vendor/` is deployed only as a complete Composer dependency set compatible with `composer.lock`. Partial dependency updates are prohibited.

`base/config.php` and `base/mail.php` remain high-risk until all environment-specific values are externalized. They require explicit review in each release manifest and are not silently overwritten.

## Protected environment content

The following paths and value classes are never overwritten by routine code synchronization:

```text
base/config.local.php
base/mail.local.php
base/.env
base/.env.*
environment variables
SMTP credentials
Telegram bot token
Telegram chat ID
API keys and passwords
```

Secrets must remain outside Git, documentation, patch output and browser-delivered code.

Recommended configuration names:

```text
FORPRINT_EMAIL_ENABLED
FORPRINT_TELEGRAM_ENABLED
FORPRINT_SMTP_HOST
FORPRINT_SMTP_PORT
FORPRINT_SMTP_USER
FORPRINT_SMTP_PASSWORD
FORPRINT_SMTP_ENCRYPTION
FORPRINT_MAIL_FROM
FORPRINT_MAIL_TO
FORPRINT_TELEGRAM_BOT_TOKEN
FORPRINT_TELEGRAM_CHAT_ID
```

The exact storage mechanism may differ by environment, but the application-facing configuration contract must remain stable.

## Protected runtime content

Routine application deployment must not delete or replace:

```text
base/userfiles/
base/log/
base/temp/
session storage
production database files or dumps
hosting control-panel files
hosting certificate material
```

After launch, `base/userfiles/` is production-owned because administrators may upload images through the website.

The approved direction for production media is:

```text
hosting userfiles → timestamped local backup
```

This is backup intake, not a destructive reverse deployment.

## Database policy

Application code deployment and database synchronization are separate operations.

Rules:

- no schema or data mutation is implied by code deployment;
- every database migration requires an explicit versioned migration;
- production export is captured before a migration;
- production data is never replaced by an older local database copy;
- local testing imports use separate backups and explicit operator approval.

## Repeat synchronization contract

Every repeat deployment follows this order:

```text
1. identify exact release files
2. read-only local/remote drift audit
3. local PHP lint and targeted checks
4. remote backup of every target
5. upload to remote staging
6. remote PHP lint
7. install exact release files
8. checksum equality verification
9. HTTP functional smoke tests
10. retain backup and safe report
```

A release must stop before installation when unexpected remote drift affects a target file.

## Deletion policy

Raw `rsync --delete` against the production webroot is prohibited.

Deletion is allowed only when:

- the exact path is listed in the release manifest;
- the path is application-owned;
- a backup exists;
- the deletion is required for a reviewed rename or removal;
- post-deployment checks cover the affected route.

The Unicode correction of `CartController.php` is an example of a controlled one-file rename, not a precedent for broad deletion.

## File modes and ownership

Deployment must preserve hosting-compatible ownership and apply explicit safe modes:

```text
application files: 0644
application directories: hosting-compatible executable directory mode
secret files: restrictive mode controlled by the environment
```

The deployment user must not change ownership of unrelated hosting resources.

## Local notification behavior

The local environment must support functional form testing without contacting real recipients.

Approved local modes:

```text
NullTransport
SpoolTransport
explicitly authorized test recipient
```

A null or spool mode must keep the same request validation and response contract as production transports.

## Release checks for communication buttons

Before a communication release is accepted:

- the button opens or submits as designed;
- frontend validation and server validation agree;
- the endpoint rejects unsupported methods;
- duplicate submissions are controlled;
- no SMTP or Telegram secret appears in HTML, JavaScript or logs;
- disabled channels return a controlled response;
- email and Telegram failures are isolated;
- existing order-email behavior is regression-tested;
- technical-domain robots blocking remains unchanged before launch.

## Rollback

A rollback restores the complete functional release unit, not only the most visible file.

For communication functionality this may include:

```text
template
JavaScript
endpoint
service or controller
configuration schema without secret values
```

Environment secrets and production data are not rolled back by code restoration.

## Evidence and retention

Each deployment checkpoint records:

- timestamp;
- local and remote checksums;
- files installed, renamed or removed;
- lint results;
- route smoke results;
- backup locations;
- rollback result;
- confirmation that database, DNS, SSL and secrets were untouched unless explicitly in scope.

Reports remain in `tmp/` or the project’s evidence location. Reports are evidence; this document defines policy.

## Prohibited shortcuts

- editing production code manually without restoring the local source of truth;
- excluding random functional scripts from future synchronization;
- storing tokens in templates or frontend JavaScript;
- overwriting `userfiles/` with an older local copy;
- deploying controller code without its matching template/JavaScript;
- treating Git state as a replacement for hosting backups;
- treating a hosting backup as a replacement for version control.

## Review triggers

Create a new version of this policy when:

- deployment changes to release directories or symlink switching;
- production uploads move to object storage;
- a queue or worker is introduced;
- secrets move to a new environment-management mechanism;
- database migrations become part of an automated release pipeline;
- local and hosting roles change.
