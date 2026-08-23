# ForPrint direct Google Drive backup and restore runbook v0.1

**Status:** canonical operator workflow
**Provider:** Google Drive
**Transport:** `rclone`
**Encrypted target:** `forprint_backup_crypt:`
**Cloud Backup Manager:** intentionally not used for the current interim workflow

## 1. Normal weekly workflow

The scheduled full backup runs once per week.

Git may be clean or dirty.

The workflow must:

1. record exact Git baseline and dirty state;
2. snapshot uncommitted working material;
3. stream the complete production webroot from hosting without creating a full archive on hosting;
4. create a fresh transactional production DB dump;
5. build Git/project/recovery artifacts locally;
6. create manifest and SHA256 checksums;
7. apply the retention/capacity preflight;
8. upload to a unique encrypted Google Drive generation;
9. verify remote inventory;
10. download the generation to an isolated local directory;
11. perform webroot, database and Git restore-readiness checks;
12. mark the generation `VERIFIED` only after all checks pass;
13. retain small evidence locally and remove disposable large local drill payloads.

## 2. Dirty worktree handling

A dirty worktree does not block backup.

The backup captures:

```text
HEAD
git status before snapshot
binary diff against HEAD
binary cached/index diff
untracked inventory and bytes
git status after snapshot
```

Restore guidance must say clearly:

> Files listed as dirty/untracked at backup time were development work in progress. Their inclusion protects against data loss but does not make them accepted or production-ready.

If status changes during snapshot creation, record the concurrent-change warning. Do not skip the entire weekly production/database backup.

## 3. Required production data

The weekly backup must contain both:

```text
production_webroot.tar.gz
production_database.sql.gz
```

Absence or failed validation of either artifact means the run is not a valid full backup.

## 4. Google Drive location

Logical backup namespace:

```text
forprint_backup_crypt:forprint/website_archives/<run-id>/
```

A run ID is timestamped and tied to the Git checkpoint, for example:

```text
20260823T083000Z_d499e60affce
```

Do not write secret-bearing archives through the plain Drive remote when the crypt remote is available.

## 5. Retention

Normal policy:

```text
weekly target: 8 verified generations
hard floor: 6 verified generations
pinned milestone backups: unlimited by automatic pruning
provider reserve: >= 20% total quota
```

Pruning happens **before** a new upload when capacity requires it.

Only the oldest exact `VERIFIED + UNPINNED` generation is eligible.

After each deletion, quota is checked again.

Do not delete the newest verified backup, a pinned backup, or the generation currently being verified.

## 6. Manual full backup

Manual execution is appropriate:

- immediately after an important production release;
- before a high-risk migration;
- after recovery material changes;
- when an operator wants an additional pinned milestone.

Manual runs follow the same verification contract as scheduled runs.

## 7. Restore-readiness drill

Verification restore always targets an isolated non-production directory.

Required checks:

```text
SHA256 all downloaded artifacts
extract complete webroot
confirm config.php and userfiles
validate database gzip/SQL structure
verify expected table count
git bundle verify
clone Git bundle
confirm checkpoint
confirm scripts/docs/migrations
```

Forbidden during a verification drill:

```text
overwrite live production webroot
import into live production database
replace production config
modify Google Ads/Search Console
```

## 8. Real disaster recovery

A real production restore is a separate operator-approved action.

Recovery order:

1. obtain the repository/project documentation;
2. obtain the independent decryption tool/master secret from its separate custody location;
3. recover the rclone/crypt access material;
4. locate the desired `VERIFIED` generation;
5. download and verify checksums;
6. restore webroot and DB into an isolated environment first;
7. review the captured dirty-development state;
8. only then design/approve production cutover.

## 9. Scheduler

Target scheduler:

```text
systemd timer
weekly
Sunday 03:30 Europe/Kyiv
Persistent=true
RandomizedDelaySec=20m
```

The timer is enabled only after one manual full backup has completed with a successful isolated restore drill.

## 10. Failure handling

Before Google Drive mutation:
- preserve logs;
- fix the cause;
- a later run may start with a new unique run ID.

After Google Drive mutation begins:
- do not blindly reuse the same remote path;
- inspect the unique partial run;
- mark/remove it only through an exact-path cleanup procedure.

Automatic retention never treats a failed/partial run as verified.

<!-- FP-PERMANENT-GDRIVE-BACKUP-AUTOMATION-V1-START -->
## 11. Permanent weekly automation

The first direct Google Drive disaster-recovery baseline was verified successfully before enabling automation:

```text
run_id:
20260823T090153Z_8aced26df4b0

committed baseline:
8aced26df4b026b26addf74880cad0515a6ea5bb

remote:
forprint_backup_crypt:forprint/website_archives/20260823T090153Z_8aced26df4b0

status:
VERIFIED + PINNED

working-state consistency:
STABLE_DURING_SNAPSHOT
```

Permanent executable:

```text
scripts/maintenance/backup_forprint_to_google_drive.py
```

The permanent executable deliberately does **not** pin itself to one historical Git commit, one historical production file count, or one historical database table count. At the start of each run it chooses the current committed `HEAD` as the recovery baseline, records upstream/ahead-behind information when available, and captures the dirty/staged/untracked working state separately.

Git cleanliness and upstream synchronization are not backup preconditions.

Normal manual execution:

```bash
cd /srv/software_development/forprint-project/forprint_website
python scripts/maintenance/backup_forprint_to_google_drive.py
```

Manual milestone/pinned execution:

```bash
cd /srv/software_development/forprint-project/forprint_website
FORPRINT_BACKUP_PIN=1 \
FORPRINT_BACKUP_PIN_REASON="before major infrastructure migration" \
python scripts/maintenance/backup_forprint_to_google_drive.py
```

Canonical systemd units:

```text
ops/systemd/forprint-google-drive-backup.service
ops/systemd/forprint-google-drive-backup.timer
```

Installed system units:

```text
/etc/systemd/system/forprint-google-drive-backup.service
/etc/systemd/system/forprint-google-drive-backup.timer
```

Schedule:

```text
Sunday 03:30 Europe/Kyiv
RandomizedDelaySec=20m
Persistent=true
```

The systemd service is serialized through:

```text
/run/lock/forprint-google-drive-backup.lock
```

Useful operator commands:

```bash
systemctl status forprint-google-drive-backup.timer
systemctl list-timers forprint-google-drive-backup.timer
systemctl start forprint-google-drive-backup.service
journalctl -u forprint-google-drive-backup.service
```

A scheduled generation is unpinned by default. It becomes `VERIFIED` only after the full isolated download/restore drill passes. Milestone generations are pinned only by explicit operator intent.

The previously verified pinned baseline remains a protected disaster-recovery generation and is not replaced by installation of the timer.
<!-- FP-PERMANENT-GDRIVE-BACKUP-AUTOMATION-V1-END -->
