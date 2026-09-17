# Organic search and measurement next-stage plan v0.1

**ID:** `FP-MKT-PLAN-2026-08-19-001`
**Date:** 2026-08-19
**Status:** active

## Starting point

The centralized head / Open Graph / structured-data release is accepted in production. Current accepted sitemap: **191 URLs**.

The earlier `marketing/reports/2026-08-18__seo_baseline_v0_3_after_banner_sitemap_release` is pre-head/schema-release evidence and is not the current post-release baseline.

Google Ads remains paused pending Google Support. Safe organic-search and measurement work continues.

<!-- FP_ORGANIC_PLAN_LIVE_OVERRIDE_2026_09_06_V2 -->
## Live status override — 2026-09-06

Google Ads is no longer paused pending Google Support. Measurement/runtime is
accepted and the Ads optimization workstream is closed until an explicit reopen
trigger.

Current organic/search priority remains:

```text
fresh production crawl
-> Search Console readiness
-> sitemap / representative URL inspection
-> current-evidence P1 selection
-> recurring read-only regression
```

This work may proceed in parallel with the local supplier-catalog pilot.

<!-- /FP_ORGANIC_PLAN_LIVE_OVERRIDE_2026_09_06_V2 -->

## Stage 1 — fresh post-release crawl

Run the canonical read-only crawler on production and store a new dated report under `marketing/reports/`. Compare it with v0.3.

Verify current production facts rather than forcing old issue counts:

- Open Graph global regressions removed;
- canonical ownership remains single;
- breadcrumb JSON-LD remains on eligible pages;
- sitemap membership reconciles with the 191-URL production set;
- requested/final HTTP status semantics remain correct.

## Stage 2 — Search Console readiness

Inspect the existing marketing control plane before adding provider configuration. Reuse stable project IDs, keep external provider IDs separate, store only symbolic `credential_ref`, and begin with least-privilege read access.

First read contract should capture, where available: property identity, sitemap status, clicks, impressions, CTR, average position, date/query/page/country/device dimensions, retrieval timestamp and provider/API version.

Do not put secrets or raw personal/user-level data into Git.

## Stage 3 — measurement source inventory

Inspect actual existing sources for Search Console, GA4/Analytics, Google Ads measurement, Business Profile and hosting analytics. Do not invent missing provider/account/property/customer IDs.

## Stage 4 — select remaining P1 work from fresh evidence

Prioritize current evidence, with likely review areas: heading hierarchy, image dimensions/aspect-ratio signals, image semantics, internal linking/crawl depth, title/description heuristics, representative HTML validity and Core Web Vitals/performance.

Heuristics are review signals, not ranking rules.

## Stage 5 — recurring read-only regression

Detect new 4xx/5xx, redirect chains, metadata/canonical/sitemap regression, missing H1, broken links/OG images, invalid JSON-LD, language/hreflang regression and representative HTML-validation regressions.

## Google Ads boundary

Until Google Support replies, do not alter the pending Ads payment/advertiser-verification recovery path. Resume Track B from `marketing/programs/forprint_growth_roadmap_v0_1.md` only after the reply.

## Completion criteria

Fresh post-release crawl stored and reviewed; Search Console readiness grounded in real project/provider identity; measurement inventory grounded in actual sources; next P1 batch selected from fresh evidence; recurring audit contract reproducible.
