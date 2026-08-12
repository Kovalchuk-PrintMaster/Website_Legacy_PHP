# Decision: Marketing control plane and API-first automation

- **ID:** `FP-WEB-ADR-2026-08-11-001`
- **Date:** 2026-08-11
- **Status:** accepted
- **Scope:** marketing/search/ads/local-presence/measurement

## Context

The website requires sustained promotion work across organic search, Google
Ads, Analytics, Search Console, Business Profile, hosting analytics, campaign
adaptation, reporting, experimentation, and future automation.

The inherited `seo/` umbrella is too narrow for this long-term domain.

## Decision

1. Introduce `marketing/` as the umbrella workspace.
2. Treat SEO/organic search as one subdomain.
3. Introduce `config/marketing/` as the machine-readable control plane.
4. Use Python as the canonical automation language.
5. Isolate provider adapters from project business logic.
6. Prefer API/report automation over repetitive dashboard operation.
7. Require plan/preview/authorize/apply/verify/evidence for mutations.
8. Support manual, assisted, and managed modes.
9. Keep credentials outside Git.
10. Use raw/staged/curated/report data zones.
11. Classify existing SEO assets before migrating them.
12. Keep ADRs centralized and snapshots historical.

## Consequence

There is an upfront controlled migration cost, but the repository gains a
stable domain able to support new providers, campaigns, reports, and API
automation without repeated top-level redesign.
