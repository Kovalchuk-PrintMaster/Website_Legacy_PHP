# Hosting full-sync working state v0.1

**Verified:** 2026-08-18
**Status:** production-proven baseline

## Production verification result

The canonical complete local-to-hosting synchronization completed
successfully after the hosting-capacity/off-host-backup work.

Verified result:

```text
rollback snapshot: local .runtime/backups/hosting/<timestamp>
managed userfiles: 2012
application code files: 346
database tables: 30
communication acceptance: PRE + POST OK
production HTTP:
  /          200
  /catalog/  200
  /contacts/ 200
  /search/   200
remote backup archives: none
```

The rollback snapshot was streamed directly from hosting to local storage
before mutation.

## Canonical commands

```bash
make hosting-sync-full-dry-run
make hosting-sync-full

make hosting-restore-local-backup-dry-run
make hosting-restore-local-backup
```

Specific rollback snapshot:

```bash
make hosting-restore-local-backup \
  HOSTING_BACKUP=.runtime/backups/hosting/<timestamp>
```

## Complete-sync ownership

For complete synchronization:

- local database is authoritative;
- `base/userfiles/` is authoritative;
- application source under `base/` is authoritative except protected hosting
  runtime paths.

Protected production runtime remains outside complete mirror ownership.

## Hosting capacity boundary

Production hosting is runtime storage, not backup storage.

- `.forprint-releases` must not retain historical payload;
- `.forprint-backups` must not retain historical payload;
- rollback snapshots stream hosting -> local;
- capacity preflight runs before mutation;
- transient release storage is cleaned after success.

## Communication safety boundary

Required before/after production mutation:

- protected runtime ready;
- security secret/directory contract ready;
- canonical boolean runtime flags;
- Telegram readiness;
- email/SMTP readiness;
- CSRF issuer/verifier parity;
- no acceptance-test notification delivery.

## Proven compatibility fixes

Two failure modes discovered while establishing full sync are now canonical:

1. GNU tar created from `.` contains a root member named `.`. The validator
   treats it as archive metadata.
2. Remote database export runs as PHP `Standard input code`. Production PHP
   does not reliably expose `STDOUT`/`STDERR` constants in that mode, so the
   exporter uses `echo` and `php://stderr`.

Regression guard:

```bash
make hosting-sync-contract-check
```

`make hosting-sync-full` runs the guard automatically.

## Operational rule

Use the narrow frontend deployment only when the release is truly frontend
only.

If database rows, managed media, admin-managed images, catalog images or other
non-frontend state may have changed, use:

```bash
make hosting-sync-full
```

Do not return to ad-hoc partial SQL/media copy scripts for that release class.
