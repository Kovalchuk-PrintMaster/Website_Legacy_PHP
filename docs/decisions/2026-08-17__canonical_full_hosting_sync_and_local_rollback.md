# Decision: canonical full hosting sync and local rollback

**Date:** 2026-08-17
**Status:** accepted
**Scope:** complete local-to-production synchronization and rollback

## Context

Partial deployment profiles were safe for narrow frontend releases, but they
allowed legitimate local database and `base/userfiles/` changes to remain
absent from production. A later hosting-side full backup exceeded quota and
caused temporary HTTP 503, so full rollback data must remain off-hosting.

## Decision

Canonical complete synchronization:

```text
local preview validation
→ hosting storage check/preparation
→ production rollback snapshot streamed directly to local storage
→ production communication acceptance
→ exact local userfiles mirror
→ exact local application-source mirror
→ full local database mirror
→ file/database verification
→ production communication acceptance
→ HTTP acceptance
→ transient release-storage cleanup
```

Local database, `base/userfiles/`, and application source are authoritative for
complete synchronization, except protected production runtime paths.

Protected runtime:

```text
base/config.php
base/vendor/
base/log/
base/temp/
base/cache/
base/sessions/
base/.well-known/
base/cgi-bin/
base/.user.ini
base/php.ini
base/error_log
base/.htaccess
```

Rollback snapshots live only in:

```text
.runtime/backups/hosting/<timestamp>/
```

A valid snapshot requires `manifest.json` and matching SHA-256 hashes. No
complete rollback archive may be staged on production hosting.

The streamed database format is versioned. Views, triggers, routines or events
block complete synchronization until explicit support exists.

If production mutation starts and complete sync then fails, automatic restore
from the pre-release local rollback snapshot is attempted.

## Operator commands

```bash
make hosting-sync-full-dry-run
make hosting-sync-full

make hosting-restore-local-backup-dry-run
make hosting-restore-local-backup
```

Specific snapshot:

```bash
make hosting-restore-local-backup HOSTING_BACKUP=.runtime/backups/hosting/<timestamp>
```

Telegram/email readiness and CSRF issuer/verifier parity remain hard release
gates before and after mutation.
