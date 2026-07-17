# First Release Frontend Checkpoint v0.1

## Purpose

This checkpoint freezes the current minimum stable product-discovery and frontend baseline for the first public release of the legacy PHP website.

The objective is not pixel-perfect presentation. The objective is to preserve a working, testable and documented state before the larger frontend-surface stabilization programme begins.

## Release rule

Complete the first release as quickly as reasonably possible.

Further spacing polish, ideal responsive behaviour, search ranking refinements and visual micro-adjustments are deferred unless they block publication or a core customer journey.

## Detected implementation state

| Capability | State | Primary evidence |
|---|---|---|| International phone validation | detected | `base/libraries/InternationalPhoneValidator.php` |
| Communication request integration | detected | `base/communication-request.php` |
| Canonical product-card price state | detected | `base/templates/default/include/productCardHelpers.php` |
| Product position ordering | detected | `base/core/user/models/Model.php` |
| Catalog sorting smoke | detected | `scripts/inspection/check_website_catalog_sorting.php` |
| New-goods defaults | detected | `base/core/admin/controllers/AddController.php` |
| Compact shared product cards | detected | `base/templates/default/assets/css/forprint-product-cards.css` |
| Controlled search suggestions | detected | `base/search-suggestions.php` |
| Shared product search service | detected | `base/libraries/ProductSearch.php` |
| One-line card-title refinement | detected | `base/templates/default/assets/css/forprint-product-cards.css` |

## Accepted baseline

Where detected in the working tree, the checkpoint accepts:

- international phone validation and communication-request handling;
- canonical product-card price state without duplicate discount application;
- product ordering by configured list position;
- repaired catalogue sorting;
- explicit defaults for new goods;
- shared product cards across home, catalogue, search and related products;
- site-controlled search suggestions;
- a shared search service for result pages and suggestions.

## Deliberately deferred

The first release does not require:

- pixel-perfect card spacing;
- final search ranking and linguistic normalization;
- final search-history presentation;
- complete mobile redesign;
- complete removal of the legacy global stylesheet;
- redesign of promotions and special-offer pages;
- ideal image handling for every empty-image state;
- exhaustive cross-browser visual testing.

## Working-tree caution

The repository contains accumulated changes from several related frontend tasks.

Do not use:

```bash
git add .
```

Stage files explicitly.

Broad legacy files such as `base/templates/default/assets/css/style.css`,
`base/templates/default/assets/css/forprint-product-detail.css` and
`base/templates/default/assets/js/script.js` must be reviewed separately before inclusion.

## Next major programme

The next frontend programme manages four surfaces:

1. home page;
2. product page;
3. catalogue and filters;
4. search results and search interaction.

The product page is currently the reference surface because it already has the strongest isolated-asset foundation.

## Acceptance

This checkpoint is ready to commit when:

- PHP and JavaScript syntax checks pass;
- `git diff --check` passes;
- the latest applicable search/card smoke passes;
- home, product, catalogue and search routes do not return server errors;
- documentation is staged with the intended implementation;
- the checkpoint is pushed to GitHub.

## Current validation suite

The active umbrella validation is:

```text
scripts/inspection/check_website_first_release_checkpoint.php
```

Historical iterative smoke files remain in the repository for traceability, but they explicitly delegate to the current checkpoint smoke after their earlier CSS markers or asset versions were superseded.

The checkpoint also retains focused checks for:

- international phone validation;
- product-card prices;
- product list positions;
- catalogue manual sorting;
- precise search and one-line product titles.
