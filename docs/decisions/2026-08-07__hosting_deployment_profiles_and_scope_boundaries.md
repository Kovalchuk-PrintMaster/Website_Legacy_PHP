# Decision: Explicit Hosting Deployment Profiles and Scope Boundaries

**ID:** `FP-WEB-ADR-2026-08-07-002`
**Date:** 2026-08-07
**Status:** accepted

## Context

The project now needs both complete hosting rebuilds and frequent small
responsive/frontend releases.

Running a full application/database mirror for every CSS/JS/template change is
unnecessary and increases transfer time and operational risk.

The repository already has two useful primitives:

- exact-manifest file deployment with backup/acceptance/rollback;
- full local-to-hosting mirror with complete database synchronization.

## Decision

Adopt explicit deployment profiles:

```text
full
code
frontend
backend
dependencies
database
media
manifest
```

No profile may infer release scope from the whole dirty working tree.

Frontend deployment must not change database/backend/runtime state.

Database deployment must not change application files.

Hosting environment and external communication runtime remain preserved in all
profiles.

Backend/code/full changes must pass non-sending communication readiness gates.

## Consequences

- mobile/frontend iteration becomes small and fast;
- database refresh can be performed without rewriting the webroot;
- backend and dependency releases become explicit;
- full mirror remains available for canonical rebuilds;
- point releases remain exact-manifest based;
- rollback responsibility stays aligned with the mutated state class.
