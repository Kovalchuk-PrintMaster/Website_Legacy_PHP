# ForPrint_Web_Site_Base — Line Ending Normalization and Admin Seed Neutralization v0.4.4a

## Status

`line_endings_admin_seed_v0_4_4a_prepared`

## Purpose

Prepare the inherited PHP website source for a controlled selected source checkpoint.

## Completed

### 1. Line ending normalization

Selected source/text files were normalized from CRLF/CR to LF.

Normalized files count:

```text
176

Scope:

base/.htaccess
base/composer.json
base/composer.lock
base/index.php
base/core/
base/libraries/
base/templates/

Binary files were not modified by this normalization step.

2. Default admin seed neutralization

Hardcoded default admin creation was neutralized in:

base/core/admin/models/UserModel.php

The inherited automatic seed with hardcoded admin credentials is no longer acceptable for selected source checkpoint.

Admin bootstrap must be performed through a controlled local maintenance step with local-only credentials.

Safety boundary
No deployment was performed.
No production DB was connected.
No production SMTP was connected.
Public admin remains blocked.
Public launch remains blocked.
Broad git add base/ remains forbidden.
Required checks
php -l base/core/admin/models/UserModel.php
make check
git diff --check
Next recommended step

ForPrint_Web_Site_Base — Selected Base Source Checkpoint v0.4.4b

Only after checks pass.
