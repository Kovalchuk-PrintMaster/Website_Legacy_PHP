# Decision: canonical frontend CSS ownership and homepage layout

**Date:** 2026-07-20
**Status:** accepted working architecture decision
**Scope:** public frontend geometry, shared shell, homepage presentation

## Context

The website is being stabilized on top of inherited PHP templates and a large legacy stylesheet. Project-owned `forprint-*.css` files were introduced to isolate modern behavior.

During visual iteration, several accepted corrections were appended as new versioned blocks. This improved the current screenshot but created a second cascade problem:

- more than one project-owned block controlled the same hero selectors;
- older image, arrow, pagination, and spacing rules remained active;
- header width was calculated in both layout and shell files;
- inherited flex/height rules could remain effective when the modern file did not explicitly establish its own display model;
- responsive behavior differed between monitors.

## Decision

1. `forprint-layout.css` is the single owner of global usable width, side space, content ceiling, rail reservation, and shared container geometry.
2. `forprint-shell.css` is the single owner of header composition and responsive header behavior.
3. `forprint-home.css` is the single owner of homepage-specific presentation, including the hero slider.
4. Component refinements are merged into one canonical block before work moves to the next feature.
5. Permanent `v0.x` override layers for the same component are not accepted.
6. Legacy styles remain a fallback only; migrated components explicitly neutralize bounded conflicts.
7. The hero image uses centered cover-crop behavior.
8. Hero controls and pagination use one project-owned visual implementation.
9. Header and homepage content share one horizontal geometry contract.
10. Responsive acceptance requires review across the defined baseline viewport widths.

## Consequences

Positive:

- one place to change global width;
- one place to change the header;
- one place to change homepage geometry;
- fewer specificity surprises;
- clearer rollback;
- easier responsive testing;
- reduced risk of old arrows or spacing rules reappearing.

Costs:

- existing modern files require consolidation;
- legacy classes may remain in markup during transition;
- some CSS rules must explicitly reset inherited properties;
- visual testing remains necessary because the legacy stylesheet still loads.

## Rollback

Rollback is file-based:

- restore the previous project-owned CSS files from the working backup;
- do not modify the database;
- do not remove the legacy fallback as part of rollback;
- restore the previous asset versions if cache-busting identifiers changed.

## Deferred work

- homepage media upload/renaming/compression;
- final responsive acceptance;
- PHP 8.2 warning cleanup;
- further reduction of legacy stylesheet dependence;
- final publication/deployment decision.
