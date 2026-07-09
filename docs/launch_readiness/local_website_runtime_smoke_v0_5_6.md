# ForPrint Website — Local Website Runtime Smoke v0.5.6

## Status

`local_website_runtime_smoke_v0_5_6_completed`

## Purpose

Validate that the local legacy PHP website configuration can connect to the local imported database.

This is a local/staging smoke checkpoint only.

It is not production deployment.

## Local database

```text
forprint_website_legacy_local
Smoke script
scripts/inspection/check_website_local_runtime_smoke.py
Completed checks
HOST defined
USER defined
PASSWORD defined
DB_NAME defined
DB host is local
DB_NAME matches expected local DB
DB connection established
DB charset set to utf8mb4
table_count: 23
Required tables checked
settings
catalog
goods
user
orders
orders_goods
Minimal count queries checked
settings = 1
catalog = 5
goods = 33
user = 1
orders = 35
Smoke result
LOCAL_WEBSITE_RUNTIME_SMOKE_OK
Safety behavior

The smoke check:

does not print DB password;
does not print full config values;
does not print row data;
requires DB host to be local;
requires DB_NAME to match expected local DB;
prints only table existence and count-query results.
Local config note

base/config.php was updated locally to point to the local imported database.

This file is ignored and must not be committed.

A local backup may exist:

base/config.php.before_local_runtime_smoke.bak

This backup is ignored and must not be committed.

Current launch position

The local database runtime layer is ready for local HTTP smoke.

Public launch remains blocked.

Still blocked before public launch
public admin exposure;
runtime permissions in deployment context;
HTTPS/server config;
mail behavior;
upload behavior;
legacy auth/session/SQL risks.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local HTTP Smoke v0.5.7
