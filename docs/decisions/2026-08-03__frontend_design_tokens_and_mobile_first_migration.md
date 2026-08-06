# Decision: design tokens and mobile-first legacy migration

**Date:** 2026-08-03
**Status:** accepted working direction
**Scope:** ForPrint public frontend visual system

## Context

The inherited `style.css` remains a temporary compatibility layer. The visual
audit identified extensive variation in colors, typography, spacing,
breakpoints and selector specificity. Project-owned files also accumulated
temporary `!important` declarations while isolating inherited rules.

The website appearance is already close to the desired result. The goal is not
a redesign. The goal is to describe the accepted appearance through a small,
canonical and maintainable visual system.

## Decision

1. New shared values are owned by `forprint-tokens.css`.
2. Shared semantic roles are owned by `forprint-foundation.css`.
3. New component work is not added to `style.css`.
4. New project-owned CSS starts with zero `!important`.
5. `!important` is permitted only as a temporary documented legacy-isolation
   exception with an explicit removal task.
6. Migrated markup uses semantic `fp-` classes.
7. Surface-specific geometry stays in its surface owner.
8. New and migrated components use mobile-first `min-width` media queries.
9. The initial breakpoint contract is:
   - base: narrow/mobile;
   - 36rem: wide mobile;
   - 48rem: tablet;
   - 64rem: compact desktop;
   - baseline review additionally covers 1366, 1600 and 1920 px.
10. Interactive controls provide a minimum 2.75rem target.
11. Form controls use at least 1rem text.
12. Bottom floating components account for safe-area insets.
13. `style.css` is removed route-by-route only after a surface passes
    functional and responsive acceptance without it.

## Initial pilot

The first migrated pilot consists of:

- consent UI;
- `/nashi-posluhy/`;
- page title;
- section title;
- card title;
- body copy;
- buttons;
- breadcrumb-to-title rhythm.

## Acceptance

The pilot is accepted only when:

- the route returns HTTP 200;
- tokens and foundation load after `style.css`;
- the four pilot CSS files contain no `!important`;
- no horizontal overflow is visible;
- consent remains reachable and reversible;
- 390, 768, 1024, 1366, 1600 and 1920 px are reviewed;
- desktop and mobile controls remain operable.

## Deferred

- global removal of `style.css`;
- migration of catalog/product/home;
- consolidation of existing project-owned `!important`;
- final production bundling and asset fingerprinting.
