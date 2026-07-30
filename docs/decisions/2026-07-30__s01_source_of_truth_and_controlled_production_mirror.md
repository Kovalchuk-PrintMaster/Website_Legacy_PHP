# Decision: s01 source of truth and controlled production mirror

**ID:** `FP-WEB-ADR-2026-07-30-001`
**Date:** `2026-07-30`
**Status:** `accepted`
**Scope:** versioned website code, release, production recovery and evidence

## Context

ForPrint Website is developed and validated on `s01`, while the public site is
served from shared hosting. Direct hosting edits would create an unreviewed
second code authority and make later recovery dependent on memory.

The project already uses local validation, exact Git commits, release archives,
production backups, hash verification, smoke crawls and rollback. This decision
makes that model explicit and permanent.

## Decision

1. The Git repository on `s01` is the authoritative source for versioned
   website code, documentation, migrations and release tooling.
2. Production code is a controlled mirror of a reviewed Git commit.
3. Direct manual editing of production source files is prohibited.
4. Every normal release is built from a committed local state.
5. A release plan verifies the exact archive, commit, payload and current
   production baseline before mutation.
6. A timestamped private production backup is required before file mutation.
7. Deployment transfers only manifest-listed files and verifies their hashes
   and syntax.
8. Production validation runs against the canonical HTTPS origin.
9. A failed deployment or validation triggers rollback.
10. Release reports are evidence; canonical docs explain the process.
11. Secrets are never stored in Git, release payloads or documentation.
12. Database records, uploaded media, DNS and production-only runtime secrets
    are separate state classes and are not overwritten by a code release.

## Consequences

Positive:

- one code authority;
- reproducible releases;
- visible drift before overwrite;
- exact recovery points;
- lower risk of SSH connection throttling through reusable connections;
- future operators can resume from documented coordinates and checks.

Costs:

- production changes require a local commit and release package;
- stateful DB/media operations need separate procedures;
- a baseline mismatch blocks release until investigated;
- release reports and snapshots must be retained intentionally.

## Emergency rule

A timestamped backup may be restored through a controlled recovery procedure.
Emergency restoration does not make production a new source of truth.

Before the next release, the intended state must be reconciled and committed
on `s01`.

## Canonical runbook

```text
docs/workflow/production_release_and_recovery_runbook_v0_1.md
```
