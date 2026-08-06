# Decision: theme aliases, shared page rhythm and fluid body width

**Date:** 2026-08-04
**Status:** working implementation
**Scope:** ForPrint public frontend foundation

## Decision

1. Primitive visual values are owned by `forprint-tokens.css`.
2. Default semantic color aliases are owned by
   `forprint-theme-default.css`.
3. Future themes override semantic aliases through `data-fp-theme`.
4. `forprint-layout.css` owns separate width ceilings:
   - compact shell ceiling;
   - wider commercial-page ceiling.
5. Public page-entry rhythm is owned by
   `forprint-page-structure.css`.
6. Buttons use shared semantic classes and theme aliases.
7. New project-owned CSS adds no `!important`.
8. Legacy `style.css` remains unchanged.
9. Homepage product tabs use a contained inner grid over a full-width band.
10. Header and footer navigation use the same typography and underline
    interaction contract.

## Current theme

```html
<body data-fp-theme="default">
```

Possible future themes:

```text
dark
seasonal-winter
```

No automatic time-based theme switching is activated at this stage.

## Width behavior

- At the accepted 1920-pixel baseline, the existing body proportion remains.
- At 2560 pixels, the main body may expand to approximately the same relative
  proportion.
- Header and footer remain visually concentrated.
- Full-width bands paint the available width while their controls stay inside
  the page container.

## Deferred

- SVG logo shimmer;
- automatic theme selection;
- full migration of every historic literal color;
- mobile/tablet acceptance;
- removal of legacy `style.css`.
