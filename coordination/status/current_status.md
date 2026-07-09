
ForPrint Website — Current Status
Status

local_website_runtime_smoke_v0_5_6_completed

Repository

/srv/software_development/forprint-project/forprint_website

Remote

git@github.com:Kovalchuk-PrintMaster/Website_Legacy_PHP.git

Website base

base/

Current state
Repository scaffold is committed and pushed.
Legacy base inventory is committed and pushed.
Safe tracking policy is committed and pushed.
Config examples are committed and pushed.
Selected legacy PHP source checkpoint is committed and pushed.
Default hardcoded admin seed is neutralized.
Minimal launch readiness and database import plan is committed and pushed.
Minimal webroot exposure hardening is committed and pushed.
Staging runtime requirements are committed and pushed.
Local database import preparation is committed and pushed.
Local SQL dump intake is committed and pushed.
Local SQL import and smoke run is committed and pushed.
Legacy webroot naming policy is committed and pushed.
Local website runtime smoke v0.5.6 is completed.
SQL dumps and local env/database artifacts are ignored.
The module is treated as a temporary legacy PHP public website, not a full ForPrint rewrite.
base/ remains the intentional legacy PHP webroot.
Public launch remains blocked.
Runtime inspectors
scripts/inspection/check_website_staging_runtime.py
scripts/inspection/check_website_database_import_readiness.py
scripts/inspection/inspect_website_sql_dump.py
scripts/inspection/import_website_sql_dump_local.py
scripts/inspection/check_website_local_runtime_smoke.py
Database
SQL dump is local only and must not be committed.
Local dump path: database_dumps/im_21.05.25.sql.
Local DB name: forprint_website_legacy_local.
Local SQL import status: LOCAL_SQL_IMPORT_AND_SMOKE_OK.
Local runtime smoke status: LOCAL_WEBSITE_RUNTIME_SMOKE_OK.
Current blockers
Local HTTP smoke must be performed.
Public admin must be blocked/restricted.
Runtime directory permissions must be reviewed in deployment context.
HTTPS/server config required.
Upload/mail behavior must be controlled before public launch.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local HTTP Smoke v0.5.7

Local Python environment
Local venv: .venv_website/.
Website Python tooling should run from .venv_website, not Blueprint venv.
Local Python environment status: WEBSITE_LOCAL_PYTHON_ENV_OK.
