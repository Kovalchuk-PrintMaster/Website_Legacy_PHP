# Decision: canonical public metadata and breadcrumb structured-data ownership

**ID:** `FP-WEB-ADR-2026-08-19-SEO-001`
**Date:** 2026-08-19
**Status:** accepted
**Scope:** public search metadata, Open Graph, structured data, breadcrumbs

## Context

The search-quality architecture requires one controlled metadata model. Visible breadcrumbs and structured breadcrumb semantics must share one canonical data trail without competing owners.

## Decision

1. Breadcrumb data is built once by `BaseUser::buildBreadcrumbItems()`.
2. `base/templates/default/include/breadcrumbs.php` renders visible navigation only.
3. `base/templates/default/include/structuredData.php` is the canonical structured-data owner and emits `BreadcrumbList` as JSON-LD from the same breadcrumb data.
4. Breadcrumb Microdata is not kept as a second structured-data owner.
5. Title, description, canonical URL and Open Graph remain centralized through the public head owner.
6. `/` emits `Organization`; `/contacts/` emits factual `LocalBusiness` contact/address/opening-hours data.
7. Product structured data remains factual and data-driven.
8. Release acceptance validates persistent contracts and representative production routes.

## Production acceptance

Accepted production publish: **2026-08-19**.

Evidence:

- `tmp/guarded_head_og_schema_publish_20260819_132145`;
- `tmp/full_hosting_sync_v1_20260819_132242`;
- rollback snapshot `.runtime/backups/hosting/20260819_132255`;
- production sitemap: **191 URLs**;
- full-sync executions: **1**;
- communication acceptance: **OK**;
- hosting storage policy: **OK**.

## Deferred

Historical public-alias migration, multilingual/hreflang rollout, Search Console read automation, and the next P1 web-quality batch remain separate work.

## Rollback

The guarded release created an exact off-host rollback snapshot before mutation:

`.runtime/backups/hosting/20260819_132255`

Persistent deployment backup archives on hosting remain forbidden.
