# ForPrint_Web_Site_Base — Selected Base Source Checkpoint v0.4.4b

## Status

`selected_base_source_checkpoint_v0_4_4b_prepared`

## Purpose

Create the first controlled source checkpoint for the inherited PHP website base.

## Scope selected for tracking

```text
base/.htaccess
base/composer.json
base/composer.lock
base/index.php
base/core/
base/libraries/
base/templates/
Explicitly excluded
base/config.php
base/config.local.php
base/mail.local.php
base/log/
base/temp/
base/userfiles/
base/vendor/
base/composer.phar
base/sitemap.xml
Safety notes
Broad git add base/ remains forbidden.
Local config remains ignored.
Runtime logs remain ignored.
Runtime uploads/media remain ignored.
Vendor dependencies remain ignored.
Public launch remains blocked.
Public admin remains blocked.
Known inherited risks retained for later hardening

The selected source checkpoint still contains inherited legacy risks that are intentionally documented and not solved in this checkpoint:

legacy md5 password handling;
direct $_GET, $_POST, $_COOKIE, $_FILES usage;
dynamic SQL construction;
upload handling through move_uploaded_file;
web-root exposure hardening still required;
admin auth/session hardening still required.
Completed before this checkpoint
SMTP config example/local split.
DB config example created.
Hardcoded default admin seed neutralized.
.gitattributes added.
LF normalization and trailing whitespace cleanup prepared for selected text source.
Required checks
make check
git diff --cached --check
git diff --check
staged sensitive scan
Next recommended step

ForPrint_Web_Site_Base — Webroot Exposure Hardening v0.5
