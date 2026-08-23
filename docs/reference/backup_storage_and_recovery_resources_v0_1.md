# ForPrint backup storage and recovery resources v0.1

**Status:** current factual reference
**Purpose:** enable a new operator to locate the backup system without relying on personal memory

## 1. Website project

```text
repository:
/srv/software_development/forprint-project/forprint_website

production SSH:
825163-nikolay.k@185.86.76.182

production webroot:
/var/www/825163-nikolay.k/data/www/forprint.net.ua

production media:
/var/www/825163-nikolay.k/data/www/forprint.net.ua/userfiles
```

Production hosting is runtime storage, not historical backup storage.

## 2. Local rollback/backups

Existing local rollback area:

```text
.runtime/backups/hosting/
```

Direct Google Drive backup working area:

```text
.runtime/backups/google_drive/<run-id>/
```

Backup reports:

```text
.runtime/reports/google_drive_full_backup_<run-id>/
```

`.runtime` is operational state and is not the canonical repository documentation layer.

## 3. Google resources

Current configured rclone remotes:

```text
forprint_gmhost:
forprint_gdrive:
forprint_backup_crypt:
```

Current full-backup target:

```text
forprint_backup_crypt:
```

Logical archive namespace:

```text
forprint/website_archives/
```

The crypt remote uses the existing Google Drive / Google Cloud OAuth configuration.

Raw OAuth/rclone credentials are not documented in plaintext here.

## 4. rclone runtime

Current rclone configuration location discovered on s01:

```text
/root/.config/rclone/rclone.conf
```

This file is sensitive and must not be committed in plaintext.

The disaster-recovery system depends on an independently recoverable encrypted copy of the required rclone/crypt material.

## 5. Existing encrypted access-recovery bundle

Current operational recovery material discovered:

```text
.runtime/recovery/access-bundles/
  forprint-access-recovery-20260814_163640-9e334264f86c.tar.gz.gpg
  forprint-access-recovery-20260814_163640-9e334264f86c.tar.gz.gpg.sha256
  README_FORPRINT_RECOVERY.md
```

The encrypted bundle itself is project-related recovery material.

The independent decryption tool/master secret is intentionally held in separate cloud custody and is not stored beside the encrypted archive.

Before promoting this bundle into a versioned repository location, verify:

```text
checksum
encryption format
independent decryptability
contents inventory
absence of plaintext secret leakage
```

Target versioned location after that verification:

```text
ops/recovery/access-bundles/
```

## 6. Canonical backup documentation

```text
docs/architecture/backup_and_disaster_recovery_policy_v0_1.md
docs/workflow/direct_google_drive_backup_and_restore_runbook_v0_1.md
docs/reference/backup_storage_and_recovery_resources_v0_1.md
```

## 7. Scheduling state

At the 2026-08-23 audit:

- no ForPrint backup systemd timer was present;
- no root crontab entry existed;
- the visible `dpkg-db-backup.timer` is an operating-system package timer and is **not** the ForPrint website/database backup.

The ForPrint weekly timer must therefore be installed explicitly after the first verified manual full backup.

## 8. Handoff minimum

A new project operator must be able to answer from repository documentation:

1. what is backed up;
2. where production is;
3. where Google Drive generations are stored;
4. how the production DB is included;
5. how dirty Git state is preserved;
6. how retention works;
7. where encrypted recovery material is;
8. where the separate master decryption capability is held;
9. how to run a backup manually;
10. how to verify and restore in isolation;
11. how the weekly timer is installed and checked.

If any answer depends only on somebody's memory, the backup documentation is incomplete.
