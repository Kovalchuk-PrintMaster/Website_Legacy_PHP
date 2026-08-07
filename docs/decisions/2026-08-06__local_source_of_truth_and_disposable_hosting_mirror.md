# Local source of truth and disposable hosting mirror

**ID:** `FP-WEB-ADR-LOCAL-MIRROR-001`
**Date:** 2026-08-06
**Status:** superseded

## Context

ForPrint is in a controlled preparation and testing period. All application
work, content editing, product setup, media preparation and database changes
are performed on the local server first. The hosting copy does not receive
independent editorial or client data during this period.

Incremental file-only deployment created hybrid states:

- application code was newer than the hosting database;
- `userfiles/` remained older than the CSS and templates that referenced it;
- working-hours data and products differed between environments;
- the public site could contain old files that no longer existed locally.

## Decision

Until an explicit production cutover decision:

1. The local server is the only application and data source of truth.
2. Hosting is a disposable public mirror of the accepted local state.
3. A hosting reset mirrors:
   - application PHP;
   - templates;
   - CSS and JavaScript;
   - `vendor/`;
   - `userfiles/`;
   - the complete local database.
4. Hosting-only application files that are absent locally are deleted.
5. Only the hosting environment pack is preserved:
   - `base/config.php`;
   - `base/mail.php` when present;
   - `base/.htaccess`;
   - `base/.user.ini`;
   - `base/php.ini` when present;
   - the private communication runtime outside webroot.
6. Runtime logs, cache, sessions and temporary files are not canonical data.
7. Every reset creates a full hosting webroot backup and a complete database
   dump before mutation.
8. Acceptance requires exact file and database fingerprints, public route
   checks, the contacts schedule, logo SVG masks and communication endpoint
   compatibility.
9. Any failed acceptance restores both the previous webroot and database.
10. Secrets remain outside Git, reports and deployment payloads.

## Communication runtime

The versioned local `communication-request.php` owns the endpoint logic.
`CommunicationRuntimeBootstrap.php` loads environment values already supplied
to the process and, on hosting, may load the private runtime file outside
webroot. This removes the need for a separate hosting-only endpoint fork.

## Exit condition

This policy ends only after an explicit production cutover. At that point the
data direction, backup frequency, migration model and production write
ownership must be reviewed because hosting may begin receiving real customer
or administrative data.

## Consequences

Positive:

- one testable source of truth;
- no code/database/media hybrids;
- deterministic recovery;
- simple parity checks;
- old hosting application files cannot silently survive.

Costs:

- every reset is intentionally destructive to hosting application data;
- the reset is heavier than an incremental release;
- production cutover requires a new operating decision.

<!-- FP_DEPLOYMENT_OWNERSHIP_SUPERSESSION_V0_1_START -->
## Supersession

This historical pre-launch decision is retained for context but is no longer the
active database ownership model.

It is superseded by the deployment ownership-policy decisions recorded on
2026-08-07. Local remains canonical for application files, database schema and
non-operational database content. Production owns operational row content, and
hosting owns its protected runtime/environment state.
<!-- FP_DEPLOYMENT_OWNERSHIP_SUPERSESSION_V0_1_END -->
