# Search identity, Google Drive backup, and admin UI roadmap addendum v0.2

**ID:** `FP-WEB-PLAN-2026-08-21-SEARCH-IDENTITY-BACKUP-ADMIN-002`
**Version:** `v0.2`
**Date:** `2026-08-21`
**Status:** `planned`
**Supersedes:** `2026-08-21__search_identity_and_google_drive_backup_roadmap_addendum_v0_1.md`
**Scope:** public Google Search presentation, final off-site website backup, and post-production admin UI modernization
**Related policy:** `docs/architecture/search_visibility_and_web_quality_strategy_v0_1.md`

## Purpose

Add three explicit roadmap items that were not sufficiently visible in the current website roadmap:

1. improve ForPrint identity and direct-contact presentation in Google Search surfaces;
2. finish the public-production protection workstream with a verified off-site backup to Google Drive through the existing Cloud Backup Manager/rclone architecture;
3. only after public production/search work is complete, modernize the internal admin UI under one maintainable project-owned style architecture.

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

## Roadmap item C — admin UI modernization after public-production work

### Position in roadmap

This task is intentionally **last**.

It does not block public website quality, Google Search visibility, Google Ads, product media, structured data, or the final off-site backup.

Work on this item begins only after the public-production/search workstream and the verified Google Drive backup are complete.

### Goal

Bring the internal admin interface to one consistent, modern, maintainable visual system without changing business behavior merely for appearance.

### Visual direction

Use the strongest already-existing admin patterns as reference rather than inventing another unrelated style:

- product edit cards as reference for rounded inputs, radio controls, compact grouped fields, and practical content density;
- media-processing settings cards as reference for section grouping, headings, spacing, field help, and responsive card layout;
- consistent save/delete/action hierarchy;
- readable typography with less legacy visual weight;
- predictable spacing, borders, states, focus behavior, validation feedback, and responsive layout.

### Architecture requirement

Admin modernization must follow the same ownership discipline already adopted for the public frontend:

1. audit the current admin CSS cascade and identify all legacy owners;
2. define one canonical project-owned admin CSS entry point / ownership model;
3. avoid permanent layers of overrides on top of overrides;
4. migrate admin surfaces incrementally by bounded functional groups;
5. remove obsolete legacy rules only after the replacement owner is proven;
6. preserve PHP/admin behavior while visual ownership is migrated;
7. treat business-logic or schema changes as separate decisions rather than hiding them inside styling work.

### Functional cleanup to include before styling each area

Admin UI modernization is not only cosmetic. Before styling a screen, remove ambiguous duplicate ownership where it affects operator understanding.

Examples already identified:

- public/browser/Search favicon must have one canonical admin write path;
- `Visual Assets` remains a valid long-term admin domain;
- site-identity media must not be duplicated as unrelated header settings;
- confirm the exact meaning and public consumers of `mobile_header_img` / `mobile_logo_img`;
- if a distinct footer mobile/logo asset is actually needed, add it under the footer owner rather than inventing a generic duplicate setting;
- system settings should show only settings actually owned by that section.

### Migration sequence

The future admin workstream should proceed approximately as follows:

1. read-only inventory of admin templates, CSS files, inline styles, JS-owned states, and shared controls;
2. canonical admin CSS ownership decision;
3. common design tokens and base controls;
4. system settings / visual assets / header / footer administration;
5. product/category/filter editors;
6. information/news/marketing content editors;
7. utility and technical admin screens;
8. responsive/accessibility pass;
9. remove proven-dead legacy CSS;
10. final admin smoke/regression suite and documentation checkpoint.

### Boundaries

- admin redesign is not an SEO task;
- admin redesign is not a reason to publish unrelated public-site changes;
- no production-visible template should change unless explicitly required by a separate public feature;
- no database migration is implied by styling work;
- operator workflows and destructive-action guards must remain at least as safe as before;
- admin modernization should improve maintainability, not merely appearance.


## Priority order

Current order:

1. resolve the active Google Ads payments-profile correction and direct-advertiser verification reset;
2. continue the current search visibility / SERP quality work;
3. implement Search identity/direct-contact presentation;
4. complete remaining public release-quality work;
5. complete the verified Google Drive off-site backup as the final public-production protection checkpoint;
6. **last:** modernize the internal admin UI under one canonical project-owned style architecture.

The Google Drive backup remains the final public-production protection checkpoint. Admin UI modernization follows afterward as a separate non-public maintenance workstream and does not replace or delay current search/Ads priorities.
