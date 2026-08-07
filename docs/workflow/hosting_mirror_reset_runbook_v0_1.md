# ForPrint Hosting Mirror Reset Runbook v0.1

**ID:** `FP-WEB-WORKFLOW-HOSTING-MIRROR-001`
**Version:** 0.1
**Date:** 2026-08-07
**Status:** active
**Scope:** current pre-publication local-to-hosting mirror workflow

## 1. Purpose

This is the canonical operator reference for rebuilding the public hosting copy from the local ForPrint Website source of truth.

Current model:

```text
local canonical website
        |
        | controlled full application/data mirror
        v
hosting disposable/public mirror
```

Hosting is not a second editable application source during this stage.

## 2. Mirrored state

The mirror includes:

- application PHP code;
- templates;
- project-owned CSS and JavaScript;
- `vendor/`;
- `userfiles/`;
- complete logical database contents.

`userfiles/` is application-managed media/content referenced by website records and belongs to the mirror.

Local and hosting database names may differ. Physical database byte size may also differ after import.

## 3. Hosting-owned environment pack — preserve, do not mirror

The maintenance workflow intentionally preserves hosting-owned runtime/configuration.

Exact webroot files:

```text
.htaccess
.user.ini
config.php
mail.php
php.ini
error_log
```

Preserved runtime/environment prefixes:

```text
.well-known/
cache/
env/
log/
logs/
sessions/
temp/
```

An external communication runtime is also preserved outside the ordinary payload. Its actual location is deployment-configured and must not be copied into Git or documentation as a secret-bearing absolute path.

Why this matters:

- `config.php` owns production database credentials/settings;
- `.htaccess`, `.user.ini` and `php.ini` may own hosting-specific web/PHP behavior;
- the external communication runtime may contain configuration/secrets required for Telegram, email and public form delivery;
- overwriting these paths with local development versions can break forms, change database connectivity or expose/replace secrets.

This preservation is a deployment contract, not an accidental exception.

## 4. Database parity contract

### Exact logical content parity

For each database object:

- object set must match;
- row count must match;
- exact row content hash must match.

### Normalized logical schema parity

Local and hosting may use different compatible MariaDB/MySQL versions. Blocking schema parity therefore compares logical semantics, not raw server presentation.

Blocking fields:

```text
column name/order
normalized SQL type
NULL/NOT NULL
key role
normalized default
normalized extra/auto-increment/generated semantics
```

Known non-blocking representation noise:

- collation label alone;
- legacy integer display widths such as `int(11)` versus `int`;
- `integer` versus `int`;
- `CURRENT_TIMESTAMP` versus `current_timestamp()`;
- MySQL `DEFAULT_GENERATED`;
- database name;
- physical database byte size;
- server version string.

Schema validation is not disabled. True type/nullability/key/default/extra drift remains blocking.

## 5. 2026-08-07 incident

Before reset:

```text
                         local    hosting
goods row count          164      94
goods max id             294      221
contacts_schedule bytes  283      0
```

The hosting database was stale.

During reset:

- the local database dump imported successfully;
- production HTTP acceptance passed with the current schedule/catalog;
- production `goods` reached 164 rows;
- `goods` content hash matched local exactly;
- `settings` content hash matched local exactly.

The reset then failed because old fingerprinting hashed raw `SHOW FULL COLUMNS` metadata. Compatible database environments represented schema metadata differently, so `schema_sha256` differed although logical data matched.

The existing safety rollback correctly restored the previous webroot and old database.

Root cause:

> import succeeded; cross-environment schema acceptance was too strict at the raw metadata representation layer.

Corrective policy:

> exact logical data + normalized logical schema; preserve rollback and hosting environment boundaries.

## 6. Controlled reset sequence

1. validate local repository/runtime;
2. validate local mirror manifest;
3. create local database dump;
4. fingerprint local logical database;
5. run local HTTP acceptance;
6. record hosting environment pack;
7. prepare remote release workspace;
8. back up remote webroot and database;
9. mirror payload while preserving hosting-owned paths;
10. clear/import hosting database from local dump;
11. run production HTTP acceptance;
12. compare normalized logical database parity;
13. compare file manifest parity;
14. confirm hosting environment pack unchanged;
15. keep the new mirror only when all checks pass.

If acceptance fails after mutation, restore both webroot and database.

## 7. Communication safety

A mirror reset must not silently replace production communication configuration.

- Telegram/email configuration remains hosting-owned.
- Diagnostics must not print passwords/tokens/secret runtime contents.
- The communication runtime remains outside the ordinary mirrored payload.
- A mirror reset must not send real Telegram/email messages as a side effect.
- `communication-request.php` may legitimately return HTTP 405 to a GET acceptance request.

## 8. Operator commands

From repository root:

```bash
make hosting-reset-from-local
make hosting-parity-check
```

`hosting-reset-from-local` is controlled mutation. `hosting-parity-check` is read-only.

Persistent reset authorization remains disabled outside the guarded process-local invocation.

## 9. Expected acceptance

After a successful mirror:

- current products and settings appear on hosting;
- contacts page renders current schedule;
- public routes return expected statuses;
- managed assets/media are available;
- exact DB content parity passes;
- normalized logical schema parity passes;
- file manifest parity passes;
- hosting environment pack is unchanged;
- external communication runtime is preserved.

## 10. Troubleshooting order

When hosting lacks recent database-backed data:

1. compare direct DB facts first, e.g. `goods` count/max id and target settings field state;
2. check whether a reset imported successfully and then rolled back;
3. compare exact table content hashes;
4. inspect HTTP acceptance;
5. only after data parity is proven investigate PHP runtime/cache/profile/template issues.

If several unrelated DB-backed features are stale simultaneously, do not start with CSS/browser cache/OPcache.

## 11. Prohibited shortcuts

Do not:

- copy local `config.php` over hosting;
- copy local secret/runtime configuration over hosting;
- disable schema validation entirely;
- disable rollback to force a failed reset to remain live;
- require equal physical database byte size;
- require byte-identical raw `SHOW FULL COLUMNS` output across engines;
- manually edit production as the normal synchronization mechanism;
- print secrets into logs/docs/patch output.

## 12. Ownership

Implementation:

```text
scripts/maintenance/reset_hosting_from_local.py
scripts/inspection/check_hosting_mirror_parity.py
```

Documentation:

```text
docs/workflow/hosting_mirror_reset_runbook_v0_1.md
docs/decisions/2026-08-07__hosting_mirror_logical_database_parity_and_environment_preservation.md
coordination/reports/2026-08-07_hosting_mirror_database_parity_incident_v0_1.md
```

<!-- FP_HOSTING_MIRROR_OPERATOR_REPORTING_V0_1 -->
## Operator reporting

Normal `make hosting-reset-from-local` and `make hosting-parity-check` output is
summary-first. Component-specific guards remain internal diagnostics and are
not the primary success vocabulary.

Canonical categories are payload, database, HTTP/content acceptance, managed
assets, hosting environment/communication-runtime preservation, safety and
overall readiness.

Detailed output is retained under:

```text
tmp/hosting-mirror-operator/<timestamp>-<operation>/
```

Verbose mode:

```bash
FP_HOSTING_MIRROR_VERBOSE=1 make hosting-reset-from-local
FP_HOSTING_MIRROR_VERBOSE=1 make hosting-parity-check
```

See `docs/workflow/hosting_mirror_operator_reporting_v0_1.md`.

<!-- FP_DEPLOYMENT_OWNERSHIP_MODEL_V0_1_START -->
## Current database ownership policy

Normal reset is policy-aware rather than an unconditional whole-database clone.

```text
local canonical:
  application files
  database schema
  non-operational database content

production canonical:
  production-operational row content
  initial table: communication_requests

hosting canonical:
  protected runtime/environment state
```

Operational schema mismatch is blocking. Operational row/content drift is
informational. Normal reset preserves operational rows; explicit destructive
replacement requires a separately named high-risk operation.
<!-- FP_DEPLOYMENT_OWNERSHIP_MODEL_V0_1_END -->
