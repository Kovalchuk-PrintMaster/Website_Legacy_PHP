# Decision: communication runtime contract and deployment acceptance

**ID:** `FP-WEB-ADR-2026-08-07-003`
**Date:** 2026-08-07
**Status:** accepted

## Context

The production forms failed through several independent contract mismatches: missing security runtime prerequisites, bootstrap allowlist lag, CSRF issuer/verifier runtime divergence and lost boolean normalization.

## Decision

1. Production communication state remains hosting-owned and outside the public webroot.
2. `CommunicationRuntimeBootstrap.php` is the single application runtime publication boundary.
3. Security secret and security directory are required contract members.
4. Boolean runtime flags normalize to canonical `0`/`1`.
5. CSRF/idempotency issuers load the same runtime before token generation.
6. `communication-request.php` loads the same runtime before verification and delivery.
7. Deployment acceptance is layered: low-level runtime readiness + safe predicates + production CSRF issuer/verifier parity.
8. Normal acceptance sends no test email or Telegram message.
9. Post-install acceptance failure remains rollback-eligible.
10. Persistent deployment authorization stays off; operator deploy commands grant temporary authorization and restore `0`.
11. Exact manifests remain preferred for narrow repairs.

## Canonical references

```text
scripts/inspection/check_website_communication_runtime.py
scripts/inspection/check_website_communication_acceptance.py
scripts/operations/hosting_release_authorized.py
base/libraries/CommunicationRuntimeBootstrap.php
base/libraries/CommunicationRequestSecurity.php
base/templates/default/include/communicationRequestForm.php
base/templates/default/include/productCommunicationButtons.php
base/communication-request.php
docs/workflow/communication_release_safety_and_recovery_v0_1.md
```
