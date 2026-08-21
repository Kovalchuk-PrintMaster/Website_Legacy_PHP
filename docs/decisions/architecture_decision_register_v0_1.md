# Реєстр рішень v0.1

**ID:** `FP-WEB-ADR-REGISTER-001`
**Дата:** 2026-07-16

| ID | Рішення | Статус |
|---|---|---|
| `FP-WEB-ADR-001` | `base/` залишається webroot | accepted |
| `FP-WEB-ADR-002` | Safe publication до фундаментального rewrite | accepted |
| `FP-WEB-ADR-003` | Stable legacy зберігається; проблемний block можна замінити цілісно | accepted |
| `FP-WEB-ADR-004` | Нові frontend components мають isolated prefixed CSS/JS | accepted |
| `FP-WEB-ADR-005` | Server є source of truth для validation | accepted |
| `FP-WEB-ADR-006` | Communication request — standalone endpoint | accepted |
| `FP-WEB-ADR-007` | Old `ValidationHelper` не видаляти без migration consumers | accepted |
| `FP-WEB-ADR-008` | International phones через `libphonenumber-for-php-lite` | accepted, pending |
| `FP-WEB-ADR-009` | Unusual phone — soft warning + second submit | accepted, pending |
| `FP-WEB-ADR-010` | `tmp/work/tmp.php`/`tmp/work/tmp.py` — scratch entrypoints | accepted |
| `FP-WEB-ADR-011` | Snapshots і plans версіюються окремими files | accepted |
| `FP-WEB-ADR-012` | Reports — evidence, docs — explanation | accepted |
| `FP-WEB-ADR-013` | Cards use cover-crop; gallery preserves fuller image | accepted |
| `FP-WEB-ADR-014` | Secrets не потрапляють у docs/patch output/Git | accepted |
| `FP-WEB-ADR-015` | Block завершується checks, visual, explicit staging, commit | accepted |
| `FP-WEB-ADR-016` | Communication success modal auto-close ≈ 1 second | accepted, pending |

## Phone classification

1. valid — normalize/send;
2. unusual — warn and allow second submit;
3. malformed — block.

Точна поведінка закріплюється targeted tests.

<!-- FP_DUAL_TRACK_DECISION_REGISTER_V0_1 -->
## Frontend dual-track strategy

- Date: 2026-07-18
- Status: superseded
- Decision: prepare the inherited frontend for practical publication while developing a separate modern preview with project-owned HTML, CSS and JavaScript.
- Record: `docs/decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md`
- Superseded by: `docs/decisions/2026-08-08__progressive_in_place_frontend_modernization.md`

<!-- FP-FRONTEND-DOCS-V02-START -->
## FP-WEB-ADR-2026-07-20-001

**Decision:** canonical frontend CSS ownership and homepage layout
**Status:** accepted working architecture decision
**Record:** `docs/decisions/2026-07-20__canonical_frontend_css_ownership_and_homepage_layout.md`
<!-- FP-FRONTEND-DOCS-V02-END -->

<!-- FP-PRODUCTION-RELEASE-ADR-V0-1-START -->
## FP-WEB-ADR-2026-07-30-001

**Decision:** s01 source of truth and controlled production mirror
**Status:** accepted
**Record:** `docs/decisions/2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md`

Normal publication requires a reviewed local commit, exact release archive,
production baseline verification, private backup, manifest-scoped deployment,
production validation and rollback on failure.
<!-- FP-PRODUCTION-RELEASE-ADR-V0-1-END -->

<!-- FP-GOOGLE-ADS-ADR-V0-1-START -->
## FP-ADS-ADR-2026-08-01-001

**Decision:** version normalized Google Ads research and gate all advertising spend
**Status:** accepted working decision
**Record:** `2026-08-01__google_ads_research_workspace_and_launch_gate.md`
<!-- FP-GOOGLE-ADS-ADR-V0-1-END -->

<!-- FP_ADR_REGISTER_2026_08_06_FRONTEND_CHECKPOINT_START -->
## Frontend foundation checkpoint — 2026-08-06

| ID | Рішення | Статус |
|---|---|---|
| `FP-WEB-ADR-2026-08-06-001` | Exact frontend/runtime checkpoint with separate SEO/Ads and scratch scope | accepted |

Canonical decision file:

```text
docs/decisions/2026-08-06__frontend-foundation-stable-checkpoint-and-next-stage.md
```
<!-- FP_ADR_REGISTER_2026_08_06_FRONTEND_CHECKPOINT_END -->

<!-- FP-EXACT-MANIFEST-COMMUNICATION-CHECK-V0-1-START -->
## FP-WEB-ADR-018 — exact release manifests and non-sending communication gates

**Status:** accepted
**Date:** 2026-08-06

Application releases must use an explicit machine-readable path manifest.
A dirty working tree is never itself a release scope. The current mobile
portrait phase 1 manifest contains exactly eight paths relative to `base/`.

Normal deployment must perform guarded, non-sending production communication
runtime checks before upload and after installation. The post-install check is
part of acceptance; failure triggers rollback. Normal deployment must never
send test email or Telegram messages.

Production-only communication runtime configuration remains outside the public
webroot and outside the deployment payload. The production communication
endpoint must not be replaced by a local variant that lacks the accepted
runtime loader.

References:

```text
config/deployment/mobile_portrait_phase_1_v0_1.manifest
docs/workflow/mobile_portrait_phase_1_release_manifest_v0_1.md
scripts/inspection/check_website_communication_runtime.py
scripts/maintenance/deploy_website_to_hosting.py
```
<!-- FP-EXACT-MANIFEST-COMMUNICATION-CHECK-V0-1-END -->

<!-- FP-HOSTING-LOCAL-MIRROR-V0-1-START -->
## FP-WEB-ADR-LOCAL-MIRROR-001

**Decision:** local server is the source of truth and hosting is a disposable
public mirror during the pre-launch test period.

**Status:** superseded by the deployment ownership-policy model

**Record:**
`docs/decisions/2026-08-06__local_source_of_truth_and_disposable_hosting_mirror.md`

**Operational command:** `make hosting-reset-from-local`
<!-- FP-HOSTING-LOCAL-MIRROR-V0-1-END -->

<!-- FP_HOSTING_MIRROR_DECISION_REGISTER_V0_1 -->
## FP-WEB-ADR-2026-08-07-001

**Decision:** hosting parity follows the deployment ownership policy: canonical database objects use strict logical parity, production-operational row content remains production-owned, and hosting runtime/environment state remains hosting-owned and preserved.
**Status:** accepted
**Record:** `docs/decisions/2026-08-07__hosting_mirror_logical_database_parity_and_environment_preservation.md`

<!-- FP_HOSTING_DEPLOYMENT_PROFILES_V0_1 -->
## FP-WEB-ADR-2026-08-07-002

**Decision:** explicit hosting deployment profiles with independent file/database scope boundaries.
**Status:** accepted
**Record:** `docs/decisions/2026-08-07__hosting_deployment_profiles_and_scope_boundaries.md`

<!-- FP_COMMUNICATION_RELEASE_SAFETY_ADR_V0_1_START -->
## FP-WEB-ADR-2026-08-07-003

**Decision:** production communication forms use one protected runtime contract through `CommunicationRuntimeBootstrap.php`; security prerequisites, canonical boolean semantics and CSRF issuer/verifier parity are mandatory non-sending deployment acceptance gates.

**Status:** accepted

**Record:** `docs/decisions/2026-08-07__communication_runtime_contract_and_deployment_acceptance.md`
<!-- FP_COMMUNICATION_RELEASE_SAFETY_ADR_V0_1_END -->

<!-- FP_OPERATIONAL_DB_ADR_V0_1_START -->
## FP-WEB-ADR-2026-08-07-004

**Decision:** `communication_requests` content is production-owned; schema remains strict local-canonical.

**Status:** accepted

**Record:** `docs/decisions/2026-08-07__production_operational_database_ownership.md`
<!-- FP_OPERATIONAL_DB_ADR_V0_1_END -->


## FP-WEB-ADR-2026-08-08-005

**Decision:** progressive in-place frontend modernization
**Status:** accepted
**Record:** `docs/decisions/2026-08-08__progressive_in_place_frontend_modernization.md`

<!-- FP_MARKETING_CONTROL_PLANE_ADR_V0_1_START -->
## FP-WEB-ADR-2026-08-11-001

**Decision:** canonical marketing control plane and API-first automation

**Status:** accepted

**Record:** `docs/decisions/2026-08-11__marketing_control_plane_and_api_first_automation.md`

`marketing/` is the canonical promotion/measurement workspace; SEO becomes an
organic-search subdomain. `config/marketing/` is the machine-readable control
plane, `scripts/marketing/` owns Python automation, and provider mutations use
plan/preview/authorize/apply/verify/evidence controls.
<!-- FP_MARKETING_CONTROL_PLANE_ADR_V0_1_END -->

<!-- FP_MARKETING_CONTROL_PLANE_SCHEMA_V02_ADR_START -->
## FP-WEB-ADR-2026-08-12-001

**Decision:** separate provider integrations, analytical data sources, official
reference provenance and website measurement event/privacy contracts inside the
existing marketing control plane.

**Status:** accepted

**Record:** `docs/decisions/2026-08-12__marketing_control_plane_contract_split_and_schema_v0_2.md`

**Architecture:** `docs/marketing/architecture/marketing_repository_architecture_v0_2.md`

**Schema profile:** `config/marketing/schemas/marketing_control_plane_v0_2.schema.json`
<!-- FP_MARKETING_CONTROL_PLANE_SCHEMA_V02_ADR_END -->

<!-- FP_MOBILE_PRESENTATION_ADR_V0_1_START -->
## FP-WEB-ADR-2026-08-15-001

**Decision:** mobile presentation architecture uses shared domain/business
logic with an explicit presentation boundary; dedicated mobile partials are
allowed only where semantics materially differ.

**Status:** accepted working architecture decision

**Record:** `docs/decisions/2026-08-15__mobile_presentation_architecture.md`
<!-- FP_MOBILE_PRESENTATION_ADR_V0_1_END -->

<!-- FP_CANONICAL_FULL_HOSTING_SYNC_ADR_V1 -->
## FP-WEB-ADR-2026-08-17-002

**Decision:** canonical complete local-to-hosting sync with off-host rollback
**Status:** accepted
**Record:** `docs/decisions/2026-08-17__canonical_full_hosting_sync_and_local_rollback.md`
<!-- /FP_CANONICAL_FULL_HOSTING_SYNC_ADR_V1 -->

<!-- FP_HOSTING_FULL_SYNC_HARDENING_ADR_V1 -->
## FP-WEB-ADR-2026-08-18-001

**Decision:** production-proven complete hosting synchronization baseline with
local regression contract

**Status:** accepted / verified in production

**Working state:**
`docs/working-state/2026-08-18__hosting_full_sync_working_state_v0_1.md`
<!-- /FP_HOSTING_FULL_SYNC_HARDENING_ADR_V1 -->

<!-- FP-PRODUCT-MEDIA-ADR-2026-08-21-START -->
## FP-WEB-ADR-2026-08-21-001

**Decision:** canonical product media owner and deterministic search renditions
**Status:** accepted
**Record:** `docs/decisions/2026-08-21__canonical_product_media_owner_and_search_renditions.md`

`GoodsImageUploadOptimizer.php` owns product-media processing and deterministic main-image search families. Search renditions remain filesystem derivatives of canonical `goods.img`; lifecycle cleanup and runtime-root portability are part of the same owner contract.
<!-- FP-PRODUCT-MEDIA-ADR-2026-08-21-END -->
