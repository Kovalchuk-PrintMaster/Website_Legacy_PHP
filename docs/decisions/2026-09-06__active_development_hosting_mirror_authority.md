# Decision: active development hosting mirror authority

**ID:** `FP-WEB-DECISION-DEVELOPMENT-HOSTING-MIRROR-001`
**Date:** 2026-09-06
**Status:** accepted
**Scope:** canonical local-to-hosting synchronization while ForPrint Website is still under active local development

## Context

ForPrint Website is still being developed primarily on the local development
server. The public hosting instance currently acts as a publication mirror of
that development state.

At this stage the hosting site is not the authoritative workplace for catalog,
content, media, inventory, order/accounting data, or other business records.
The project is not yet in the later lifecycle where administrators maintain
canonical production content directly on hosting.

Repeated attempts to apply a future production-data-preservation model to the
current development sync create confusion and can block the intended release
workflow.

## Decision

While this decision is active, **local development state is authoritative** for:

- application/PHP source;
- templates, CSS and JavaScript;
- project-managed `userfiles` and site media;
- the entire application database, including all current rows;
- catalog/content/configuration data stored in that database.

The canonical development publication command is:

```text
make hosting-sync-full
```

For that command, production hosting is intentionally a disposable mirror for
application-owned files, media and database content.

A full sync therefore intentionally replaces the production database from the
validated local database package. This is not a defect and must not be
"corrected" into operational-row preservation while this decision is active.

## Hosting-owned state that must survive

Full mirroring does **not** authorize overwriting hosting-specific runtime
state. Existing deployment protection continues to preserve the hosting-owned
contract, including as applicable:

```text
base/config.php
.htaccess
.user.ini
php.ini
mail/runtime configuration
communication runtime/environment
measurement runtime/environment activation
credentials, secrets and provider tokens
hosting/server-specific configuration
```

The exact protected set remains owned by the deployment/hosting tooling. This
decision does not authorize copying local secrets to hosting.

## Communication and measurement

Telegram, Email/SMTP and Google measurement are production-runtime capabilities.
Their hosting-specific activation/configuration must survive a full application
mirror and must be checked by the release-health gates.

The site data behind forms may be replaced from local because the production
database is not authoritative in the current development phase. Runtime
credentials and provider configuration remain hosting-owned.

## Targeted deployment profiles

The repository also contains narrower `hosting-deploy-*` profiles with their
own preservation semantics. Those commands remain useful for explicitly scoped
point releases.

They do not change the authority model of the canonical development publication
path:

```text
make hosting-sync-full
```

When the operator asks to publish the current complete local site during active
development, `hosting-sync-full` is the intended mirror operation.

## Transition to production-maintenance mode

This policy must **not** change automatically because:

- the public site receives traffic;
- enquiry forms exist;
- rows appear in the production database;
- a future assistant assumes production data should normally be preserved.

The authority model changes only after the project explicitly decides that
active local development has ended and production/admin activity becomes
authoritative.

That transition requires an **explicit replacement decision** that:

1. names which production tables/rows become authoritative;
2. changes the sync/database ownership contract;
3. updates Makefile/help text and permanent checkers;
4. updates `AGENTS.md` and current project state;
5. changes backup/rollback and release acceptance where required;
6. explicitly supersedes this decision.

Until then, future assistants must treat full local database replacement during
`make hosting-sync-full` as intentional project policy.
