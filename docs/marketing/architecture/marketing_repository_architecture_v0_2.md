# Marketing repository architecture v0.2

- **ID:** `FP-WEB-ARCH-MARKETING-001`
- **Date:** 2026-08-12
- **Status:** accepted current architecture
- **Supersedes:** `marketing_repository_architecture_v0_1.md`
- **Decision:** `docs/decisions/2026-08-12__marketing_control_plane_contract_split_and_schema_v0_2.md`
- **Scope:** promotion, measurement, research, campaigns, local presence

## Purpose

ForPrint needs a durable marketing subsystem, not an ad-hoc SEO folder. It must
support years of organic search work, advertising campaigns, analytics,
hosting observations, recurring reports, experiments, new products/providers,
and progressively deeper API automation.

Provider dashboards, temporary exports, and personal memory are not the
project system of record.

## Four planes

### Control plane — `config/marketing/`

Machine-readable registries/contracts own stable IDs, lifecycle state,
relationships, capabilities, and automation policy.

### Workspace/evidence plane — `marketing/`

Research, campaign evidence, curated datasets, reports, local-presence assets,
experiments, and acquisition manifests.

### Automation plane — `scripts/marketing/`

Python adapters, collectors, transforms, reporting, controlled mutations, and
validators.

### Governance plane — `docs/marketing/` + `docs/decisions/`

Architecture, policies, runbooks, plans, reference material, and centralized
ADRs. Historical snapshots remain in `docs/status/snapshots/`.

## Domain boundaries

SEO becomes `organic-search/`, one sibling capability alongside Ads,
Analytics, local presence, hosting analytics, experiments, and cross-source
reporting.

## Stable identity and lifecycle

Programs, campaigns, work items, reports, landing pages, experiments, and
sources use stable IDs. Lifecycle is metadata, not a folder name.

Campaign lifecycle:

`draft -> review -> ready -> active -> paused/completed -> archived`

Work lifecycle:

`backlog -> ready -> in_progress -> blocked/paused -> done/cancelled`

## Programs

Programs are long-lived strategic streams. Initial programs cover measurement,
organic search, Google Ads acquisition, local presence, and landing-page
optimization.

## Data architecture

Canonical flow:

`raw -> staged -> curated -> reports`

Raw provider acquisitions are immutable. Staged data is machine-normalized.
Curated data is reviewed and decision-ready. Reports are derived products.

Git is not a data warehouse. Large raw exports, private provider payloads,
credentials, and personal/user-level analytics data remain outside Git by
default. Small manifests preserve provenance.

## Provider adapters

Provider APIs are isolated behind Python adapters. Project business logic uses
project-domain models rather than leaking provider clients across the codebase.

Target families: Google Analytics Data/Admin APIs, Search Console API, Google
Ads API, Business Profile APIs, hosting/server analytics, and future providers.

API versions are pinned by adapters and recorded in operation evidence.

## Read automation

Read/report automation is the first automation layer. Collectors are
source-explicit, retry/quota-aware, idempotent against their output contract,
provenance-producing, and secret-safe.

## Write automation

Mutations use:

`discover -> plan -> preview/validate -> authorize -> apply -> verify -> evidence`

Management modes:

- `manual`;
- `assisted`;
- `managed`.

The long-term direction is managed automation where safe, not permanent
dependence on provider dashboards.

## Mutation safety

Each write adapter defines allowed resources/fields, authorization boundary,
preview/validation, before/desired state, failure behavior, post-apply
read-back, evidence, and rollback/compensating action where feasible.

High-impact budget, bidding, activation, targeting, conversion, or measurement
changes need explicit policy before managed mode.

## Secrets/access

Tracked files contain only symbolic `credential_ref` values. OAuth credentials,
refresh tokens, developer tokens, API secrets, passwords, cookies, and provider
sessions stay outside Git. Least privilege applies.

## Migration boundary

Existing `seo/`, `docs/seo/`, Ads Editor imports/exports, keyword research,
Business Profile assets, measurement contracts, and snapshots are migration
inputs. MARKETING.01 does not move or reinterpret them.

MARKETING.03 classifies each artifact before physical migration.

## Documentation evolution

Marketing documentation follows the repository-wide living-documentation
policy.

Current architecture, policies, runbooks and references are updated in place
when the accepted contract is unchanged but facts or explanations evolve.

Material ownership, API contract, lifecycle or automation-safety changes create
a newer canonical revision and explicitly supersede the predecessor.

Historical campaign evidence, snapshots and completed plans are not rewritten
into the current state.

Legacy SEO documents retained only for migration/compatibility are
transitional, must identify their replacement/retirement condition during
classification, and are not allowed to override current marketing control
plane or architecture.

## Change rule

Material changes to domain ownership, control-plane contracts, lifecycle,
mutation authorization, secrets, or data-zone guarantees require an ADR and a
versioned architecture update.

<!-- FP_MARKETING_CONTROL_PLANE_SPLIT_V02_START -->
## Control-plane object separation — current refinement

The control plane distinguishes four source-like concepts rather than using one
generic source registry:

1. **Provider/API integration source** — API/account/adapter capability,
   credential reference and write mode. Owner:
   `config/marketing/source_registry_v0_1.yaml`.
2. **Data source** — analytical/internal dataset origin, sensitivity class,
   repository/export policy and optional provider-source relationship. Owner:
   `config/marketing/data_source_registry_v0_1.yaml`.
3. **Official reference** — external standards/provider documentation
   provenance (`owner`, URL, retrieval date, purpose). Owner:
   `config/marketing/reference_registry_v0_1.yaml`.
4. **Measurement event contract** — website event names, conversion semantics,
   allowed/forbidden parameters and privacy rules. Owner:
   `config/marketing/measurement/event_contract_v0_1.yaml`.

This is a refinement of the existing four-plane architecture, not a new
repository plane. Legacy `seo/config/*` remains migration input until exact
MARKETING.03E/04 retirement decisions are approved.
<!-- FP_MARKETING_CONTROL_PLANE_SPLIT_V02_END -->
