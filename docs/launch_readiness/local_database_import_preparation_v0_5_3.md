# ForPrint Website — Local Database Import Preparation v0.5.3

## Status

`local_database_import_preparation_v0_5_3_prepared`

## Purpose

Prepare a safe process for importing the inherited website SQL dump into a local or staging database.

The SQL dump itself must not be committed to Git.

## Strategic position

The current PHP website is a temporary legacy public website / landing channel.

The database import is needed only to make the legacy site run.

This does not make the website the canonical ForPrint owner of:

```text id="xv3a81"
clients;
orders;
payments;
warehouse;
accounting;
pricing;
product semantics.

Those responsibilities remain outside this legacy website.

SQL dump policy

Allowed local paths:

database_dumps/
db_dumps/
dumps/
imports/

Allowed local filenames:

*.sql
*.sql.gz
*.dump
*.dump.gz

These paths/files are ignored by Git.

Safe import flow
1. Put SQL export into ignored local folder, for example:
   database_dumps/legacy_website_export.sql

2. Do not commit the dump.

3. Create local/staging DB manually.

4. Configure base/config.php locally from base/config.example.php.

5. Import the dump into local/staging DB.

6. Run:
   php -l checks / make check
   scripts/inspection/check_website_staging_runtime.py
   scripts/inspection/check_website_database_import_readiness.py

7. Open site locally/staging.

8. Restrict admin access before public exposure.
Example import commands

For plain SQL:

mysql -h <host> -u <user> -p <database_name> < database_dumps/<dump_file>.sql

For gzip SQL:

gzip -dc database_dumps/<dump_file>.sql.gz | mysql -h <host> -u <user> -p <database_name>
Sensitive data warning

The dump may contain:

admin users;
password hashes;
customer contacts;
forms/leads;
product/order data;
business-private content.

Therefore:

do not commit;
do not paste full dump into chat;
do not expose publicly;
use staging/local first;
change/restrict admin credentials before public launch.
Added inspector
scripts/inspection/check_website_database_import_readiness.py

It checks:

base/config.php exists locally and is ignored;
base/config.example.php exists;
mysql/mysqldump client visibility;
dump directories are ignored;
local dump discovery;
staged SQL/env/local DB safety.
Expected readiness status

Before import:

DATABASE_IMPORT_READY_WITH_WARNINGS

is acceptable if the only warning is that no dump exists yet.

After dump is placed locally, no SQL/env/local DB artifacts should be staged.

Next recommended checkpoint

ForPrint_Web_Site_Base — Local SQL Import and Smoke Run v0.5.4
