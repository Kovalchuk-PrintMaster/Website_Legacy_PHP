# Marketing standards profile v0.2

- **ID:** `FP-WEB-REF-MARKETING-STANDARDS-001`
- **Date:** 2026-08-12
- **Status:** active current reference
- **Supersedes:** `marketing_standards_profile_v0_1.md`

## Purpose

This profile defines interoperability rules for the marketing control plane,
automation, provider integrations, and evidence artifacts.

Project-specific semantics remain in ForPrint registries and policies.
Serialization, validation, timestamps, identifiers, checksums, API boundaries,
and mutation evidence use established external standards where practical.

## Serialization and validation

Human-maintained control-plane documents use YAML 1.2 syntax.

Canonical Python parsing/validation uses `ruamel.yaml` in safe, pure-Python
YAML 1.2 mode with duplicate mapping keys rejected.

Machine structural validation uses JSON Schema Draft 2020-12 after YAML is
parsed to a JSON-compatible data model.

UTF-8 is the canonical text encoding. Repository text files use LF line
endings.

New registry fields must be represented in schema before automation depends on
them.

## Time

Machine timestamps use RFC 3339 / ISO 8601 date-time representation with an
explicit UTC offset or `Z`.

Calendar-only review dates use `YYYY-MM-DD`.

Source/account/property timezone is explicit where it affects reporting.

## Stable identifiers

ForPrint-owned objects use immutable project IDs:

- `MKT-SRC-*` — source/provider integration;
- `MKT-PROG-*` — long-lived program;
- `MKT-CAMP-*` — campaign;
- `MKT-WORK-*` — work item;
- `MKT-REPORT-*` — report;
- `MKT-LP-*` — landing page;
- `MKT-EXP-*` — experiment;
- `MKT-OP-*` — mutation operation plan.

External provider resource IDs are stored separately and are not reused as the
project object's identity.

Project IDs are never reused after retirement.

## Schema evolution

`schema_version` is required on machine-readable control-plane documents.

Consumers fail closed on unsupported schema versions.

Breaking contract changes require a new schema version and an explicit
migration.

## Checksums and immutable plans

SHA-256 is the default repository checksum for immutable operation plans,
external acquisition manifests, and significant generated evidence.

An apply operation consumes the exact authorized plan ID/checksum. Rebuilding
intent creates a new plan.

## Python dependency configuration

Repository-local Python dependencies are declared under
`config/python/requirements/`.

The current marketing dependency manifest is
`config/python/requirements/marketing.txt`.

The installed `.venv_website/` environment is local runtime state and is not a
dependency source of truth.

A root `pyproject.toml` is intentionally not introduced by the marketing
foundation. If Python tooling later becomes a cohesive package/project, its
project boundary and standardized packaging metadata must be adopted through a
separate repository-wide tooling decision.

## API adapters

Provider API clients are isolated behind `scripts/marketing/connectors/`.

Adapters record provider/API version in evidence and expose project-domain
operations to higher layers.

Provider SDK objects and credentials are not persistent project-domain
configuration.

## Read operations

Read collectors record source ID, provider account/property/customer reference,
API/export method, reporting period, source timezone when relevant,
dimensions/metrics/filters, retrieval timestamp, adapter/API version, and
pagination/sampling/threshold/limit metadata when exposed.

## Write operations

Mutations follow:

`discover -> plan -> preview/validate -> authorize -> apply -> verify -> evidence`

Provider validation-only capability is used where available, but it does not
replace local semantic review.

Partial-failure behavior is explicit. Post-apply read-back verification is
required for project-controlled mutation workflows.

## Secret boundary

Tracked configuration contains symbolic `credential_ref` values only.

OAuth refresh tokens, client secrets, developer tokens, API keys, passwords,
cookies, session data, and equivalent credentials stay outside Git.

Read-only integrations use least privilege rather than receiving write
authority by default.

## Data retention

Git stores schemas, manifests, safe curated datasets, configuration, code, and
durable aggregated evidence.

Large raw provider exports and personal/user-level analytics data use protected
local/external storage unless a reviewed policy explicitly authorizes
repository storage.

<!-- FP_MARKETING_ID_FAMILIES_V02_START -->
## Additional stable control-plane ID families

The current control plane also reserves immutable project IDs:

- `MKT-DATA-*` — analytical/internal data source;
- `MKT-REF-*` — official external reference/provenance record;
- `MKT-EVT-*` — website measurement event.

These IDs are distinct from external provider resource IDs and from provider
integration IDs (`MKT-SRC-*`). Project IDs are never reused after retirement.
<!-- FP_MARKETING_ID_FAMILIES_V02_END -->
