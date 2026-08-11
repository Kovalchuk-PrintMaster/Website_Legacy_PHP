# Decision: progressive in-place frontend modernization

**ID:** `FP-WEB-ADR-2026-08-08-005`
**Date:** 2026-08-08
**Status:** accepted

## Context

Earlier project work considered a separate modern frontend preview while the
legacy publication path was stabilized. Current implementation instead has
project-owned `forprint-*` styles, shared layout/shell contracts, component
ownership and mobile portrait work inside the existing PHP website.

## Decision

ForPrint adopts progressive in-place modernization of the existing PHP website.

1. `base/` remains the website runtime/webroot.
2. Existing PHP business/application behavior remains unless separately
   migrated.
3. Project-owned `forprint-*` CSS/JS/template components are the actively
   maintained frontend layer.
4. Legacy global styles remain bounded compatibility assets while consumers
   require them.
5. New presentation behavior is implemented in project-owned files.
6. Mobile/tablet work proceeds as bounded responsive phases of the same site.
7. Stable migrated structures should prefer PHP/template ownership while
   JavaScript primarily owns behavior.
8. Legacy dependencies are removed incrementally after consumer/regression
   review.

The project therefore has one evolving public frontend architecture rather
than a legacy site plus a second canonical replacement frontend.
