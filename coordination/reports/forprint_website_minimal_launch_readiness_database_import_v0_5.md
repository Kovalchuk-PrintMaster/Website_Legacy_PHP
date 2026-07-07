
ForPrint_Web_Site_Base — Minimal Launch Readiness and Database Import Plan v0.5 Report
Status

minimal_launch_readiness_database_import_v0_5_prepared

Completed
Documented temporary launch strategy for legacy PHP website.
Documented that this module is not being refactored into a new architecture now.
Documented SQL dump policy.
Added Git ignore patterns for SQL dumps, env files, local DB files, import/export folders and secrets folder.
Kept local config, runtime data, uploads and vendor outside Git.
Reconfirmed public launch remains blocked until webroot/admin/runtime/database checks are complete.
Important decision

The website is treated as a temporary public PHP website / landing channel.

The stronger future ForPrint website should be planned separately after the current site is minimally online.

Database decision

SQL dump may be provided later.

It must be imported only into local/staging/controlled deployment database and must not be committed.

Standards alignment

Reviewed directionally:

ForPrint Module Alignment Policy;
ForPrint Module Standards Awareness Protocol;
Secrets and Environment Policy;
Preferred module project structure standard.

Applied now:

gradual adoption;
no destructive rewrite;
secrets not committed;
SQL dumps ignored;
coordination status/report updated;
legacy deviation documented.

Deferred:

full Blueprint target project structure;
new website architecture;
deep integration with ForPrint core modules;
full auth/session/upload/SQL refactor.
Next recommended checkpoint

ForPrint_Web_Site_Base — Webroot Exposure Hardening v0.5.1
