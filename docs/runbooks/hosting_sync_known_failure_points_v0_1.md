# ForPrint Website — Hosting Sync Known Failure Points v0.1

**ID:** `FP-WEB-RUNBOOK-HOSTING-SYNC-FAILURE-POINTS-001`
**Version:** 0.1
**Date:** 2026-09-06
**Status:** active
**Scope:** canonical `make hosting-sync-full` during active local development

## Purpose

Keep the recurring hosting-sync traps visible to future operators and
assistants. Read this before changing sync, database authority, hosting runtime,
measurement, transport, or release-health sequencing.

## Active authority model

During active local development:

```text
local application code      -> authoritative
local project-managed media -> authoritative
local FULL database         -> authoritative
hosting application copy    -> disposable mirror
hosting runtime/env/secrets -> hosting-owned and protected
```

Canonical publication command:

```text
make hosting-sync-full
```

The direct full database mirror is intentional while the active development
mirror decision remains in force.

Canonical decision:

```text
docs/decisions/2026-09-06__active_development_hosting_mirror_authority.md
```

## Known failure point 1 — transient SSH reset

Observed symptom:

```text
kex_exchange_identification: read: Connection reset by peer
Connection reset by ... port 22
```

Rules:

- use canonical `scripts/operations/hosting_transport.py`;
- use its bounded transient retry / idempotent SSH path where appropriate;
- do not hardcode a second SSH transport, key, host, or retry policy;
- a transient SSH reset is not evidence of a website or measurement failure.

## Known failure point 2 — full DB mirror is intentional

`hosting-sync-full` intentionally replaces the hosting application database
from the validated local database package during the current development phase.

Do not "repair" this into production operational-row preservation merely
because rows exist on production.

The authority model changes only through an explicit superseding decision.

## Known failure point 3 — protected hosting runtime survives the mirror

Hosting-owned runtime/configuration remains protected, including the deployment
tool's protected set such as:

```text
.htaccess
.user.ini
php.ini where present
config.php
communication runtime/environment
credentials/secrets/provider tokens
```

Google measurement activation is production runtime state and must survive the
application mirror.

Do not copy local secrets to hosting and do not overwrite protected hosting
runtime as part of the application mirror.

## Known failure point 4 — communication acceptance is non-sending

Routine release health must not create a real enquiry.

Canonical rows:

```text
Telegram readiness
Email / SMTP readiness
```

A PASS means the existing non-sending communication acceptance passed. It does
not mean a real Telegram or email message was sent.

## Known failure point 5 — Google measurement runtime

Expected production configuration:

```text
enabled=true
testMode=false
provider=google-tag
googleTagId=AW-959055246
conversionDestination=AW-959055246/3ccOCP6mntocEI6LqMkD
```

Accepted browser consent state:

```text
consent=granted
ready=true
granted=true
gtag=function
loader=true
```

A real lead conversion remains a separate explicit E2E action because it writes
a request, may send notifications, and may fire a Google Ads conversion.

## Known failure point 6 — PRE and POST health

Canonical full sync runs:

```text
hosting-health-pre
...
production mutation
...
hosting-health-post
```

PRE measurement is advisory so a release can repair an already-broken runtime.

POST measurement is blocking. A blocking POST failure inside the mutation
boundary is rollback-eligible.

## Final operator-visible health table

A successful canonical full sync must finish by re-rendering the already
accepted POST evidence:

```text
make hosting-health-summary
```

Expected final rows:

```text
Telegram readiness       PASS
Email / SMTP readiness   PASS
Public HTTP              PASS
Google Ads measurement   PASS
Browser / consent E2E    MANUAL
```

The summary does not repeat deployment, database work, messaging, a provider
mutation, or a real lead. It is an operator-facing presentation of the accepted
POST evidence.

## Consent wording

Use familiar neutral affirmative wording while keeping the disclosure truthful.

Current consent controls:

```text
Відхилити
Прийняти cookies
```

The labels are intentionally short and familiar. The refusal control remains a
single-click action because non-essential Google Ads measurement must not be
made easier to accept than to refuse.

The explanatory copy remains responsible for disclosing that Google Ads
measurement loads only after consent.

## Fast diagnosis order

For a future full-sync failure, diagnose in this order:

```text
1. SSH transport / discovery
2. active development-mirror policy
3. protected hosting runtime
4. rollback snapshot
5. exact code/userfiles mirror
6. full database mirror
7. Telegram + Email/SMTP readiness
8. public HTTP
9. Google measurement server config
10. manual browser consent/provider verification
```

Do not redesign one layer to work around a failure in another layer.

<!-- FP_RELEASE_HEALTH_EVIDENCE_DIAGNOSTICS_RUNBOOK_V02_START -->
## Exact-run protected integration diagnosis — 2026-09-17

If release health stops on Telegram or Email/SMTP, use the exact evidence path
printed by that failed health run.

```text
make hosting-health-diagnose \
  HEALTH_REPORT=tmp/operator_reports/hosting_health/<exact-run>
```

Interpretation:

- `COMM_CHECK_TRANSPORT_OR_EXECUTION_TRANSIENT` — SSH/transport/process problem;
  do not change Telegram/SMTP configuration based on this result.
- `COMM_READINESS_PREDICATE_FAILURE` — the checker ran and exposed an actual
  false readiness predicate; use the accompanying `TG_*`, `SMTP_*` or `EMAIL_*`
  code.
- `COMM_CHECK_EXECUTION_FAILURE_UNCLASSIFIED` — checker returned non-zero but
  did not expose enough structured predicate evidence.
- `COMM_FAILURE_EVIDENCE_INCOMPLETE` — the evidence bundle is insufficient and
  must be investigated before release.
- `MEASUREMENT_*` — Google measurement state from that same PRE/POST run.

Keep two concepts separate:

- **preservation**: hosting-owned runtime/env/secrets/config must survive sync;
- **readiness**: the preserved integration must satisfy its release-health
  contract.

Do not bypass the gate. Diagnose the exact run, correct the relevant owner, and
then execute the canonical release-health command again.
<!-- FP_RELEASE_HEALTH_EVIDENCE_DIAGNOSTICS_RUNBOOK_V02_END -->
