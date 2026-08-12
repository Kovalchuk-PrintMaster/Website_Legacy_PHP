# Marketing data zones

Canonical flow:

`raw -> staged -> curated -> reports`

- `raw` — immutable acquisition evidence;
- `staged` — machine-normalized intermediate data;
- `curated` — reviewed decision-ready datasets;
- `reports` — derived analytical outputs.

Git is not the marketing data warehouse. Large raw exports, credentials,
private provider payloads, and personal/user-level analytics data are not
committed by default.

Durable acquisitions/reports should record source ID, period, retrieval time,
API/export method, query/transform definition, schema/tool version, and
checksum where practical.
