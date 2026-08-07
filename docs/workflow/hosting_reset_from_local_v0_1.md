# Hosting reset from deployment ownership policy

**ID:** `FP-WEB-WORKFLOW-HOSTING-RESET-001`
**Date:** 2026-08-06
**Status:** active during the pre-launch test period

## Canonical commands

Read-only parity check:

```bash
make hosting-parity-check
```

Full hosting reset from the accepted local state:

```bash
make hosting-reset-from-local
```

The Make target grants process-local authorization. The persistent
`FP_HOSTING_RESET_ALLOWED` value remains or returns to `0`.

## Mirrored scope

The reset mirrors all non-operational files under `base/`, including:

```text
communication-request.php
core/
libraries/
templates/
vendor/
userfiles/
index.php
composer.json
composer.lock
```

The complete local database replaces the hosting database.

## Preserved environment pack

```text
base/config.php
base/mail.php
base/.htaccess
base/.user.ini
base/php.ini
/var/www/.../.forprint-secrets/communication_runtime.php
```

Operational paths such as cache, logs, sessions, temp and `.well-known` are
not application source data. Cache, temp and sessions may be cleared during
acceptance.

## Reset sequence

1. Validate the local Git branch, required files and PHP syntax.
2. Build a complete local application snapshot.
3. Create a complete local database dump.
4. Verify local routes, contacts schedule, CSS and logo SVG masks.
5. Record the hosting environment pack hashes and modes.
6. Create unique remote stage and backup directories.
7. Back up the complete hosting webroot.
8. Back up the complete hosting database.
9. Mirror the local application with deletion of hosting-only files.
10. Replace the hosting database with the local dump.
11. Verify the application manifest and clean rsync state.
12. Compare deterministic database fingerprints.
13. Verify production routes, contacts schedule, logo SVG masks and CSS.
14. Confirm that the hosting environment pack is unchanged.
15. Write `ACCEPTED.txt`, report and receipt.

## Rollback

After any failed installation or acceptance:

1. Restore the complete previous webroot.
2. Clear the partially imported database.
3. Restore the pre-reset hosting database dump.
4. Verify established routes.
5. Verify the environment pack.
6. Record the rollback reason.

## Required production acceptance

```text
/                          HTTP 200
/catalog/                  HTTP 200
/contacts/                 HTTP 200 and working schedule
/nashi-posluhy/            HTTP 200
/communication-request.php HTTP 405 for GET
```

Both logo masks must return HTTP 200:

```text
/userfiles/footer_settings/forprint_logo_white.svg
/userfiles/settings/img-print-studiia-povnoho-tsyklu_01.svg
```

The delivered `forprint-shell.css` hash must equal the local file, and the
complete database fingerprint must equal the local database fingerprint.

## Safety rules

- Never print database passwords or communication secrets.
- Never run the reset without a successful local acceptance.
- Never use broad hosting deletion outside the configured webroot.
- Never delete the external communication runtime.
- Never treat reports as secret storage.
- Keep the unique remote backup until the next accepted recovery checkpoint.

<!-- FP_DEPLOYMENT_OWNERSHIP_MODEL_V0_1_START -->
## Current database ownership policy

Normal reset is policy-aware rather than an unconditional whole-database clone.

```text
local canonical:
  application files
  database schema
  non-operational database content

production canonical:
  production-operational row content
  initial table: communication_requests

hosting canonical:
  protected runtime/environment state
```

Operational schema mismatch is blocking. Operational row/content drift is
informational. Normal reset preserves operational rows; explicit destructive
replacement requires a separately named high-risk operation.
<!-- FP_DEPLOYMENT_OWNERSHIP_MODEL_V0_1_END -->
