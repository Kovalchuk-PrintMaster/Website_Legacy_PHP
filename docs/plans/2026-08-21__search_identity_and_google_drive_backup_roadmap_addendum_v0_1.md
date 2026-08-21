# Search identity and Google Drive backup roadmap addendum v0.1

**ID:** `FP-WEB-PLAN-2026-08-21-SEARCH-IDENTITY-BACKUP-001`
**Date:** `2026-08-21`
**Status:** `planned`
**Scope:** public Google Search presentation and final off-site website backup
**Related policy:** `docs/architecture/search_visibility_and_web_quality_strategy_v0_1.md`

## Purpose

Add two explicit roadmap items that were not sufficiently visible in the current website roadmap:

1. improve ForPrint identity and direct-contact presentation in Google Search surfaces;
2. finish the website workstream with a verified off-site backup to Google Drive through the existing Cloud Backup Manager/rclone architecture.

These items are separate from the current Google Ads payments-profile / advertiser-verification support case, which remains the immediate operational priority.

## Roadmap item A — Google Search identity and direct contact presentation

### Goal

Make ForPrint easier to recognize and contact directly from Google Search where Google supports such presentation.

### Planned work

- audit the current site-name signal on the production home page;
- define one preferred `WebSite.name` and controlled `alternateName` values;
- keep the preferred site name consistent with visible homepage branding and `og:site_name`;
- audit and, if needed, improve the crawlable site favicon;
- keep one stable favicon URL and a search-suitable square asset;
- audit `Organization` / `LocalBusiness` identity markup for truthful business contact data;
- expose the canonical business telephone through the appropriate structured business identity where supported;
- audit the Google Business Profile phone number and contact settings;
- verify that users can reach the business directly from Google Business Profile / Search / Maps surfaces where Google renders a call action;
- validate the homepage and identity markup after release through Search Console / URL Inspection and structured-data checks;
- monitor what Google actually selects instead of treating a preferred presentation as guaranteed.

### Important presentation boundary

Google controls the final Search UI.

The website can provide strong identity/contact signals, but it cannot force:

- a specific decorative rendering;
- a particular site-name treatment;
- a favicon on every result;
- a clickable phone number inside every standard organic result.

The roadmap target is therefore **eligibility and strong consistent signals**, not a fabricated guarantee.

### Address policy

Making the street address prominent in Search is **not a goal of this roadmap item**.

Address handling must remain truthful and compliant with the actual Google Business Profile business type:

- storefront/hybrid profiles follow Google's storefront/location rules;
- service-area businesses may hide the street address and show a service area instead;
- we do not hide or falsify an address merely for marketing appearance when Google requires it for the actual business model.

The main desired quick-contact signal is the telephone, not address prominence.

## Roadmap item B — final off-site website backup to Google Drive

### Goal

Close the website stabilization/release workstream with a verified recoverable off-site copy in Google Drive.

### Existing foundation to reuse

The separate ForPrint Cloud Backup Manager already has prior work around:

- `rclone`;
- Google Drive as the first cloud provider;
- provider/account grouping;
- Google Drive targets;
- OAuth2 authorization;
- Google Drive API / Google Cloud project setup;
- checksum, manifest, restore-readiness, and guarded operator workflows.

Do not create a competing backup implementation inside the PHP website.

### Planned website backup scope

Before execution, define the exact recoverable set. It should normally include, as applicable:

- production website application files;
- `userfiles/` canonical website media;
- a fresh database dump produced by an approved database-backup procedure;
- required deployment/runtime configuration references;
- a manifest and checksums.

Secrets must not be copied into a broadly accessible archive without an explicit protected-secret backup policy.

### Execution sequence

1. inspect the existing dedicated Google account, Google Cloud project, Drive API state, OAuth consent/credentials, and current `rclone` remote;
2. do not recreate credentials or re-authorize if the existing configuration is healthy;
3. run a read-only provider and remote preflight;
4. calculate source inventory and projected archive size;
5. verify available Google Drive capacity against the planned archive;
6. perform only an explicitly approved guarded write test if still required;
7. create the website backup artifact with manifest/checksum;
8. upload to the approved Google Drive target;
9. verify remote size/checksum;
10. perform a controlled download/readability or restore verification;
11. record the final backup artifact, target, checksum, date, and restore path;
12. only then mark the website backup roadmap item complete.

### Ownership boundary

The website repository documents the requirement and the recoverable website scope.

Actual cloud authorization, `rclone`, provider validation, upload, retention, restore testing, and operator safety belong to the Cloud Backup Manager project.

## Priority order

Current order:

1. resolve the active Google Ads payments-profile correction and direct-advertiser verification reset;
2. continue the current search visibility / SERP quality work;
3. implement Search identity/direct-contact presentation;
4. complete remaining release-quality work;
5. finish with the verified Google Drive off-site backup.

The Google Drive backup is intentionally the final operational protection checkpoint, not a replacement for current search/Ads work.
