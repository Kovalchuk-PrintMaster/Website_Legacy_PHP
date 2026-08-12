# Decision: marketing control-plane contract split and schema profile v0.2

- **ID:** `FP-WEB-ADR-2026-08-12-001`
- **Date:** 2026-08-12
- **Status:** accepted
- **Scope:** marketing control plane, measurement, data governance, reference provenance

## Context

The MARKETING.01 foundation established `config/marketing/` as the
machine-readable control plane and intentionally deferred semantic migration of
legacy SEO/measurement contracts.

MARKETING.03 classification and semantic reconciliation showed that four
concepts previously grouped under source/SEO configuration have different
ownership and safety semantics:

1. provider/API integration source;
2. analytical/internal data source;
3. official external reference/provenance record;
4. website measurement event/privacy contract.

Keeping these as one generic source registry would blur credentials/API
capability, data-sensitivity policy, reference provenance and measurement
privacy.

## Decision

1. Keep the existing four repository planes unchanged.
2. Keep `config/marketing/source_registry_v0_1.yaml` as the provider/API
   integration registry.
3. Introduce `config/marketing/data_source_registry_v0_1.yaml` for analytical
   and internal data-source ownership, personal-data classification, Git/export
   policy and optional provider-source linkage.
4. Introduce `config/marketing/reference_registry_v0_1.yaml` for official
   external reference provenance.
5. Introduce
   `config/marketing/measurement/event_contract_v0_1.yaml` for approved website
   measurement events, conversion semantics, allowed/forbidden parameters and
   fail-closed privacy rules.
6. Reserve immutable project-ID families `MKT-DATA-*`, `MKT-REF-*` and
   `MKT-EVT-*`.
7. Advance the aggregate JSON Schema validation profile to
   `marketing_control_plane_v0_2.schema.json`; retain profile v0.1 as the
   predecessor.
8. Keep measurement privacy fail-closed: credentials, free text, PII and raw
   request IDs are not approved measurement payloads.
9. Legacy `seo/config/*` remains migration input until MARKETING.03E/04 exact
   retirement/migration decisions. This ADR does not authorize physical legacy
   migration.

## Supersession

This material control-plane contract change creates:

- `docs/marketing/architecture/marketing_repository_architecture_v0_2.md`,
  superseding v0.1;
- `docs/marketing/reference/marketing_standards_profile_v0_2.md`,
  superseding v0.1.

The MARKETING.01 ADR remains accepted; this decision refines its control-plane
contract rather than replacing the marketing umbrella/API-first direction.

## Consequences

Positive:

- provider capability and credentials stay distinct from dataset policy;
- data-source sensitivity/Git rules become machine-readable;
- external reference provenance no longer competes with provider-source
  identity;
- measurement privacy is an explicit validated contract;
- schema evolution and stable IDs remain fail-closed and reviewable.

Cost:

- migration tooling must reconcile legacy IDs/paths with the new object classes
  before MARKETING.04;
- current documentation indexes and registry must move to the v0.2 successors.
