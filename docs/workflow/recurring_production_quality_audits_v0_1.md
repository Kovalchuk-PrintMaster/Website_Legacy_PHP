# ForPrint recurring production quality audits v0.1

**ID:** `FP-WEB-WORKFLOW-RECURRENT-AUDIT-001`
**Version:** `v0.1`
**Date:** `2026-08-22`
**Status:** `planned`
**Scope:** recurring read-only production quality verification

## Purpose

Confirmed production defects and near-misses must be converted into permanent regression checks instead of remaining only in operator memory.

Canonical lifecycle:

```text
observed problem
→ identify canonical owner
→ fix the owner
→ add a reproducible regression check
→ register the check
→ include it in the recurring audit runner
→ generate a dated report
→ human review
```

The recurring audit program is an operational control. It does not replace focused acceptance checks during active implementation.

## Execution policy

Recurring production audits are read-only by default.

They may:

- inspect Git state and tracked source;
- read bounded non-secret production DB fields;
- fetch public routes;
- parse sitemap, robots, canonical metadata and structured data;
- verify media/runtime contracts;
- inspect backup manifests and backup freshness;
- write timestamped local reports.

They must not:

- mutate production source;
- mutate production DB;
- change Search Console, Google Ads or other Google configuration;
- auto-repair detected failures;
- expose credentials or secrets;
- assume optional tools exist on production.

Remote capabilities must be preflighted. The project Python environment owns orchestration; production should be queried through bounded SSH/PHP/shell probes when needed.

## Cadence

### Monthly

Run the consolidated production audit approximately once per month and write one dated report.

Target future entry point:

```text
scripts/inspection/run_monthly_production_quality_audit.py
```

Target report namespace:

```text
marketing/reports/YYYY-MM-DD__monthly_production_quality_audit_<timestamp>/
```

Preferred scheduler after implementation:

```text
systemd service + systemd timer
```

### After relevant releases

Run focused checks immediately after releases affecting the relevant owner.

Examples:

- sitemap generator → sitemap/product coverage;
- structured data → structured-data contract;
- media pipeline → media/rendition contract;
- routing/metadata → route/canonical/robots contract.

### Quarterly

Review this registry and separately verify restore readiness under the backup/restore policy.

## Initial regression registry

| Area | Regression contract | Origin / reason | Cadence |
|---|---|---|---|
| Sitemap product coverage | Every visible public product that is HTTP 200, canonical, crawlable and not `noindex` must appear exactly once in the live sitemap | 2026-08-22: 164 indexable products, 161 product URLs in sitemap; missing goods IDs 269, 270, 273 | monthly + after sitemap/catalog changes |
| Sitemap hygiene | Canonical indexable URLs only; no duplicates; accepted HTTP statuses | Search visibility contract | monthly |
| Robots ↔ sitemap | `robots.txt` declares `https://forprint.net.ua/sitemap.xml`; public product URLs remain crawlable | Search discovery contract | monthly + after robots changes |
| HTTP crawl health | Canonical sitemap URLs do not produce 5xx | Production crawl stability | monthly |
| Route metadata | Canonical, title, description, language, primary heading and robots policy remain valid | Metadata stabilization work | monthly + after metadata changes |
| Site identity / favicon | `ForPrint` remains the public identity; favicon keeps one stable canonical owner and crawlable square asset | Search identity work | monthly + after identity/media changes |
| Organization identity | Brand and legal entity remain separate; factual `legalName`, `taxID`, `vatID` stay consistent; descriptive `alternateName` does not reappear | Structured-data identity cleanup | monthly + after settings/schema changes |
| Structured data | JSON-LD matches visible facts; no fabricated price, availability or review claims | Search structured-data policy | monthly + after schema changes |
| Product price semantics | exact/range/request presentation and schema eligibility remain aligned to canonical DB ownership | Canonical price model | monthly + after pricing changes |
| Product images | Public image dimensions/aspect-ratio contracts remain valid | Image-dimension stabilization | monthly + after media/template changes |
| Search image renditions | Required product search renditions remain present and dimension-valid | Search rendition rollout | monthly + after product-media changes |
| Internal linking / orphan risk | Important canonical pages stay discoverable through crawlable links | Search architecture policy | monthly |
| Backup freshness | Latest verified backup evidence remains recent and internally consistent | Production protection requirement | monthly after backup system implementation |
| Restore readiness | Restore procedure and manifests remain testable without touching production by default | Disaster recovery | quarterly after backup system implementation |

## Incident-to-test rule

When a new production problem is confirmed:

1. record the symptom and canonical owner;
2. fix the owner rather than only the observed instance;
3. create a focused regression check;
4. add the check under `scripts/inspection/`;
5. register it here or in the future machine-readable registry;
6. include it in the monthly audit;
7. retain dated reports as evidence.

A recurring defect class is not considered operationally closed until a regression check exists, unless the architecture removes the condition entirely.

## Report contract

Each recurring report should record at least:

```text
timestamp
Git checkpoint
production target
audit version
individual check status
counts and mismatches
affected canonical owner
evidence paths or URLs
mutation classes = NONE
recommended next action
```

Statuses:

- PASS;
- WARN;
- FAIL;
- SKIP with explicit reason.

A failed audit must never auto-repair production.

## Known environment lesson

The 2026-08-22 sitemap-owner investigation proved that the production host must not be assumed to provide `python3`.

Reusable audits must therefore keep orchestration in the project Python environment and use bounded remote PHP/shell probes unless a production capability has been explicitly preflighted.

## Composition rule

The future monthly runner should reuse accepted focused checks rather than reimplementing them independently.

Candidate areas include:

- route metadata;
- structured data;
- local-business schedule;
- image dimensions;
- product media runtime;
- hosting/storage contracts;
- sitemap/product coverage.

## Completion definition

This program becomes operational only after all of the following exist:

- reviewed regression registry;
- one read-only consolidated runner;
- stable report format;
- verified production access/preflight strategy;
- bounded scheduler permissions;
- no automatic production repair;
- documented manual response workflow;
- verified backup/restore protection;
- successful manual dry run;
- successful scheduled run.
