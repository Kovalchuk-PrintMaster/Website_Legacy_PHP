# Local-first SEO launch roadmap v0.1

**ID:** `FP-WEB-SEO-ROADMAP-LOCAL-FIRST-001`
**Date:** 2026-07-29
**Status:** active plan

## Objective

Start controlled discovery and a small promotional presence for the current
HTTPS website without creating production/local divergence. All durable site
changes are prepared locally and deployed as reviewed releases.

## Track A — immediate external bootstrap, no production code changes

1. Keep `https://forprint.net.ua` as the public canonical host.
2. Verify a Search Console Domain property using DNS.
3. Inspect the home page and a small set of important current URLs.
4. Request indexing only for those representative public URLs.
5. Do not submit the current legacy sitemap.
6. Record an initial visibility baseline.
7. Resolve the Google Ads account/payments blocker.
8. Prepare, but do not broadly launch, a narrow paid-search pilot.

## Track B — local technical SEO implementation

1. Centralize canonical production-origin ownership.
2. Correct the sitemap generator and regenerate HTTPS canonical URLs.
3. Add or correct `robots.txt` with an HTTPS sitemap reference.
4. Confirm `<html lang="uk">`.
5. Define indexable and non-indexable routes.
6. Add controlled title and description ownership.
7. Align Open Graph URLs with canonical URLs.
8. Remove internal references to legacy hosts.
9. Validate representative routes, forms, mixed content and redirects.
10. Commit the accepted local checkpoint.

## Track C — controlled mirror release

1. Build an exact release inventory from the accepted commit.
2. Run PHP, frontend, metadata, sitemap and robots checks.
3. Upload to remote staging outside the webroot.
4. Create a timestamped production backup.
5. Install only approved files.
6. Run production HTTPS and form smoke tests.
7. Accept or roll back.
8. Submit the corrected HTTPS sitemap after acceptance.

## Track D — measured promotion

1. Use direct `https://forprint.net.ua/...` final URLs.
2. Keep campaign scope narrow by geography, intent and landing page.
3. Start with an approved small daily cap and explicit stop conditions.
4. Do not use misleading claims, stale prices or unsupported deadlines.
5. Review search terms, clicks, accepted leads and site stability.
6. Increase scope only after useful signal is observed.

## Acceptance principle

Search visibility and advertising results are not guaranteed. Each step
requires a written baseline, a measurable outcome and a rollback or pause
condition.
