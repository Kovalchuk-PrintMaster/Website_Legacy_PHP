# Decision: hosting capacity and off-host backup policy

**Date:** 2026-08-17
**Status:** accepted operational architecture decision
**Scope:** production hosting deployment, rollback snapshots, hosting capacity

## Context

A full-mirror preparation attempted to create a compressed production
`userfiles` backup on the hosting filesystem. The hosting account had
insufficient quota. `gzip` failed with `Disk quota exceeded`, and the public
website temporarily returned HTTP 503 until the temporary release artifacts
were removed.

This incident establishes that the hosting account cannot be treated as a
general-purpose backup volume.

## Decision

1. Production hosting is a **runtime target**, not a persistent backup target.
2. Persistent deployment archives, database dumps and historical release
   backups must not remain on hosting.
3. High-risk rollback snapshots are streamed **directly hosting -> local**.
   A complete archive must not first be assembled on hosting.
4. Local staging is:

   `.runtime/backups/hosting/`

   It is local-only, mode 0700 and excluded from Git.
5. Routine release snapshots cover mutable webroot state and database. They
   intentionally exclude production `config.php`, `vendor/`, logs, temp/cache
   and sessions. Protected disaster-recovery backup of secrets remains a
   separate infrastructure responsibility.
6. Existing `.forprint-releases` and `.forprint-backups` directories may remain
   for deployer compatibility, but their contents are transient and must be
   removed after a successful deployment.
7. Before mutating frontend deployment:
   - remove stale release/backup payload;
   - perform a temporary bounded write probe;
   - remove the probe immediately;
   - refuse deployment if the probe fails.
8. The initial write probe is 64 MiB. It is a deployment guard, not complete
   quota monitoring.
9. Future full DB/media mirror operations must run a local streamed backup
   first and must never create a full production backup archive on hosting.
10. Historical retention and Google Drive are owned by the separate Cloud
    Backup Manager, not by website deployment tooling.

## Canonical operator commands

```bash
make hosting-storage-check
make hosting-storage-prepare
make hosting-clean-release-storage
make hosting-backup-local-dry-run
make hosting-backup-local
```

`hosting-deploy-frontend` is guarded by capacity preparation before deployment
and transient release-storage cleanup after a successful deployment.

## Communication safety

Existing Telegram/email runtime and CSRF acceptance remain mandatory release
gates. Storage capacity is an additional gate, not a replacement.

## Failure behavior

- capacity probe failure -> block deployment;
- insufficient local disk -> block backup before transfer;
- interrupted streamed backup -> no `manifest.json`, therefore incomplete;
- remote cleanup must never touch live webroot content, database, `config.php`
  or protected communication runtime.

## Future integration

Target direction:

```text
production website
    -> streamed rollback snapshot on local project storage
    -> Cloud Backup Manager
    -> Google Drive retention
```

The website hosting must not become the intermediate archive repository.
