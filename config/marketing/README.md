# Marketing control plane

`config/marketing/` is the machine-readable control plane for marketing
automation.

It stores stable IDs, lifecycle state, relationships, capabilities,
measurement contracts, and symbolic credential references.

Never store OAuth refresh tokens, client secrets, developer tokens, passwords,
API secrets, cookies, or private provider payloads here.

Credentials remain outside Git and are referenced only through
`credential_ref`.

Machine-generated timestamps use RFC 3339 / ISO 8601 with timezone.
Stable project IDs are never reused.

<!-- FP_MARKETING_CONTROL_PLANE_V02_START -->
## Current control-plane split

Current machine-readable ownership is intentionally separated:

- `source_registry_v0_1.yaml` — provider/API integration sources;
- `data_source_registry_v0_1.yaml` — analytical/internal data sources,
  sensitivity classification and Git/export policy;
- `reference_registry_v0_1.yaml` — official external reference provenance;
- `measurement/event_contract_v0_1.yaml` — measurement events, conversions and
  fail-closed privacy constraints.

The current aggregate validation profile is
`schemas/marketing_control_plane_v0_2.schema.json`.

Provider integrations, data sources, references and measurement events are
different object classes and must not be collapsed into one generic registry.
<!-- FP_MARKETING_CONTROL_PLANE_V02_END -->
