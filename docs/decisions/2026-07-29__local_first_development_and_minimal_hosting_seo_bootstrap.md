# Local-first development and minimal-hosting SEO bootstrap

**ID:** `FP-WEB-ADR-2026-07-29-SEO-LOCAL-FIRST-001`
**Date:** 2026-07-29
**Status:** accepted

## Decision

The local repository and accepted Git commits remain the source of truth.
Production hosting is a controlled mirror/release target during the initial
month of active development.

SEO, metadata, sitemap, robots, analytics and interface work is implemented
and validated locally. Production receives only an accepted release package
with an exact file inventory, backup, validation, smoke tests and rollback.

## Minimal hosting actions allowed now

- maintain the accepted Let's Encrypt certificate;
- keep HTTP-to-HTTPS and `www`-to-primary redirects already enabled;
- verify Google Search Console through DNS;
- use URL Inspection for a small set of current public HTTPS URLs;
- resolve the existing Google Ads account/payments issue;
- run a limited campaign only after the account and destination gates pass;
- observe logs, resources and external availability.

## Production mutations deferred

- manual edits to PHP, CSS, JavaScript or templates;
- manual sitemap or robots edits on hosting;
- production-only metadata changes;
- database synchronization;
- product-media synchronization;
- new analytics tags before local consent/event validation;
- HSTS enablement;
- broad advertising spend.

## Current sitemap boundary

The current sitemap contains legacy non-canonical URLs. It is not submitted
to Search Console. Its generator and output are corrected locally and
released through the normal application release process.

## Legacy domain

`e-machine.com.ua` remains parked and inactive. It is not redirected, indexed,
advertised or included in the current launch.
