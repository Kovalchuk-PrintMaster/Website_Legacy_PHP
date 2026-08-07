# Decision: Hosting Mirror Logical Database Parity and Environment Preservation

**ID:** `FP-WEB-ADR-2026-08-07-001`
**Date:** 2026-08-07
**Status:** accepted
**Scope:** local-to-hosting mirror, database parity, hosting runtime preservation

## Context

The local ForPrint Website instance is the canonical application/data source during the current pre-publication stage. Hosting is a rebuildable public mirror.

A full reset successfully imported the current local database and production HTTP acceptance showed the current contact/catalog state. Exact table content hashes matched local. The reset nevertheless rolled back because the old fingerprint treated raw server-specific `SHOW FULL COLUMNS` metadata as exact cross-engine schema identity.

Hosting also contains configuration/runtime that must not be overwritten by local development files, including database connection configuration and communication runtime used by public forms, Telegram and email integrations.

## Decision

1. Local remains canonical for the current mirror stage.
2. Application code, project assets, `vendor/`, managed `userfiles/` and complete logical database contents are mirrored.
3. Database content parity remains exact.
4. Database schema parity remains blocking but uses normalized logical column semantics rather than raw cross-engine metadata.
5. Collation labels, integer display widths and other documented presentation metadata do not by themselves fail a mirror when logical structure/data are equivalent.
6. Hosting-owned environment/runtime paths remain preserved.
7. External communication runtime remains hosting-owned and outside the ordinary payload.
8. Post-install failure continues to restore both webroot and database.
9. Normal mirror acceptance must not send real Telegram/email messages.
10. Manual production edits are not the canonical synchronization method.

## Hosting-owned preservation contract

Exact webroot files:

```text
.htaccess
.user.ini
config.php
mail.php
php.ini
error_log
```

Runtime/environment prefixes:

```text
.well-known/
cache/
env/
log/
logs/
sessions/
temp/
```

The external communication runtime path is deployment-configured and intentionally not duplicated here as a secret-bearing absolute path.

## Database parity semantics

Exact:

```text
object set
row count
row content
```

Normalized logical schema:

```text
column order/name
normalized type
nullability
key role
normalized default
normalized extra semantics
```

Not equality requirements:

```text
database name
physical database size
collation label alone
integer display width
DEFAULT_GENERATED decoration
server version
```

## Consequences

- successful compatible MariaDB/MySQL imports are not rolled back for representation noise;
- true data loss remains detectable;
- true logical schema drift remains detectable;
- production credentials/communication runtime remain protected;
- rollback safety remains intact.

Any future schema normalization must be narrow and evidence-backed.

## Rollback

This decision does not weaken rollback. If file, HTTP, logical database or environment-preservation acceptance fails after mutation, restore both previous webroot and previous database.

## References

- `docs/workflow/hosting_mirror_reset_runbook_v0_1.md`
- `coordination/reports/2026-08-07_hosting_mirror_database_parity_incident_v0_1.md`
- `scripts/maintenance/reset_hosting_from_local.py`
- `scripts/inspection/check_hosting_mirror_parity.py`

<!-- FP_DEPLOYMENT_OWNERSHIP_MODEL_V0_1_START -->
## Current ownership-policy interpretation

This decision remains applicable to canonical database objects and hosting
environment preservation.

Its earlier whole-database content-parity wording is superseded for
production-operational tables. `communication_requests` now uses strict schema
parity but production-owned row content, which appears as informational drift in
the parity checker.

Canonical policy:
`config/deployment/database_ownership_policy_v0_1.json`.
<!-- FP_DEPLOYMENT_OWNERSHIP_MODEL_V0_1_END -->
