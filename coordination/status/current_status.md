
ForPrint Website — Current Status
Status

webroot_exposure_hardening_v0_5_1_prepared

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
SQL dumps and local env/database artifacts are ignored.
Minimal webroot exposure hardening v0.5.1 is prepared.
The module is treated as a temporary legacy PHP public website, not a full ForPrint rewrite.
Broad git add base/ remains forbidden.
Public launch remains blocked.
Webroot hardening

base/.htaccess now blocks direct HTTP access to:

config.php / config.local.php / mail.local.php
core/
vendor/
log/
temp/
.env*
SQL/dump/local DB/backup artifacts
Database
SQL dump is not committed.
SQL dump may be provided later for local/staging import.
SQL dumps must stay ignored and out of Git.
Current blockers
Public admin must be blocked/restricted.
Runtime directory permissions must be reviewed.
Database import must be validated.
HTTPS/server config required.
Upload/mail behavior must be controlled before public launch.
Next recommended checkpoint

ForPrint_Web_Site_Base — Staging Runtime Requirements v0.5.2
