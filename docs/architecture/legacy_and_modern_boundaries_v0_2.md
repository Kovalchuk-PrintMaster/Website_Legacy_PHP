# Legacy and modern boundaries v0.2

**ID:** `FP-WEB-ARCH-002-V02`
**Version:** 0.2
**Date:** 2026-08-08
**Status:** active canonical architecture
**Supersedes:** `legacy_and_modern_boundaries_v0_1.md`

## Current direction

ForPrint uses progressive in-place modernization of the existing PHP website.

```text
legacy
    compatibility / inherited behavior

project-owned ForPrint layer
    canonical maintained presentation and new behavior
```

Legacy assets may remain while real consumers still depend on them, but new
project presentation behavior belongs in project-owned `forprint-*` CSS/JS and
templates rather than inherited global styles.

## Mobile modernization

Mobile portrait Phase 1 belongs to the same canonical frontend layer and is
not a parallel replacement site. Stable structure should progressively move
toward maintainable PHP/template ownership while JavaScript primarily owns
behavior.

## Migration boundary

A component is migrated when project-owned markup/classes identify it, one
project-owned presentation owner is explicit, required JavaScript behavior has
an owner, inherited conflicts are bounded, functional behavior is validated,
and responsive behavior is reviewed for the affected scope.

Backend/data migrations remain separate decisions.

Communication hardening, hosting ownership, deployment profiles and database
ownership are current project-owned operational architecture, not legacy merely
because the site originated from an inherited PHP base.

When a legacy boundary changes materially, update current documentation and
preserve earlier records only as historical evidence.
