# ForPrint Website system architecture overview v0.2

**ID:** `FP-WEB-ARCH-001-V02`
**Version:** 0.2
**Date:** 2026-08-08
**Status:** active canonical architecture
**Supersedes:** `system_architecture_overview_v0_1.md`

## Architectural model

ForPrint Website is an existing PHP website modernized progressively in place.

```text
PHP website runtime
    base/

Project-owned presentation
    forprint-*.css
    project-owned JavaScript
    PHP templates/components

Legacy presentation/runtime
    compatibility only where still required

Python tooling
    inspection
    maintenance
    deployment/release operations
    integration with other ForPrint modules
```

PHP remains the website runtime language. Python is the preferred language for
repository tooling, automation, inspection and inter-module operations.

## Frontend ownership

The actively maintained presentation layer is project-owned. Primary owners
include `forprint-layout.css`, `forprint-shell.css`, `forprint-home.css`,
`forprint-product-cards.css`, product/detail/communication/search styles and
project-owned JavaScript.

`forprint-mobile-portrait.js` belongs to the same progressive frontend
modernization. Inherited `style.css` and other legacy assets are compatibility
layers only.

A separate replacement frontend is not the current canonical architecture.

## Communication and hosting

Communication runtime uses `base/communication-request.php` and
`base/libraries/CommunicationRuntimeBootstrap.php`. Production communication
configuration/security state stays outside the public webroot and outside
normal application payloads.

Hosting deployment is controlled by Python operational tooling and explicit
release profiles. Routine deployment preserves hosting-owned runtime and
environment state.

## Database ownership

Database parity is ownership-policy aware:

```text
schema
    local canonical

canonical/non-operational content
    local canonical

declared production operational rows
    production canonical
```

Operational row-count/content drift may therefore be informational while
schema parity remains strict.

## Repository operations

```text
scripts/inspection/
    read-only validation and diagnostics

scripts/maintenance/
    explicit controlled mutations

scripts/operations/
    release/operator orchestration
```

`Makefile` exposes stable operator entrypoints over these tools.

## Governance and coordination

`docs/` contains current architecture, workflow, reference and decisions.
`coordination/` contains project coordination/evidence. Blueprint is an
external project-coordination module and is not a website runtime dependency.

Root `tmp.py`, root `tmp.php` and `tmp/` are permitted ignored temporary local
state. They are not canonical source or release payload.

## Source-of-truth hierarchy

```text
effective code / schema / runtime configuration
    -> current canonical architecture/reference docs
    -> accepted decisions
    -> workflows
    -> historical snapshots/reports
```

Historical material explains evolution but does not override current accepted
implementation.
