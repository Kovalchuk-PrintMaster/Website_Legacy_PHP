
ForPrint Website — Current Status
Status

local_sql_import_smoke_run_v0_5_5_completed

Repository

/srv/software_development/forprint-project/forprint_website

Website base

base/

Current state
Repository scaffold is committed.
Legacy base inventory is committed.
Safe tracking policy is committed.
Config examples are committed.
Selected legacy PHP source checkpoint is committed.
Default hardcoded admin seed is neutralized.
Minimal launch readiness and database import plan is committed.
Minimal webroot exposure hardening is committed.
Staging runtime requirements are committed.
Local database import preparation is committed.
Local SQL dump intake is committed.
Local SQL import and smoke run v0.5.5 is completed.
SQL dumps and local env/database artifacts are ignored.
The module is treated as a temporary legacy PHP public website, not a full ForPrint rewrite.
Broad git add base/ remains forbidden.
Public launch remains blocked.
Runtime inspectors
scripts/inspection/check_website_staging_runtime.py
scripts/inspection/check_website_database_import_readiness.py
scripts/inspection/inspect_website_sql_dump.py
scripts/inspection/import_website_sql_dump_local.py
Database
SQL dump is local only and must not be committed.
Local dump path: database_dumps/im_21.05.25.sql.
Local DB name: forprint_website_legacy_local.
Local SQL import status: LOCAL_SQL_IMPORT_AND_SMOKE_OK.
Current blockers
Local website runtime smoke must be performed.
Public admin must be blocked/restricted.
Runtime directory permissions must be reviewed in deployment context.
HTTPS/server config required.
Upload/mail behavior must be controlled before public launch.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local Website Runtime Smoke v0.5.6
