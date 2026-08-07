# Hosting Mirror Database Parity Incident — 2026-08-07

**ID:** `FP-WEB-REPORT-HOSTING-MIRROR-2026-08-07-001`
**Status:** completed evidence
**Date:** 2026-08-07

## Observed stale hosting state

```text
                         local    hosting before reset
goods row count          164      94
goods max id             294      221
contacts_schedule bytes  283      0
```

The local schedule value was valid JSON with three weekly rows.

## Reset evidence

The controlled reset successfully:

- built the local snapshot;
- dumped local database;
- backed up hosting;
- installed the mirror payload;
- imported local database;
- passed production HTTP acceptance.

After import:

- production `goods` row count was 164;
- `goods` exact content hash matched local;
- `settings` exact content hash matched local;
- production contacts/catalog HTTP responses reflected current local data.

## Failure and rollback

Numerous table `schema_sha256` values still differed while corresponding `content_sha256` values matched.

Old fingerprinting included raw `SHOW FULL COLUMNS` metadata, including collation/type/default/extra presentation. The reset classified this as database mismatch and invoked the existing combined rollback:

```text
[FAIL] acceptance failed; restoring webroot and DB
REMOTE_ROLLBACK=OK
```

Hosting therefore returned to the previous stale database.

## Root cause

Database import was successful.

Acceptance was stricter than the intended cross-environment mirror contract: server-specific schema presentation was treated as exact identity across compatible but different MariaDB/MySQL environments.

Local evidence included MariaDB 10.11 on Debian 12 with legacy `utf8mb3_general_ci` column metadata. Hosting is a separate managed database environment.

## Corrective action

- retain exact object/row/content parity;
- normalize logical schema metadata before hashing;
- ignore only documented representation noise such as collation labels, integer display widths and `DEFAULT_GENERATED`;
- retain true type/nullability/key/default/extra validation;
- retain combined rollback;
- retain hosting environment/communication runtime preservation;
- remove ambiguous `database=` from generated `[client]` files because the database name is already passed explicitly to DB CLI commands.

## Safety boundary

No change authorizes overwriting hosting `config.php` or communication secrets/runtime. No change disables rollback.
