# Decision: Freeze First-Release Scope and Start Progressive Frontend Refactor

- Date: 2026-07-17
- Status: accepted
- Repository: Website_Legacy_PHP

## Decision

Freeze the current product-card, ordering, publication-default and search baseline as a first-release checkpoint.

Do not delay the release for minor spacing, ranking or responsive refinements unless they block publication or a core customer flow.

After this checkpoint, begin a progressive frontend refactor across:

1. home;
2. product;
3. catalogue and filters;
4. search.

Use the product page as the reference implementation.

## Rationale

The frontend is globally coupled through legacy templates and a very large stylesheet.

Continuing with unrelated global overrides increases regression risk.

A complete rewrite before release would delay publication.

Progressive surface isolation preserves current routes and data while creating explicit ownership of layout, CSS and JavaScript.

## Consequences

Positive:

- current stable work is preserved;
- publication is not blocked by visual perfection;
- frontend work receives a clear sequence;
- shared components remain reusable;
- responsive work becomes measurable.

Accepted cost:

- some visual imperfections remain in release 1;
- `style.css` remains temporarily necessary;
- legacy and isolated layers coexist during migration;
- every surface requires an inventory before implementation.

## Commit policy

The checkpoint commit must:

- include only the accepted baseline;
- stage files explicitly;
- exclude unrelated or unverified changes;
- pass `git diff --check`;
- include smoke evidence;
- push to the configured GitHub repository.
