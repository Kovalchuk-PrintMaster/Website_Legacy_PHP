
ForPrint Website — Current Status
Status

local_database_import_preparation_v0_5_3_prepared

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
Local database import preparation v0.5.3 is prepared.
SQL dumps and local env/database artifacts are ignored.
The module is treated as a temporary legacy PHP public website, not a full ForPrint rewrite.
Broad git add base/ remains forbidden.
Public launch remains blocked.
Runtime inspectors
scripts/inspection/check_website_staging_runtime.py
scripts/inspection/check_website_database_import_readiness.py
Database
SQL dump is not committed.
SQL dump may be provided later for local/staging import.
SQL dumps must stay ignored and out of Git.
Current blockers
SQL dump must be provided locally for import.
Public admin must be blocked/restricted.
Runtime directory permissions must be reviewed in deployment context.
Database import must be validated.
HTTPS/server config required.
Upload/mail behavior must be controlled before public launch.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local SQL Import and Smoke Run v0.5.4
