# ForPrint Hosting Mirror Operator Reporting v0.1

**ID:** `FP-WEB-WORKFLOW-HOSTING-MIRROR-REPORTING-001`
**Version:** 0.1
**Date:** 2026-08-07
**Status:** active
**Scope:** operator-facing console/reporting for hosting mirror reset and parity checks

## Purpose

The hosting mirror tools perform detailed checks. Some were introduced while
diagnosing individual frontend symptoms such as a contact schedule, specific
SVG assets or shell CSS markers.

Those checks remain useful internal guards, but they are not the correct
primary operator interface.

The operator interface is therefore **summary first, diagnostics on demand**.

## Canonical commands

```bash
make hosting-reset-from-local
make hosting-parity-check
```

Both commands route through:

```text
scripts/operations/hosting_mirror_operator.py
```

The wrapper runs the established reset/parity implementation, captures the
complete diagnostic stream and presents a stable category-level summary.

## Operator categories

Reset covers local payload, local database, local HTTP/content acceptance,
hosting backup/staging, mirror installation, production HTTP/content
acceptance, database logical parity, hosting environment/communication runtime
preservation, receipt creation and authorization safety.

Parity covers database logical parity, application files and managed media,
HTTP acceptance, managed HTTP assets, hosting environment/communication
runtime, read-only safety and overall readiness.

## Success-output policy

Routine success output is architectural rather than incident-specific.

Checks such as one logo effect, one SVG path, one contacts marker or one CSS
marker may remain in detailed diagnostics, but the normal terminal output
reports their owning category.

Example:

```text
[OK]   Database logical parity
[OK]   Application files and managed media parity
[OK]   Local and production HTTP acceptance
[OK]   Managed HTTP asset delivery
[OK]   Hosting environment and communication runtime boundary
[OK]   Read-only / no-upload / no-notification safety boundary
[OK]   Hosting mirror is ready
```

## Failure-output policy

Failures remain specific. The summary keeps the concrete underlying error,
indicates rollback completion when applicable, and gives the raw-log path.

## Detailed evidence

Every run writes:

```text
tmp/hosting-mirror-operator/<timestamp>-<operation>/raw.log
tmp/hosting-mirror-operator/<timestamp>-<operation>/summary.txt
tmp/hosting-mirror-operator/<timestamp>-<operation>/summary.json
```

Parity also copies its detailed TXT/JSON report into the same directory when
available.

## Verbose mode

```bash
FP_HOSTING_MIRROR_VERBOSE=1 make hosting-parity-check
FP_HOSTING_MIRROR_VERBOSE=1 make hosting-reset-from-local
```

Verbose mode streams the complete underlying diagnostics while retaining the
same report artifacts.

## Safety and ownership boundary

This reporting layer does not alter acceptance semantics:

- local remains the source of truth during the current pre-publication stage;
- exact logical database data parity remains required;
- normalized logical schema parity remains required;
- `vendor/` and `userfiles/` remain mirrored;
- hosting-owned environment files remain preserved;
- the external communication runtime remains preserved;
- normal acceptance sends no Telegram/email messages;
- reset failure retains combined webroot + database rollback;
- parity remains read-only.

The console is an operator interface, not an incident transcript.
