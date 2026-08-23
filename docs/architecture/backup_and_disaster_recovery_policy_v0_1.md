# ForPrint backup and disaster-recovery policy v0.1

**Status:** canonical operational policy
**Scope:** ForPrint website, production database, project development state, Google Drive archival, recovery material, retention and restore verification

## 1. Purpose

A ForPrint backup is a disaster-recovery artifact, not merely a copy of the public webroot.

A full backup is complete only when it contains enough evidence and data to recover:

1. the production website files and `userfiles`;
2. a fresh production database dump;
3. the committed Git history;
4. the current development working state, even when Git is dirty;
5. project scripts, documentation and migrations;
6. manifest/checksum/recovery instructions;
7. encrypted recovery material needed to regain infrastructure access, without embedding the independent master decryption secret.

The production database is mandatory. A website-file archive without the corresponding fresh database dump is **not a successful full backup**.

## 2. Architecture

Current interim architecture:

```text
Production hosting
  -> streamed website archive
  -> fresh transactional DB dump

Local project / s01
  -> Git bundle
  -> current dirty-worktree recovery snapshot
  -> scripts/docs/migrations
  -> encrypted access-recovery material

All parts
  -> manifest + SHA256
  -> direct rclone
  -> forprint_backup_crypt:
  -> Google Drive
  -> isolated download/restore verification
```

The unfinished Cloud Backup Manager is not an execution dependency for this interim workflow.

Google Cloud owns the Drive API / OAuth application context. Google Drive is the storage provider. `rclone` is the transport. `forprint_backup_crypt:` is the client-side encryption layer used for secret-bearing backup artifacts.

## 3. Git cleanliness is not a backup precondition

The project is continuously developed. A scheduled disaster-recovery backup must not wait for a clean worktree.

A dirty worktree is **metadata to preserve**, not an error condition.

Every backup must record:

```text
git HEAD
upstream reference
ahead/behind
git status --porcelain
git diff --binary HEAD
git diff --cached --binary HEAD
untracked file inventory
actual untracked file payload
snapshot start timestamp
snapshot end timestamp
whether Git state changed during snapshot creation
```

The committed baseline is preserved by a Git bundle.

Uncommitted development state is labelled explicitly as non-canonical working material. During restore, it must be reviewed rather than silently treated as accepted production code.

If development changes while the working-state snapshot is being built, the backup must still preserve the collected snapshot and record:

```text
development_snapshot_consistency=CONCURRENT_CHANGE_DETECTED
```

This warning does not invalidate the independently verified production webroot/database backup.

## 4. Full backup contents

Required full-backup parts:

```text
production_webroot.tar.gz
production_database.sql.gz
website_repository.bundle
website_working_state.tar.gz
git_status_before.txt
git_status_after.txt
git_diff_head.patch
git_diff_cached.patch
git_untracked_files.txt
encrypted_recovery_material.tar.gz
manifest.json
SHA256SUMS
RESTORE_README.md
```

The exact implementation may split metadata into additional files, but it must not omit the semantics above.

## 5. Database consistency

Current production tables are InnoDB.

The normal database backup procedure therefore uses a fresh `mysqldump` with transactional semantics, including at minimum:

```text
--single-transaction
--quick
--routines
--events
--triggers
--hex-blob
--default-character-set=utf8mb4
--skip-lock-tables
```

Before each backup, the engine inventory is checked again.

If any non-InnoDB persistent table appears, the weekly backup must not silently use the InnoDB-only assumption. It must select/document an appropriate consistency strategy.

## 6. Secret and recovery-material boundary

The project is intended to be operationally self-describing and transferable.

Therefore:

- all resource names, paths, account roles, remote names, procedures and recovery prerequisites are documented in the repository;
- encrypted infrastructure recovery bundles may be stored as project-controlled artifacts;
- raw OAuth tokens, raw rclone configuration, SSH private keys and plaintext credentials are never committed in plaintext;
- the independent master secret/key required to decrypt the recovery bundle is kept outside this repository and outside the same Google Drive backup set.

Current encrypted recovery material exists under:

```text
.runtime/recovery/access-bundles/
```

This material is included in disaster-recovery backups. Promotion of an encrypted bundle into a versioned `ops/recovery/access-bundles/` location requires a separate verification of encryption format, checksum and independent decryptability.

## 7. Backup frequency

Default schedule:

```text
full verified backup: once per week
recommended window: Sunday 03:30 Europe/Kyiv
```

An additional manually triggered full backup is recommended after a major production release or before a high-risk infrastructure migration.

The weekly schedule is not conditional on Git cleanliness.

## 8. Retention policy

Default full-backup retention:

```text
target weekly generations: 8
minimum verified generations that must remain: 6
pinned/milestone generations: never auto-delete
newest verified generation: never auto-delete
active verification generation: never auto-delete
```

Capacity management is proactive.

Before a new upload:

1. read provider quota through `rclone about`;
2. estimate the new artifact size;
3. preserve a safety reserve of at least 20% of total provider capacity;
4. if the new backup does not fit, select the oldest **VERIFIED + UNPINNED** generation;
5. delete only that exact generation;
6. re-read quota;
7. repeat until the new backup plus reserve fits, or until no eligible generation remains.

If no eligible generation may be deleted safely, the new backup aborts and reports a capacity incident.

The retention implementation must never globally empty Google Drive Trash as an incidental cleanup action. Space-reclaim behavior for an exact backup directory must be explicitly verified against the Drive backend.

## 9. Verification and definition of success

Upload success alone is insufficient.

A full backup is `VERIFIED` only after:

- local artifact validation;
- upload through the encrypted remote;
- exact remote file-set and plaintext-size verification;
- download to an isolated non-production directory;
- end-to-end SHA256 verification;
- full webroot extraction;
- `userfiles` count/structure verification;
- database dump readability and table-definition verification;
- Git bundle verification and isolated clone;
- scripts/docs/migrations recovery verification.

No verification step may overwrite the live webroot or import into the live production database.

## 10. Partial and failed backups

Every run uses a unique timestamp + Git checkpoint path.

A failed/partial run is never silently labelled verified.

Automatic retention of normal verified generations must not count an incomplete generation as a valid recovery point.

Cleanup of incomplete paths is a separate exact-path operation.

## 11. Ownership

Until the dedicated Cloud Backup Manager becomes production-ready, the website/project repository owns this bounded direct-rclone disaster-recovery workflow.

When Cloud Backup Manager is later adopted, it must preserve this policy contract rather than silently weaken it.
