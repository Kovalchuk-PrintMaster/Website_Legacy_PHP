
ForPrint_Web_Site_Base — Safe Tracking Policy and Config Split v0.4.2 Report
Status

safe_tracking_policy_v0_4_2_complete

Completed

Created:

docs/launch_readiness/safe_tracking_policy_v0_4_2.md

Created:

coordination/reports/forprint_website_safe_tracking_policy_v0_4_2.md

Updated:

.gitignore
Policy result

The inherited PHP base/ directory is split into tracking categories:

candidate source files;
local config / secret-bearing files;
runtime logs;
temporary/generated files;
runtime uploads/media;
vendor dependencies.
Important decision

Broad tracking remains forbidden:

git add base/

must not be used.

Ignored by policy

The following are ignored or kept local-only:

base/config.php
base/config.local.php
base/mail.local.php
base/log/
base/temp/
base/userfiles/
base/vendor/
base/composer.phar
base/sitemap.xml
Candidate source paths for later selected tracking

These may be considered later, after config split and review:

base/index.php
base/.htaccess
base/core/
base/libraries/
base/templates/
base/composer.json
base/composer.lock
base/mail.example.php
Public launch status

Public launch remains blocked.

Next recommended prompt

ForPrint_Web_Site_Base — Config Example and Secret Scan v0.4.3
