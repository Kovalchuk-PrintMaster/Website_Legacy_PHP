# Local technical SEO implementation backlog v0.1

**ID:** `FP-WEB-SEO-BACKLOG-LOCAL-001`
**Date:** 2026-07-29
**Status:** planned

## Priority 0 — release safety

- preserve local Git as source of truth;
- no direct production edits;
- exact release inventory and rollback;
- keep database and product media deferred.

## Priority 1 — crawl and canonical foundation

- one canonical origin: `https://forprint.net.ua`;
- direct `www` and HTTP normalization;
- canonical metadata service;
- HTTPS-only internal links;
- correct sitemap generator;
- corrected sitemap output;
- `robots.txt` sitemap directive;
- valid 404 behavior.

## Priority 2 — indexation ownership

Expected indexable:

- home;
- approved categories;
- approved products/services;
- about and contact information;
- useful articles/news.

Expected non-indexable:

- admin;
- cart/checkout;
- internal search;
- previews and diagnostics;
- arbitrary filters/sorts;
- duplicate route variants.

## Priority 3 — page metadata

- `<html lang="uk">`;
- unique title;
- useful description;
- one visible primary heading;
- self-referencing canonical;
- Open Graph aligned with canonical;
- page-specific social image where available.

## Priority 4 — useful content and links

- understandable category hierarchy;
- crawlable links;
- product/service options in visible text;
- related content links;
- no orphan priority pages;
- no keyword-stuffed or near-duplicate pages.

## Priority 5 — media and performance

- useful `alt`;
- width/height or stable aspect ratio;
- responsive image variants;
- appropriate lazy loading;
- LCP/hero review;
- favicon and missing-resource cleanup.

## Priority 6 — later measurement

- consent UI;
- approved event contract;
- single `generate_lead` event after accepted storage;
- no PII in analytics;
- aggregate reporting and forecast comparison.
