# ForPrint recurring production quality audit roadmap v0.1

**ID:** `FP-WEB-PLAN-RECURRENT-AUDIT-001`
**Version:** `v0.1`
**Date:** `2026-08-22`
**Status:** `planned`
**Purpose:** place recurring regression-audit automation at the final operational stage of the website roadmap

## Position in the roadmap

This plan is intentionally late.

Order:

1. finish current public/customer-visible and search-quality work;
2. complete the remaining production-release quality tasks;
3. implement and verify backup/restore protection;
4. complete any intentionally deferred internal/admin modernization work;
5. then operationalize the recurring production quality audit program;
6. run the consolidated read-only audit approximately once per month.

The current sitemap defect is fixed now. Monthly automation is deferred; the regression case is preserved now so it cannot be forgotten.

## Phase A — build the regression registry

Status: planned.

Deliverables:

- keep the canonical workflow registry current;
- convert each confirmed recurring defect class into a focused check;
- identify the canonical owner for each check;
- classify PASS/WARN/FAIL/SKIP semantics.

## Phase B — consolidate focused checks

Status: planned after main implementation work.

Target:

```text
scripts/inspection/run_monthly_production_quality_audit.py
```

Rules:

- orchestration in the project Python environment;
- no assumption that production has Python, `rg`, Node.js or Composer;
- bounded SSH/PHP/shell probes only;
- read-only by default;
- no production self-healing.

## Phase C — stable reporting

Status: planned.

Target report namespace:

```text
marketing/reports/YYYY-MM-DD__monthly_production_quality_audit_<timestamp>/
```

Each report records:

- Git checkpoint;
- production target;
- audit version;
- result per check;
- counts and mismatches;
- evidence;
- next action;
- mutation classes = NONE.

## Phase D — scheduler

Status: deferred until backup/restore protection is verified.

Preferred implementation:

```text
systemd service + systemd timer
```

Initial cadence:

```text
approximately monthly
```

The service must run with bounded permissions and must not have authority to repair production.

## Phase E — operational review

Status: planned.

- monthly human review of reports;
- focused check after relevant releases;
- quarterly registry review;
- quarterly restore-readiness evidence review under the backup policy.

## First registered production regression

On 2026-08-22 the product/sitemap audit found:

```text
public indexable products = 164
product URLs in live sitemap = 161
missing goods IDs = 269, 270, 273
```

Permanent contract:

> Every visible public product that returns HTTP 200, is canonical, crawlable and not `noindex` must be present exactly once in the canonical live sitemap.

This regression check must remain in the future monthly audit even after the current sitemap owner is fixed.
