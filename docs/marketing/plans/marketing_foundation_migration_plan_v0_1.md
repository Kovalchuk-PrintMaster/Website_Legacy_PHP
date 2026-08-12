# Marketing foundation migration plan v0.1

- **Date:** 2026-08-11
- **Status:** active plan

## MARKETING.01 — foundation — completed 2026-08-12

Create umbrella, registries, data-zone policy, API automation policy, migration
map, validator, and documentation index. Move nothing from legacy SEO.

## MARKETING.02 — schema/validation hardening — active guardrail

Establish formal schemas and validators for IDs/relationships, lifecycle
states, credential-reference rules, secrets, data zones, provenance, and
operation plans/evidence before migrating legacy material.

## MARKETING.03 — inventory/classification — next

Build a machine-readable inventory of `seo/`, `docs/seo/`, SEO/Ads ADRs/plans,
snapshots, documentation packages, Ads exports/imports/research, and Business
Profile assets.

## MARKETING.04 — controlled physical migration

Use explicit `git mv` for current canonical material. Preserve historical
snapshots and centralized ADRs. Update references atomically.

## Documentation-currentness gate

Every MARKETING migration phase must classify affected documentation as one of:

- current living document;
- superseded predecessor;
- historical evidence;
- transitional compatibility material;
- removable obsolete material.

Current indexes and the canonical-document registry must point to the accepted
successor before a predecessor is considered retired.

## MARKETING.05 — API read automation

Implement read-only Python connectors/collectors for high-value reporting
sources. Record source ID, API version, query contract, period, and provenance.

## MARKETING.06 — assisted mutations

Add immutable plans, semantic previews, provider validation where supported,
explicit apply authorization, read-back verification, and evidence capture.

## MARKETING.07 — policy-managed automation

After assisted mode is stable, allow bounded non-interactive routine changes
under explicit versioned policy.

<!-- FP_MARKETING_02C_PROGRESS_START -->
## MARKETING.02C progress — 2026-08-12

The active schema/validation guardrail now includes canonical contracts for:

- website measurement events/privacy;
- analytical/internal data sources;
- official external reference provenance.

The aggregate schema profile advances from v0.1 to v0.2 while v0.1 remains the
predecessor profile.

MARKETING.02 is still an active guardrail. Remaining deeper hardening includes
operation-plan/evidence contracts, acquisition/provenance manifests, remaining
registry referential integrity and later API-operation semantics.

MARKETING.03 review evidence remains valid; MARKETING.04 is not authorized by
this change.
<!-- FP_MARKETING_02C_PROGRESS_END -->
