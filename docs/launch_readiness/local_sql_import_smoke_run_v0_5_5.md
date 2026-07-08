# ForPrint Website — Local SQL Import and Smoke Run v0.5.5

## Status

`local_sql_import_smoke_run_v0_5_5_completed`

## Purpose

Import the ignored legacy website SQL dump into a local database and run a safe smoke check.

This checkpoint is local/staging only.

It is not production deployment.

## Local dump

```text
database_dumps/im_21.05.25.sql
Local database
forprint_website_legacy_local
Import helper
scripts/inspection/import_website_sql_dump_local.py
Executed checks

Dry run:

LOCAL_SQL_IMPORT_DRY_RUN_OK

Import and smoke run:

LOCAL_SQL_IMPORT_AND_SMOKE_OK
Smoke result
table_count: 23

Detected table counts:

advantages: 8
articles: 3
blocked_access: 0
catalog: 5
delivery: 3
filters: 23
filters_categories: 3
goods: 33
goods_filters: 14
information: 3
news: 3
old_alias: 14
orders: 35
orders_goods: 44
orders_statuses: 2
parsing_data: 1
payments: 2
relationship: 4
sales: 4
settings: 1
socials: 2
user: 1
visitors: 2
Safety notes

The smoke run prints only table counts.

It does not print row data.

The SQL dump remains ignored and must not be committed.

The local config remains ignored and must not be committed.

Current interpretation

The database dump is structurally usable for local/staging runtime checks.

The presence of tables such as orders, orders_goods, user and visitors confirms the dump must be treated as private data.

Still blocked before public launch
public admin exposure;
runtime permissions in deployment context;
HTTPS/server config;
mail behavior;
upload behavior;
legacy auth/session/SQL risks.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local Website Runtime Smoke v0.5.6
