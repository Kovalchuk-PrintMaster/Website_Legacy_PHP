# ForPrint production operational data and database sync v0.1

**Status:** active
**Date:** 2026-08-07

## Purpose

Public forms create real production data, therefore the hosting database is not an unconditional content clone of local development.

## Ownership

Local-canonical tables:

```text
schema  = strict
content = strict
```

Production-operational tables:

```text
communication_requests
schema  = local-canonical / strict
content = production-owned / informational parity
```

## Normal database operations

Normal full reset and database-only sync preserve production operational tables in place. Local dumps omit them and the remote clear helper preserves them. All other database objects remain local-canonical.

Operational schema mismatch is blocking. Rollback restores the complete pre-mutation production database.

## Explicit destructive replacement

Only the separately named commands may replace production operational rows from local:

```text
make hosting-deploy-database-destructive
make hosting-deploy-full-destructive
```

These are high-risk maintenance operations and are not routine deployment commands.

## Parity

```text
canonical schema/content mismatch → FAIL
operational schema mismatch       → FAIL
operational row/content drift     → INFO
```

Raw whole-database hashes may differ while policy parity is healthy.

## Diagnostic hygiene

```text
make hosting-diagnostic-hygiene
make hosting-diagnostic-hygiene-clean
```

Only allowlisted `.forprint-...-check-<hex>.php` files older than ten minutes are eligible for cleanup.
