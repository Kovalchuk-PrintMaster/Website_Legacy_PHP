# ForPrint Hosting Deployment Profiles v0.1

**ID:** `FP-WEB-WORKFLOW-HOSTING-PROFILES-001`
**Version:** 0.1
**Date:** 2026-08-07
**Status:** active

## Purpose

ForPrint supports several explicit hosting deployment profiles so routine
frontend work does not require a full application/database mirror.

The deployment ownership policy is multi-owner: local is canonical for application files, database schema and non-operational database content; production is canonical for operational rows; hosting-specific environment/runtime state remains hosting-owned.

## Profiles

| Profile | Files | Database | Typical use |
|---|---|---|---|
| `full` | full application, vendor, all userfiles | policy-aware database sync; operational rows preserved | rebuild hosting under the ownership policy |
| `code` | frontend + backend + vendor/composer | unchanged | application code release without data |
| `frontend` | templates, CSS, JS, frontend-owned media | unchanged | responsive/mobile/UI work |
| `backend` | core, libraries, endpoint/index | unchanged | PHP application logic |
| `dependencies` | vendor + composer files | unchanged | dependency-only release |
| `database` | unchanged | policy-aware database sync; operational rows preserved | canonical data/schema refresh only |
| `media` | `userfiles/frontend/` only | unchanged | presentation-owned media |
| `manifest` | exact caller-supplied paths | unchanged | smallest point release |

## Canonical commands

```bash
make hosting-deploy-full
make hosting-deploy-code
make hosting-deploy-frontend
make hosting-deploy-backend
make hosting-deploy-dependencies
make hosting-deploy-database
make hosting-deploy-media
make hosting-deploy-manifest MANIFEST=config/deployment/<release>.manifest
```

Dry-run:

```bash
make hosting-deploy-frontend-dry-run
make hosting-deploy-code-dry-run
make hosting-deploy-backend-dry-run
make hosting-deploy-database-dry-run
make hosting-deploy-media-dry-run
make hosting-deploy-manifest-dry-run MANIFEST=...
```

## Frontend boundary

The frontend profile intentionally excludes:

```text
base/core/
base/libraries/
base/communication-request.php
base/vendor/
database
hosting environment files
```

It includes project presentation owners under `templates/default/` and
frontend-owned media under `userfiles/frontend/`.

This is the preferred mode for responsive/mobile work.

If a visual feature also requires controller/model changes, use `code`,
`backend`, or an exact `manifest` release instead of silently broadening the
frontend profile.

## Backend boundary

Backend deployment may update:

```text
core/
libraries/
communication-request.php
index.php
```

It does not update the database or production runtime configuration.

Because `communication-request.php` is a production communication boundary,
backend/code/full releases must pass the guarded non-sending production
communication readiness check before and after mutation.

## Database-only boundary

The normal database profile is policy-aware:

1. creates a consistent local dump of local-canonical database objects;
2. excludes production-operational tables from the normal local import payload;
3. records a complete production database backup before mutation;
4. verifies production-operational schema compatibility;
5. clears/imports local-canonical database objects while preserving operational rows;
6. compares strict canonical schema/content parity;
7. treats operational row/content drift as informational;
8. runs HTTP and non-sending communication acceptance;
9. restores the complete previous production database on acceptance failure.

Initial production-operational table:

```text
communication_requests
```

Its schema remains local-canonical and strict. Its row content is production-owned.

Physical database byte size, database name, and whole-database SHA equality are
not readiness requirements when operational row content differs by policy.

Explicit destructive operational replacement exists only through the separately
named `*-destructive` profiles and is not a routine deployment operation.

## Media profile

`media` is intentionally limited to frontend-presentation-owned media under:

```text
base/userfiles/frontend/
```

Database-managed images must not be moved or published independently when new
database references are required. In that case use database/full or an
explicit coordinated release.

## Exact manifest profile

Use `manifest` when only a few exact paths changed.

A dirty Git worktree is never interpreted automatically as deployment scope.
The manifest itself is the release contract.

## Hosting-owned state

Every profile preserves the hosting environment contract, including:

```text
.htaccess
.user.ini
config.php
mail.php
php.ini
error_log
.well-known/
cache/
env/
log/
logs/
sessions/
temp/
external communication runtime
```

Secrets are never copied from local or written into documentation/report output.

## Communication safety

Normal deployment validation must not send real Telegram or email messages.

Runtime readiness is checked with:

```text
scripts/inspection/check_website_communication_runtime.py
```

A runtime-ready result proves configuration/transport readiness, not successful
delivery of a real message. Real delivery tests remain explicit separate
operations.

## Existing commands

Existing commands remain valid:

```bash
make deploy
make deploy-dry-run
make hosting-reset-from-local
make hosting-parity-check
```

The new profile commands provide clearer intent and smaller release scopes.

<!-- FP_COMMUNICATION_RELEASE_SAFETY_DEPLOYMENT_PROFILE_V0_1_START -->
## Communication safety gate

Application file deployments run full non-sending communication acceptance before and after installation. Post-install failure remains rollback-eligible.

The full local-to-hosting reset performs the same gate after webroot/database installation and before final acceptance.

Standalone read-only command:

```text
make hosting-communication-check
```

Normal Makefile mutation targets use temporary authorization through `hosting_release_authorized.py`; do not manually leave `FP_DEPLOY_ALLOWED=1`.

See `docs/workflow/communication_release_safety_and_recovery_v0_1.md`.
<!-- FP_COMMUNICATION_RELEASE_SAFETY_DEPLOYMENT_PROFILE_V0_1_END -->

<!-- FP_OPERATIONAL_DB_DOCS_V0_1_START -->
## Operational database boundary

Normal `hosting-deploy-database` and `hosting-deploy-full` preserve production operational rows. The `*-destructive` variants explicitly replace them and are high-risk.
<!-- FP_OPERATIONAL_DB_DOCS_V0_1_END -->
