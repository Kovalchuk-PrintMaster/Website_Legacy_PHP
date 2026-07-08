# ForPrint Website — Local SQL Dump Intake v0.5.4

## Status

`local_sql_dump_intake_v0_5_4_prepared`

## Purpose

Prepare safe local intake of the inherited website SQL dump.

The dump is private runtime data and must not be committed to Git.

## Current expected local dump path

```text
database_dumps/im_21.05.25.sql
```                      
Safety rule

Do not keep SQL dumps inside base/ webroot.

Preferred:

database_dumps/

Avoid:

base/database_dumps/
Added inspector
scripts/inspection/inspect_website_sql_dump.py

It prints safe metadata only:

file size;
sha256;
line count;
CREATE TABLE count;
INSERT statement count;
database declarations;
table names;
secret-like keyword line numbers without row values.

It does not print table row data.

Next step after metadata inspection

If the dump looks valid, create/import into local or staging DB.

Public launch remains blocked until:

database import is validated;
admin is restricted;
runtime permissions are reviewed;
HTTPS/server config is prepared;
mail/upload behavior is controlled.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local SQL Import and Smoke Run v0.5.5
