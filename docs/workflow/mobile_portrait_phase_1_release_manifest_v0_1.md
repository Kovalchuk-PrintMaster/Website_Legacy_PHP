# Mobile portrait phase 1 exact release manifest v0.1

**ID:** `FP-WEB-WORKFLOW-MOBILE-PORTRAIT-RELEASE-001`
**Date:** 2026-08-06
**Status:** active release scope
**Machine-readable owner:**
`config/deployment/mobile_portrait_phase_1_v0_1.manifest`

## Purpose

This release publishes only the accepted mobile portrait phase 1 frontend
change. The machine-readable manifest is the sole payload owner. The
deployment tool must not infer the release from the whole `base/` directory or
from the complete dirty working tree.

Manifest paths are relative to:

```text
base/
```

## Exact release files

```text
core/base/settings/internal_settings.php
core/user/controllers/IndexController.php
templates/default/assets/css/forprint-home.css
templates/default/assets/css/forprint-layout.css
templates/default/assets/css/forprint-product-cards.css
templates/default/assets/css/forprint-shell.css
templates/default/assets/js/forprint-mobile-portrait.js
templates/default/include/header.php
```

The manifest contains exactly eight files. Missing files, duplicate paths,
symlinks, absolute paths, parent traversal, protected paths, and unlisted files
must fail closed.

## Explicit exclusions

This mobile release does not include:

```text
communication-request.php
templates/default/assets/js/forprint-product-communication.js
templates/default/assets/css/forprint-product-communication.css
templates/default/include/productCommunicationButtons.php
config.php
mail.php
vendor/
userfiles/
database data or schema
```

The production `communication-request.php` currently owns a production-only
runtime loader for:

```text
/var/www/825163-nikolay.k/data/.forprint-secrets/communication_runtime.php
```

The local endpoint does not own that loader and must not replace the production
endpoint as part of this mobile-only release.

## Validation sequence

```bash
make deploy-check
make communication-check
make deploy-dry-run
```

`make deploy-check` validates the exact eight-file scope, local routes, PHP
syntax, SSH access, and remote prerequisites without upload.

`make communication-check` performs a guarded, non-sending production web-SAPI
check. It may create one randomly named temporary diagnostic file, access it
through HTTPS with a one-time header capability, and remove it in `finally`.
It performs no form POST, email delivery, Telegram delivery, or database
mutation.

`make deploy-dry-run` builds a SHA-256 payload and report locally. The report
must show:

```text
file_count=8
scope_manifest=config/deployment/mobile_portrait_phase_1_v0_1.manifest
```

## Live deployment acceptance

Live deployment remains disabled until the operator reviews the dry-run and
sets this ignored runtime value intentionally:

```text
FP_DEPLOY_ALLOWED=1
```

Then:

```bash
make deploy
```

The deployment tool performs a non-sending communication check before upload
and again after installation. A post-install communication failure causes
automatic rollback, followed by a non-sending post-rollback check.

Normal deployment never sends test email or Telegram messages. A delivery
smoke test remains a separate, explicitly authorized workflow.

## Rollback boundary

Only the exact manifest targets are backed up, installed, verified, and
restored. Production configuration, private runtime configuration, uploads,
vendor dependencies, and database state remain production-owned.
