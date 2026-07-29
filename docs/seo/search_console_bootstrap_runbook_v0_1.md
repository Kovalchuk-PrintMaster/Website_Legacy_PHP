# Search Console bootstrap runbook v0.1

**ID:** `FP-WEB-SEO-WF-SC-BOOTSTRAP-001`
**Date:** 2026-07-29
**Status:** ready

## Scope

This runbook starts Google discovery without editing production application
files.

## Steps

1. Open Google Search Console.
2. Add a **Domain property** for `forprint.net.ua`.
3. Copy the TXT verification value.
4. Add the TXT record in the authoritative DNS zone.
5. Wait for DNS propagation and confirm verification.
6. Open URL Inspection for:
   - `https://forprint.net.ua/`
   - `https://forprint.net.ua/about/`
   - one current category URL;
   - one current product or service URL;
   - one current contact route.
7. Run the live test for each URL.
8. Request indexing only when the page returns successfully and the canonical
   is the HTTPS primary domain.
9. Record the request date and result in the execution register.

## Sitemap rule

Do not submit the current `sitemap.xml`, because the accepted audit found
legacy `http://cpa.fvds.ru` URLs. A sitemap is submitted only after the local
generator is corrected, deployed and verified on production.

## Monitoring

During the initial bootstrap:

- check Page indexing weekly;
- check HTTPS and manual actions;
- check the selected URLs through URL Inspection;
- do not repeatedly request indexing every day;
- treat Search Console data as delayed and directional.
