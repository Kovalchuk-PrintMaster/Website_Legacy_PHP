# Marketing schemas

Machine-readable marketing YAML is parsed as YAML 1.2 and validated with
JSON Schema Draft 2020-12.

## Current schema profile

`marketing_control_plane_v0_2.schema.json` is the current validation profile.

It validates the original foundation registries plus:

- `marketing_measurement_event_contract`;
- `marketing_data_source_registry`;
- `marketing_reference_registry`.

`marketing_control_plane_v0_1.schema.json` remains the predecessor schema
profile and is not the current validator target.

Individual control documents retain their own `schema_version`; the schema
profile revision describes the set of document contracts validated together.

Schema validation complements semantic/referential validation and does not
replace it.
