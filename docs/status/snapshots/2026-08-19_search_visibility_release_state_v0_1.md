# ForPrint search visibility release state — 2026-08-19

**ID:** `FP-WEB-STATUS-2026-08-19-SEO-001`
**Version:** v0.1
**Date:** 2026-08-19
**Status:** historical snapshot
**Publication:** completed

## Accepted production state

The guarded release completed exactly one canonical full local-to-hosting sync.

- favicon owner: single;
- Open Graph: canonical;
- breadcrumb schema owner: centralized JSON-LD;
- breadcrumb Microdata owner: removed;
- homepage business type: `Organization`;
- contacts business type: `LocalBusiness`;
- opening-hours coverage: 7 days;
- services/catalog breadcrumbs: accepted;
- representative Product schema: preserved;
- production sitemap: 191 URLs;
- communication acceptance: OK;
- hosting storage policy: OK.

Representative production routes accepted with HTTP 200: `/`, `/contacts/`, `/catalog/`, `/nashi-posluhy/`, `/product/eko-vzitki/`.

## Structured-data pre-release contract

- URLs: 191;
- breadcrumb pages: 190;
- `WebSite` pages: 1;
- `LocalBusiness` pages: 2;
- eligible product pages: 159;
- Product schema pages: 159;
- request-price product pages: 2;
- emitted availability: 0;
- currency: UAH.

## Release evidence

- guarded publish: `tmp/guarded_head_og_schema_publish_20260819_132145`;
- full-sync report: `tmp/full_hosting_sync_v1_20260819_132242`;
- rollback snapshot: `.runtime/backups/hosting/20260819_132255`.

## Next active stage

Fresh post-release crawl -> Search Console/measurement readiness -> select remaining P1 work from current evidence -> recurring read-only regression.

Google Ads remains a separate paused branch pending Google Support.
