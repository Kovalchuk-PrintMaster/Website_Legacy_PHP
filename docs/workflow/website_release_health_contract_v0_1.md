# ForPrint Website — Production Release Health Contract v0.1

**ID:** `FP-WEB-WORKFLOW-RELEASE-HEALTH-001`
**Version:** 0.1
**Date:** 2026-09-06
**Status:** active
**Scope:** read-only pre/post production acceptance around hosting releases

## Purpose

Every important hosting update must make known fragile production integrations
visible instead of relying on operator memory.

Canonical standalone command:

```text
make hosting-health-check
```

The canonical full local-to-hosting sync runs the same health registry before
and after mutation. The post phase renders a combined **Before / After** table.

## Health classes

- `BLOCKING` — failure blocks acceptance; a post-mutation failure is
  rollback-eligible in the canonical full-sync owner.
- `ADVISORY` — visible warning that does not block the current phase.
- `MANUAL` — browser/provider behavior intentionally not automated.

Google measurement is `ADVISORY` during PRE because a release may intentionally
repair an already-broken production measurement activation. It becomes
`BLOCKING` during POST and standalone production health.

## Initial registry

| Check | PRE | POST | Reason |
|---|---|---|---|
| Telegram readiness | BLOCKING | BLOCKING | enquiry delivery runtime must survive release |
| Email / SMTP readiness | BLOCKING | BLOCKING | enquiry delivery runtime must survive release |
| Public HTTP | BLOCKING | BLOCKING | key public routes must return HTTP 200 |
| Google Ads measurement | ADVISORY | BLOCKING | production must render the expected consent-aware Google Ads runtime |
| Browser / consent E2E | MANUAL | MANUAL | consent and controlled conversion need explicit browser/provider review |

Telegram and Email rows currently reuse the existing non-sending
`hosting-communication-check`. A PASS means that acceptance verified its SMTP
and Telegram readiness predicates. It does **not** mean a real message was sent.

## Google measurement contract

Production HTML must render:

```text
enabled = true
testMode = false
provider = google-tag
googleTagId = AW-959055246
conversionDestination = AW-959055246/3ccOCP6mntocEI6LqMkD
```

The automated checker does not click consent, load the provider in a headless
browser, create a real lead, or write to Google Ads.

## Terminal UX

The checker prints a colored table:

```text
green   PASS
yellow  WARN
red     FAIL
cyan    MANUAL
```

POST uses the latest PRE snapshot to show:

```text
Check | Before | After | Gate | Details
```

The terminal output also includes the absolute manual-runbook path and an OSC-8
hyperlink when the terminal supports it.

## Evidence

Each invocation writes:

```text
tmp/operator_reports/hosting_health/<timestamp>_<phase>/health.json
tmp/operator_reports/hosting_health/<timestamp>_<phase>/summary.md
tmp/operator_reports/hosting_health/<timestamp>_<phase>/communication_acceptance.log
```

The latest PRE state used by POST comparison is local runtime evidence:

```text
tmp/operator_runtime/hosting_health/latest_pre.json
```

## Full-sync integration

Canonical owner:

```text
scripts/maintenance/sync_local_to_hosting_full.py
```

The sync remains responsible for production backup, exact code/userfiles
transfer, database handling, acceptance sequencing and automatic rollback.

The health checker only inspects.

## Extending the registry

When a repeatable production weak point is discovered:

1. classify it as `BLOCKING`, `ADVISORY`, or `MANUAL`;
2. add the smallest read-only check;
3. automate only what can be checked safely and deterministically;
4. put browser/provider actions into the manual runbook;
5. add the new row to this contract;
6. change full-sync acceptance only when the new check changes release semantics.

A health row should protect a real production capability or safety boundary,
not merely expose another metric.

<!-- FP_FINAL_HEALTH_TABLE_CONTRACT_V1 -->
## Final operator-visible table

The blocking POST health check remains inside the full-sync rollback boundary.

After successful acceptance, the canonical full sync performs a **read-only
re-render** of the already accepted POST evidence:

```text
make hosting-health-summary
```

It does not repeat deployment, database work, messaging, provider mutation, or
a real lead.

A successful `make hosting-sync-full` should therefore finish with:

```text
Telegram readiness       PASS
Email / SMTP readiness   PASS
Public HTTP              PASS
Google Ads measurement   PASS
Browser / consent E2E    MANUAL
```

Troubleshooting:
`docs/runbooks/hosting_sync_known_failure_points_v0_1.md`.
<!-- /FP_FINAL_HEALTH_TABLE_CONTRACT_V1 -->

<!-- FP_RELEASE_HEALTH_EVIDENCE_DIAGNOSTICS_V02_START -->
## Evidence-bound protected integration diagnostics — 2026-09-17

Telegram, Email/SMTP and Google measurement are separate protected release
controls. A failed release-health run is the authority for diagnosis.

Do **not** explain a previous failure by re-running the communication checker:
a later run may observe a different transient state.

Canonical diagnosis:

```text
make hosting-health-diagnose \
  HEALTH_REPORT=tmp/operator_reports/hosting_health/<exact-run>
```

`HEALTH_REPORT` may point to the exact run directory, `summary.md`, or
`health.json`. Without it, the diagnostic searches for the latest failed
hosting-health evidence. It does not perform a fresh SSH/hosting check.

The exact run persists:

```text
communication_acceptance.log
communication_acceptance.meta.json
health.json
summary.md
```

`communication_acceptance.meta.json` records the health phase, health run ID,
checker return code and evidence path. It contains no secrets.

Diagnostic error classes:

```text
COMM_CHECK_TRANSPORT_OR_EXECUTION_TRANSIENT
COMM_READINESS_PREDICATE_FAILURE
COMM_CHECK_EXECUTION_FAILURE_UNCLASSIFIED
COMM_FAILURE_EVIDENCE_INCOMPLETE
MEASUREMENT_RELEASE_HEALTH_FAILURE
MEASUREMENT_EVIDENCE_INCOMPLETE
```

Predicate failures are further mapped to stable `TG_*`, `SMTP_*` and `EMAIL_*`
codes. Transport/process failures must not be misreported as Telegram/SMTP
configuration failures.

Google measurement is read from the **same exact health run**, never from an
unrelated latest report.

Reports expose booleans, control IDs and evidence paths only. Telegram tokens,
SMTP passwords and other secret values must never be printed.
<!-- FP_RELEASE_HEALTH_EVIDENCE_DIAGNOSTICS_V02_END -->
