# Marketing API automation policy v0.1

- **ID:** `FP-WEB-POL-MARKETING-AUTOMATION-001`
- **Date:** 2026-08-11
- **Status:** active working policy

## Goal

Maximize useful API automation while preserving safety, reproducibility,
reviewability, and provider-account integrity.

Routine reporting, analysis, campaign preparation, and eventually bounded
routine mutations should not require repeated provider-dashboard work.

## Read path

Scheduled reads require explicit source identity, appropriate credential scope,
versioned query contract, known destination/retention policy, provenance, and
observable failure handling.

## Write path

All mutation tools implement:

1. discover current remote state;
2. create an immutable operation plan;
3. preview semantic diff and validate where supported;
4. satisfy write authorization;
5. apply only the approved plan;
6. read remote state back;
7. persist outcome evidence without secrets.

## Write modes

- `disabled`;
- `preview_only`;
- `operator_approved`;
- `policy_managed`.

`policy_managed` requires versioned limits, cadence, spend/risk limits, stop
conditions, verification, incident behavior, and evidence requirements.

## Plan integrity

Apply consumes a previously generated plan with stable ID/checksum. Changing
the operation invalidates previous authorization.

## Provider validation

Use provider validation-only/equivalent capability when available, but retain a
local semantic preview because provider validity is not project intent.

## Partial failure

Adapters explicitly choose all-or-nothing versus partial-failure behavior.
Silent partial success is forbidden.

## Verification

A successful API response is not final evidence. Read back affected resources
and compare observed state with approved intent.

## Credentials

Secrets never appear in Git, operation plans, reports, CSV evidence, command
examples, or coordination reports. Registries store symbolic references only.

## Audit evidence

Record operation/plan ID, source, safe target identifiers, timestamp,
adapter/API version, intended semantic diff, authorization mode, provider
request/result IDs where safe, verification result, and repair outcome.

Provider dashboards remain available for exceptional/manual operations, but
are not the target interface for routine repeatable work.
