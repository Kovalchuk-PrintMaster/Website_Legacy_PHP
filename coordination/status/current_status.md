
ForPrint Website — Current Status
Status

staging_runtime_requirements_v0_5_2_prepared

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
Staging runtime requirements v0.5.2 are prepared.
SQL dumps and local env/database artifacts are ignored.
The module is treated as a temporary legacy PHP public website, not a full ForPrint rewrite.
Broad git add base/ remains forbidden.
Public launch remains blocked.
Runtime inspector
scripts/inspection/check_website_staging_runtime.py
Database
SQL dump is not committed.
SQL dump may be provided later for local/staging import.
SQL dumps must stay ignored and out of Git.
Current blockers
Public admin must be blocked/restricted.
Runtime directory permissions must be reviewed in deployment context.
Database import must be validated.
HTTPS/server config required.
Upload/mail behavior must be controlled before public launch.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local Database Import Preparation v0.5.3
