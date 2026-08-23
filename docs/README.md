# ForPrint Website — індекс документації

**Registry:** `documentation/canonical_document_registry_v0_1.yaml`
**Оновлено:** 2026-08-12
**Статус:** active current-state index

## Призначення

Цей каталог є головною навігаційною точкою поточної технічної документації ForPrint Website. Індекс охоплює current-state документи та посилається на історичні evidence/snapshots там, де вони потрібні для traceability.

Він фіксує:

- поточну архітектуру репозиторію, runtime і ownership boundaries;
- межі legacy та прогресивно модернізованих компонентів;
- операційний workflow, deployment/recovery та локальний scratch contract;
- canonical marketing/API-automation architecture;
- current plans, policies, registries та accepted decisions;
- historical evidence, яке зберігається окремо від current-state reading path.

## Розділи

| Каталог | Призначення |
|---|---|
| `architecture/` | Архітектура, потоки, межі старого і нового, frontend strategy |
| `marketing/` | Marketing architecture, API automation policy, plans and reference |
| `workflow/` | Робочий процес, tmp-протокол, checks і Git |
| `status/` | Датовані snapshots фактичного стану |
| `plans/` | Версійні плани запуску й стабілізації |
| `decisions/` | Реєстр прийнятих рішень |
| `reference/` | Карта репозиторію, критичні файли, словник |
| `documentation/` | Політика документації та маніфест пакета |
| `development/` | Уже наявні feature-level документи |
| `launch_readiness/` | Уже наявні readiness-документи |

## Порядок читання

1. `architecture/system_architecture_overview_v0_2.md`
2. `architecture/legacy_and_modern_boundaries_v0_2.md`
3. `architecture/frontend_css_ownership_and_layout_strategy_v0_3.md`
4. `workflow/hosting_deployment_profiles_v0_1.md`
5. `workflow/communication_release_safety_and_recovery_v0_1.md`
6. `workflow/production_operational_data_and_database_sync_v0_1.md`
7. `marketing/README.md`
8. `decisions/architecture_decision_register_v0_1.md`

Historical snapshots, completed plans and coordination reports remain
available for traceability but are not the primary current-state reading path.
## Джерела істини

- Код, схема БД і runtime-конфігурація визначають фактичну поведінку.
- Snapshot описує історичний стан на конкретну дату.
- Plan визначає погоджену чергу конкретного етапу.
- Decision діє, доки нове рішення явно його не замінить.
- `coordination/reports/` підтверджує виконання окремих блоків.
- `development/` пояснює реалізацію окремих функцій.

## Базовий принцип

Поточна мета — безпечно підтримувати й розвивати діючий сайт, прогресивно модернізувати legacy-межі, зберігати контрольований production-release contract та будувати відтворювану marketing/API automation. Стабільна legacy-основа може зберігатися, доки її ownership і поведінка залишаються зрозумілими та контрольованими.

<!-- FRONTEND_CHECKPOINT_INDEX_START -->
## Active first-release frontend checkpoint

- [First Release Frontend Checkpoint v0.1](status/first_release_frontend_checkpoint_v0_1.md)
- [First Release Checkpoint Commit Manifest v0.1](status/first_release_checkpoint_commit_manifest_v0_1.md)
- [Frontend Surface Stabilization Roadmap v0.1](plans/frontend_surface_stabilization_roadmap_v0_1.md)
- [Frontend Surface Isolation Strategy v0.1](architecture/frontend_surface_isolation_strategy_v0_1.md)
- [Decision: Freeze First-Release Scope and Start Progressive Frontend Refactor](decisions/2026-07-17__first_release_scope_freeze_and_frontend_refactor.md)
<!-- FRONTEND_CHECKPOINT_INDEX_END -->

## Frontend control governance

- [Disabled and Deferred Interface Capabilities v0.1](reference/disabled_and_deferred_interface_capabilities_v0_1.md)
- [Interface Capability Registry v0.1 — machine-readable](reference/interface_capability_registry_v0_1.yaml)
- [Frontend Visual System v0.1](architecture/frontend_visual_system_v0_1.md)
- [Frontend Visual System v0.1 — machine-readable](architecture/frontend_visual_system_v0_1.yaml)
- [Media Storage and Image Processing Policy v0.1](architecture/media_storage_and_image_processing_policy_v0_1.md)

## Home frontend contract

- [Home Frontend Functional Contract v0.1](reference/home_frontend_functional_contract_v0_1.md)
- [Home Frontend Functional Contract v0.1 — machine-readable](reference/home_frontend_functional_contract_v0_1.yaml)
- [Home Frontend Block Map v0.1](architecture/home_frontend_block_map_v0_1.md)

<!-- FP_DUAL_TRACK_DOC_INDEX_V0_1 -->
## Актуальна frontend-стратегія від 2026-07-18

Канонічний набір для поточного етапу:

- [`reference/legacy_frontend_current_state_v0_1.md`](reference/legacy_frontend_current_state_v0_1.md) — фактичний стан legacy frontend;
- [`reference/inspection_and_maintenance_tools_v0_1.md`](reference/inspection_and_maintenance_tools_v0_1.md) — реєстр постійних inspection і maintenance інструментів;
- [`decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md`](decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md) — рішення про два паралельні frontend-треки;
- [`plans/legacy_publication_and_modern_frontend_plan_v0_1.md`](plans/legacy_publication_and_modern_frontend_plan_v0_1.md) — план публікації legacy та ізольованої розробки modern frontend.

`coordination/reports/` зберігає історичні докази виконання і не є заміною актуальним reference, decision та plan документам.

<!-- FP-FRONTEND-DOCS-V02-START -->
## Frontend architecture working checkpoint — 2026-07-20

- [Frontend CSS Ownership and Layout Strategy v0.3](architecture/frontend_css_ownership_and_layout_strategy_v0_3.md)
- [Homepage Structure and Slider Architecture v0.1](architecture/home_frontend_structure_and_slider_architecture_v0_1.md)
- [Canonical Frontend CSS Ownership and Homepage Layout Decision](decisions/2026-07-20__canonical_frontend_css_ownership_and_homepage_layout.md)
- [Frontend Working State — 2026-07-20](status/snapshots/2026-07-20_frontend_working_state_v0_1.md)
- [Frontend Next-Stage Plan v0.2](plans/frontend_next_stage_plan_v0_2.md)
- [Documentation Package Manifest v0.2](documentation/package_manifest_v0_2.md)
<!-- FP-FRONTEND-DOCS-V02-END -->

<!-- FP_DEPLOY_NOTIFICATION_DOCS_V0_1_START -->
## Deployment, mirroring, and notification delivery

- [Deployment and Mirroring Policy v0.1](workflow/deployment_and_mirroring_policy_v0_1.md)
- [Notification Delivery Architecture v0.1](architecture/notification_delivery_architecture_v0_1.md)
<!-- FP_DEPLOY_NOTIFICATION_DOCS_V0_1_END -->

- Repository-root and runtime layout: `architecture/repository_root_and_runtime_layout_v0_2.md`.

<!-- FP-PRODUCTION-RELEASE-DOCS-V0-1-START -->
## Production release and recovery

- [Production Release and Recovery Runbook v0.1](workflow/production_release_and_recovery_runbook_v0_1.md)
- [Decision: s01 Source of Truth and Controlled Production Mirror](decisions/2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md)
- [Production Release State — 2026-07-30](status/snapshots/2026-07-30_production_release_state_v0_1.md)

The `s01` Git repository is authoritative for versioned website code.
Production is a controlled mirror. Database, uploaded media, DNS and
production-only secrets are separate state classes.
<!-- FP-PRODUCTION-RELEASE-DOCS-V0-1-END -->

<!-- FP_FRONTEND_CHECKPOINT_2026_08_06_INDEX_START -->
## Frontend foundation checkpoint — 2026-08-06

- [Historical snapshot](status/snapshots/2026-08-06_frontend_foundation_stable_checkpoint_v0_1.md)
- [Checkpoint and next-stage decision](decisions/2026-08-06__frontend-foundation-stable-checkpoint-and-next-stage.md)

The checkpoint is local-source only. It does not record deployment and does
not include the parallel SEO/Ads research workspace.
<!-- FP_FRONTEND_CHECKPOINT_2026_08_06_INDEX_END -->

<!-- FP_HOSTING_MIRROR_DOC_INDEX_V0_1 -->
## Hosting mirror — canonical operational references

- [`workflow/hosting_mirror_reset_runbook_v0_1.md`](workflow/hosting_mirror_reset_runbook_v0_1.md) — full local-to-hosting mirror reset, database parity, environment preservation and rollback.
- [`decisions/2026-08-07__hosting_mirror_logical_database_parity_and_environment_preservation.md`](decisions/2026-08-07__hosting_mirror_logical_database_parity_and_environment_preservation.md) — accepted database-parity and hosting-environment boundary.
- `coordination/reports/2026-08-07_hosting_mirror_database_parity_incident_v0_1.md` — evidence for the false schema-mismatch rollback incident.

Ownership is explicit: local is canonical for application files, database schema and non-operational database content; production is canonical for operational rows such as `communication_requests`; hosting runtime/environment state remains hosting-owned and preserved.

<!-- FP_HOSTING_MIRROR_OPERATOR_REPORTING_V0_1 -->
### Hosting mirror operator reporting

- [`workflow/hosting_mirror_operator_reporting_v0_1.md`](workflow/hosting_mirror_operator_reporting_v0_1.md) — summary-first operator output with detailed diagnostics retained on demand.

<!-- FP_HOSTING_DEPLOYMENT_PROFILES_V0_1 -->
## Hosting deployment profiles

- [`workflow/hosting_deployment_profiles_v0_1.md`](workflow/hosting_deployment_profiles_v0_1.md) — full, code, frontend, backend, dependency, database, media and exact-manifest hosting release modes.
- [`decisions/2026-08-07__hosting_deployment_profiles_and_scope_boundaries.md`](decisions/2026-08-07__hosting_deployment_profiles_and_scope_boundaries.md) — accepted release-scope boundaries.

<!-- FP_COMMUNICATION_RELEASE_SAFETY_DOCS_V0_1_START -->
## Communication release safety

- [`workflow/communication_release_safety_and_recovery_v0_1.md`](workflow/communication_release_safety_and_recovery_v0_1.md) — non-sending acceptance, deployment rules and fast recovery.
- [`decisions/2026-08-07__communication_runtime_contract_and_deployment_acceptance.md`](decisions/2026-08-07__communication_runtime_contract_and_deployment_acceptance.md) — accepted runtime/issuer/verifier boundary.

Operator command:

```text
make hosting-communication-check
```

Production-only communication state remains outside deployment payloads.
<!-- FP_COMMUNICATION_RELEASE_SAFETY_DOCS_V0_1_END -->

<!-- FP_OPERATIONAL_DB_DOCS_V0_1_START -->
## Production operational database ownership

Policy: `config/deployment/database_ownership_policy_v0_1.json`.

`communication_requests` is production-owned for content and strict local-canonical for schema.
<!-- FP_OPERATIONAL_DB_DOCS_V0_1_END -->

<!-- FP_MARKETING_DOC_INDEX_V0_1_START -->
## Marketing automation and growth subsystem

Canonical current entry point:

- [`marketing/README.md`](marketing/README.md)
- [`marketing/architecture/marketing_repository_architecture_v0_2.md`](marketing/architecture/marketing_repository_architecture_v0_2.md)
- [`marketing/reference/marketing_standards_profile_v0_2.md`](marketing/reference/marketing_standards_profile_v0_2.md)
- [`marketing/policies/marketing_api_automation_policy_v0_1.md`](marketing/policies/marketing_api_automation_policy_v0_1.md)
- [`decisions/2026-08-11__marketing_control_plane_and_api_first_automation.md`](decisions/2026-08-11__marketing_control_plane_and_api_first_automation.md)
- [`reference/repository_map_v0_2.md`](reference/repository_map_v0_2.md)

`marketing/`, `config/marketing/`, `scripts/marketing/` and `docs/marketing/` are the current promotion and measurement owners. The controlled legacy SEO migration is complete.

<!-- FP_DOCUMENTATION_LIFECYCLE_INDEX_V0_1_START -->
## Documentation lifecycle and currentness

- [`documentation/documentation_versioning_policy_v0_1.md`](documentation/documentation_versioning_policy_v0_1.md) — living/current/historical/transitional document lifecycle rules.
- [`documentation/canonical_document_registry_v0_1.yaml`](documentation/canonical_document_registry_v0_1.yaml) — machine-readable critical current-document registry.

Current documentation is allowed to evolve aggressively when facts change.
Historical evidence remains historical; materially changed contracts receive a
new canonical revision; obsolete material without a current/history/
compatibility purpose does not remain active merely because it is old.
<!-- FP_DOCUMENTATION_LIFECYCLE_INDEX_V0_1_END -->

<!-- FP_SEARCH_VISIBILITY_RELEASE_20260819_START -->
## Search visibility production checkpoint — 2026-08-19

- `decisions/2026-08-19__canonical_public_metadata_and_breadcrumb_structured_data_ownership.md`
- `status/snapshots/2026-08-19_search_visibility_release_state_v0_1.md`
- `marketing/plans/organic_search_measurement_next_stage_plan_v0_1.md`

Production evidence is stored under `coordination/reports/`.
<!-- FP_SEARCH_VISIBILITY_RELEASE_20260819_END -->

<!-- FP-MARKETING-SEARCH-ADS-2026-08-20-START -->
## Marketing, Search and Google Ads checkpoint — 2026-08-20

- [Marketing, Search and Google Ads Working State — 2026-08-20 v0.1](status/snapshots/2026-08-20_marketing_search_ads_working_state_v0_1.md)
- [Marketing, Search, Ads and SERP Enhancement Next-Stage Plan v0.1](plans/marketing_search_ads_and_serp_enhancement_plan_v0_1.md)

This checkpoint separates:

- accepted technical SEO / Batch A evidence;
- pending SEO Batch B release;
- Google Ads verification/policy/delivery work;
- rich organic-search image and price presentation;
- later performance optimization.
<!-- FP-MARKETING-SEARCH-ADS-2026-08-20-END -->

<!-- FP-PRODUCT-MEDIA-DOCS-V03-START -->
## Canonical product media architecture — 2026-08-21

Current product-media documentation:

- [Media Storage and Image Processing Policy v0.2](architecture/media_storage_and_image_processing_policy_v0_2.md) — canonical media ownership, runtime storage, search profiles, cleanup and production policy;
- [Product Media Pipeline Reference v0.1](reference/product_media_pipeline_v0_1.md) — practical end-to-end runtime map for developers and operators;
- [Decision: canonical product media owner and search renditions](decisions/2026-08-21__canonical_product_media_owner_and_search_renditions.md) — accepted ownership and derivative architecture;
- [Product media and search-rendition production state — 2026-08-21](status/snapshots/2026-08-21_product_media_search_rendition_state_v0_1.md) — dated production/backfill evidence summary;
- [Documentation Package Manifest v0.3](documentation/package_manifest_v0_3.md) — package scope and validation.

`GoodsImageUploadOptimizer.php` is the canonical product-media owner. Product search variants are deterministic derivatives of `goods.img`, not independent database records.
<!-- FP-PRODUCT-MEDIA-DOCS-V03-END -->

<!-- FP-SEARCH-IDENTITY-BACKUP-ROADMAP-2026-08-21-START -->
## Search identity, final off-site backup, and admin modernization roadmap — 2026-08-21

Three explicit planned items are tracked:

- [Search identity, Google Drive backup, and admin UI roadmap addendum v0.2](plans/2026-08-21__search_identity_and_google_drive_backup_roadmap_addendum_v0_2.md) — improve Google Search site identity/direct-contact eligibility, finish public-production protection with a verified Google Drive backup through Cloud Backup Manager/rclone, and only then modernize the internal admin UI under one canonical project-owned style architecture.

The active Google Ads payments-profile correction and direct-advertiser verification reset remain higher priority. Admin UI modernization is intentionally the final roadmap item.
<!-- FP-SEARCH-IDENTITY-BACKUP-ROADMAP-2026-08-21-END -->

<!-- FP_RECURRING_PRODUCTION_QUALITY_AUDIT_V0_1_START -->
## Recurring production quality audits

- [`workflow/recurring_production_quality_audits_v0_1.md`](workflow/recurring_production_quality_audits_v0_1.md) — canonical policy and regression registry for recurring read-only production audits.
- [`plans/recurring_production_quality_audit_roadmap_v0_1.md`](plans/recurring_production_quality_audit_roadmap_v0_1.md) — intentionally final operationalization phase, after verified backup/restore protection and remaining implementation work.
<!-- FP_RECURRING_PRODUCTION_QUALITY_AUDIT_V0_1_END -->

<!-- FP_OPERATOR_ASSISTANT_BOOTSTRAP_INDEX_START -->
## Operator / assistant handoff

- [`workflow/operator_assistant_workflow_v0_1.md`](workflow/operator_assistant_workflow_v0_1.md) — canonical operator/assistant workflow and current context-window bootstrap.
<!-- FP_OPERATOR_ASSISTANT_BOOTSTRAP_INDEX_END -->

<!-- FP-BACKUP-DR-GOVERNANCE-V0-1-START -->
## Backup and disaster recovery

- [`architecture/backup_and_disaster_recovery_policy_v0_1.md`](architecture/backup_and_disaster_recovery_policy_v0_1.md) — canonical full-backup, dirty-worktree, database, encryption and retention policy.
- [`workflow/direct_google_drive_backup_and_restore_runbook_v0_1.md`](workflow/direct_google_drive_backup_and_restore_runbook_v0_1.md) — direct `rclone` Google Drive backup, verification and restore workflow.
- [`reference/backup_storage_and_recovery_resources_v0_1.md`](reference/backup_storage_and_recovery_resources_v0_1.md) — current production paths, rclone remotes, recovery-material locations and operator handoff map.
<!-- FP-BACKUP-DR-GOVERNANCE-V0-1-END -->
