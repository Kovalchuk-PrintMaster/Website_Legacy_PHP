# ForPrint Website

## Purpose

This repository is the versioned source of truth for the maintained ForPrint
public website, its deployment/inspection tooling, technical documentation, and
the repository-owned marketing automation subsystem.

The inherited website runtime remains PHP under:

```text
base/
```

Python is used for repository tooling, inspections, maintenance automation,
deployment operators, reporting, and marketing/API integration.

## Current repository model

Canonical top-level domains include:

```text
base/          public website/webroot source
config/        tracked configuration contracts and examples
coordination/  implementation evidence and coordination state
database_dumps/
docs/          current technical documentation and historical evidence
marketing/     canonical promotion/measurement workspace
scripts/       Python inspection, maintenance and automation tooling
```

`seo/` is a transitional legacy migration source. New cross-channel marketing
work belongs under `marketing/`, `config/marketing/`, `scripts/marketing/`, and
`docs/marketing/`.

`tmp/` and root `tmp.py`/`tmp.php` are permitted ignored local scratch state,
not canonical repository content.

## Website ownership boundary

The website may present public product/service information, landing pages,
communication forms, search-visible content, and other website-owned
presentation/runtime behavior.

The website must not silently become the canonical owner of wider ForPrint
business domains such as accounting, stock, payments, 1C data, or external
operational registries unless a separate integration/ownership decision
explicitly establishes that contract.

## Development direction

The project is maintained through progressive in-place modernization rather
than an uncontrolled rewrite.

Stable inherited behavior may remain while maintainable project-owned layers
progressively replace legacy presentation or implementation boundaries.

Current architecture and accepted ownership are indexed from:

```text
docs/README.md
docs/documentation/canonical_document_registry_v0_1.yaml
docs/decisions/architecture_decision_register_v0_1.md
```

Historical snapshots and superseded documents remain evidence; they do not
override newer accepted current-state documentation.

## Marketing automation

Marketing is a first-class repository domain rather than an SEO-only workspace.

```text
config/marketing/   machine-readable control plane
marketing/          research, campaign evidence, reports and safe data
scripts/marketing/  Python API/reporting/mutation automation
docs/marketing/     architecture, policies, plans and reference
```

The long-term automation model favors reproducible API/report workflows over
repetitive provider-dashboard work.

Provider mutations use a guarded flow:

```text
discover -> plan -> preview/validate -> authorize -> apply -> verify -> evidence
```

Credentials and provider secrets remain outside Git.

## Python environment

Repository-local Python tooling currently uses:

```text
.venv_website/
```

Tracked Python dependency inputs live under:

```text
config/python/
```

The virtual environment is local runtime state and is not committed.

## Release and runtime safety

A dirty working tree is not a deployment scope.

Production release, database ownership, communication runtime, hosting
environment preservation, rollback, and parity behavior are governed by the
current workflow/decision documents under `docs/`.

Do not commit secrets. Keep production-only credentials/runtime state outside
the public webroot and outside Git-tracked configuration.

Prefer read-only inspection before mutation, explicit scope before apply, and
post-change verification with durable evidence.
