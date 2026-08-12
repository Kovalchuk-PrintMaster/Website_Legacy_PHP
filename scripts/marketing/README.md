# Marketing automation

Python is the canonical automation language for this domain.

Target modules:

- `connectors/` — provider API adapters;
- `collectors/` — read-only acquisitions;
- `transforms/` — deterministic normalization;
- `reporting/` — reusable reporting;
- `campaign_ops/` — controlled mutations;
- `validators/` — registry/plan/evidence validation.

Provider adapters are separated from project business logic.

Write operations follow:

`discover -> plan -> preview/validate -> authorize -> apply -> verify -> evidence`

Mutation commands default to no mutation. Long-term `policy_managed`
automation is supported only under explicit bounded policy.

Credentials never belong in source code, reports, command examples, or
Git-tracked configuration.

## Python environment

Marketing automation uses the repository-local Python virtual environment.

Canonical dependency input:

`config/python/requirements/marketing.txt`

The initial schema-validation stack is pinned for reproducibility and includes
a YAML 1.2 parser plus JSON Schema Draft 2020-12 validation.

<!-- FP_MARKETING_VALIDATION_V02_START -->
## Control-plane validation profile v0.2

Run:

`.venv_website/bin/python scripts/marketing/validate_marketing_control_plane.py`

The validator checks nine canonical control-plane documents using YAML 1.2 and
JSON Schema Draft 2020-12, then applies project-level semantic checks for ID
uniqueness, program/provider references, measurement privacy rules, event
parameter conflicts and normalized official-reference URL uniqueness.
<!-- FP_MARKETING_VALIDATION_V02_END -->
