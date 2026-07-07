
ForPrint_Web_Site_Base — Webroot Exposure Hardening v0.5.1 Report
Status

webroot_exposure_hardening_v0_5_1_prepared

Completed
Added minimal webroot hardening block to base/.htaccess.
Blocked direct access to local/private config files.
Blocked direct access to internal/runtime/dependency directories.
Blocked direct access to .env, SQL dumps, local database files and backup artifacts.
Documented that admin remains blocked for public launch.
Documented that Nginx requires equivalent server-level rules.
Safety boundary
No PHP refactor was performed.
No database import was performed.
No production deployment was performed.
No production credentials were added.
No SQL dump was committed.
Current launch position

The site is closer to staging readiness, but public launch remains blocked until:

admin access is restricted;
runtime permissions are reviewed;
database import is validated;
HTTPS/server config is prepared;
upload/mail behavior is controlled.
Next recommended checkpoint

ForPrint_Web_Site_Base — Staging Runtime Requirements v0.5.2
