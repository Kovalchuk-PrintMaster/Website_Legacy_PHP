# Makefile and controlled hosting deployment operator guide v0.1

**ID:** `FP-WEB-WORKFLOW-MAKE-DEPLOY-001`
**Date:** 2026-08-06
**Status:** implementation candidate for local validation

## Purpose

Use one canonical local operator interface and one short hosting command:

```bash
make deploy
```

Make remains a thin interface. Deployment logic lives in:

```text
scripts/maintenance/deploy_website_to_hosting.py
```

## Canonical local runtime

The only background preview owner is:

```text
forprint-website-preview.service
```

The rebuilt Makefile removes competing PHP start methods and `/tmp` PID-file
ownership.

```bash
make preview-status
make preview-restart
make preview-smoke
make db-status
```

`db-status` checks only service, listener and config-file presence. It neither
prints credentials nor changes the database.

## One-time hosting setup

```bash
make deploy-init
```

Fill the ignored mode-0600 file:

```text
.runtime/env/website.deploy
```

It needs SSH coordinates, public webroot, private staging/backup roots, remote
PHP and the public HTTPS URL.

```bash
make deploy-check
make deploy-dry-run
```

After reviewing the dry-run:

```text
FP_DEPLOY_ALLOWED=1
```

For intentional phone review of uncommitted local frontend work:

```text
FP_DEPLOY_ALLOW_DIRTY=1
```

## Deployment sequence

`make deploy` performs local PHP and HTTP checks, read-only SSH validation,
builds a SHA-256-manifested working-tree payload, uploads it to private remote
staging, runs remote PHP lint, backs up every existing target, installs without
`--delete`, verifies installed hashes, runs public HTTPS smoke tests, and
accepts or rolls back.

## Excluded production-owned state

```text
base/config.php
base/mail.php
base/userfiles/
base/log/
base/logs/
base/temp/
base/tmp/
base/cache/
base/sessions/
base/vendor/
.runtime/
database_dumps/
```

Database synchronization is separate. Production uploads remain
production-owned.

## Evidence

Local safe reports:

```text
tmp/deployments/<release-id>/report.json
```

Remote stage and backup paths come only from the ignored runtime config.

The deployment tool does not commit, push or mutate the database. It rejects
symlinks, protected paths and private-key markers. A failed public smoke test
removes newly created files and restores replaced files from the remote backup.

<!-- FP-EXACT-MANIFEST-COMMUNICATION-CHECK-V0-1-START -->
## Exact release manifests and communication acceptance

Deployment payloads are selected only through a reviewed manifest:

```text
config/deployment/mobile_portrait_phase_1_v0_1.manifest
```

Paths are relative to `base/`. The current manifest contains exactly eight
mobile portrait phase 1 files. The deploy tool rejects missing files,
duplicates, symlinks, traversal, absolute paths, protected paths, and unlisted
working-tree files.

Canonical commands:

```bash
make deploy-check
make communication-check
make deploy-dry-run
make deploy
```

`make communication-check` is non-sending. It validates the production
LiteSpeed web runtime, private mode-0600 communication configuration, database
schema, PHPMailer, Telegram readiness, and email readiness. It creates and
removes a randomly named guarded diagnostic file and never submits a form.

`make deploy` performs the same non-sending check before upload and after
installation. Post-install failure triggers rollback and a post-rollback
check. Normal deployment never sends email or Telegram test notifications.

Reference:
`docs/workflow/mobile_portrait_phase_1_release_manifest_v0_1.md`
<!-- FP-EXACT-MANIFEST-COMMUNICATION-CHECK-V0-1-END -->

<!-- FP-HOSTING-LOCAL-MIRROR-V0-1-START -->
## Temporary pre-launch hosting policy

The local server is the only source of truth. Incremental deployment is not
the canonical publication path during this period.

Use:

```bash
make hosting-parity-check
make hosting-reset-from-local
```

The reset mirrors code, styles, `vendor/`, `userfiles/` and the complete
database. It preserves the hosting environment pack, creates full webroot and
database backups, verifies exact fingerprints, and rolls back both layers on
failure.

Decision:
`docs/decisions/2026-08-06__local_source_of_truth_and_disposable_hosting_mirror.md`

Workflow:
`docs/workflow/hosting_reset_from_local_v0_1.md`
<!-- FP-HOSTING-LOCAL-MIRROR-V0-1-END -->
