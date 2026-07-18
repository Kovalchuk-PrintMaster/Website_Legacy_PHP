# Inspection and Maintenance Tools v0.1

Status: current reference<br>
Scope: persistent tools stored under `scripts/`<br>
Canonical date: 2026-07-18

## 1. Purpose

This registry describes persistent repository tools. Temporary root-level `tmp.php` and `tmp.py` files are operator transport mechanisms and are not persistent project tools.

The registry distinguishes:

- static or read-only inspection;
- runtime validation with temporary process effects;
- maintenance or data-changing operations.

A script classification describes intended use. It is not permission to run the script without reviewing its arguments and environment.

## 2. General execution rules

Run tools from the repository root and use the project virtual environment where Python is required.

Before any maintenance command:

```bash
git status --short --untracked-files=all
git diff --check
```

Inspection scripts should not be treated as maintenance scripts. Maintenance scripts must not be run merely because they exist.

## 3. Core frontend and release-check inspections

| Tool | Intended purpose | Repository mutation |
|---|---|---|
| `scripts/inspection/check_website_first_release_checkpoint.php` | Validates the frozen first-publication frontend checkpoint and critical assets. | No intended repository mutation. |
| `scripts/inspection/check_website_frontend_governance_docs.php` | Checks canonical frontend governance, media and capability documentation. | No intended repository mutation. |
| `scripts/inspection/check_website_frontend_profile_resolver.php` | Exercises allowed frontend profiles and fallback behavior through controlled runtime runs. | No intended source mutation; may restart or vary local runtime environment. |
| `scripts/inspection/check_website_home_functional_contract.php` | Checks the documented home data and rendered behavior contract. | No intended repository mutation. |
| `scripts/inspection/check_website_home_surface_boundary.php` | Verifies that home-scoped CSS/JS and surface markers do not leak into internal pages. | No intended repository mutation. |
| `scripts/inspection/check_website_home_component_extraction.php` | Verifies current legacy home component composition and runtime presence. | No intended repository mutation. |

## 4. Catalog, product-card and search inspections

| Tool | Intended purpose | Repository mutation |
|---|---|---|
| `scripts/inspection/check_website_catalog_sorting.php` | Verifies catalog ordering rules and related query behavior. | No intended repository mutation. |
| `scripts/inspection/check_website_grid_cards_search_suggestions.php` | Checks shared grid cards and controlled search suggestions. | No intended repository mutation. |
| `scripts/inspection/check_website_product_card_price.php` | Verifies product-card price presentation. | No intended repository mutation. |
| `scripts/inspection/check_website_product_card_rhythm_search_submit.php` | Checks card rhythm and search form submission behavior. | No intended repository mutation. |
| `scripts/inspection/check_website_product_defaults_cards_search.php` | Checks product defaults, card rendering and search integration. | No intended repository mutation. |
| `scripts/inspection/check_website_product_image_runtime.php` | Verifies product image runtime behavior and expected image sources. | No intended repository mutation. |
| `scripts/inspection/check_website_product_position_order.php` | Verifies product position ordering. | No intended repository mutation. |
| `scripts/inspection/check_website_search_precision_card_titles.php` | Checks matching precision and product-card titles. | No intended repository mutation. |
| `scripts/inspection/check_website_search_ux_matching.php` | Checks search UX matching behavior. | No intended repository mutation. |
| `scripts/inspection/inspect_website_navigation_sources.php` | Reports navigation ownership and menu sources. | No intended repository mutation. |

## 5. Validation and environment inspections

| Tool | Intended purpose | Repository mutation |
|---|---|---|
| `scripts/inspection/check_website_international_phone_validation.php` | Verifies international phone normalization and validation. | No intended repository mutation. |
| `scripts/inspection/check_website_python_environment.py` | Checks the supported local Python environment. | No intended repository mutation. |
| `scripts/inspection/check_website_database_import_readiness.py` | Checks prerequisites for controlled local database intake. | No intended database import. |
| `scripts/inspection/check_website_local_runtime_smoke.py` | Validates the local website runtime and required dependencies. | No intended source mutation; may invoke local commands. |
| `scripts/inspection/check_website_staging_runtime.py` | Checks staging prerequisites and exposure requirements. | No intended source mutation; may inspect runtime services. |
| `scripts/inspection/run_website_local_http_smoke.py` | Starts or drives controlled local HTTP smoke verification. | No intended source mutation; may create temporary runtime files or processes. |
| `scripts/inspection/local_http_smoke_router.php` | Router helper used by local HTTP smoke tooling. | Not intended as a standalone maintenance command. |
| `scripts/inspection/inspect_website_sql_dump.py` | Reads and reports SQL-dump structure before import. | No intended database mutation. |

## 6. Data-changing import tool

| Tool | Intended purpose | Repository mutation |
|---|---|---|
| `scripts/inspection/import_website_sql_dump_local.py` | Performs a controlled local SQL import after readiness checks. | Changes the configured local database. Review arguments, target and backup state first. |

The import tool remains under `scripts/inspection/` for historical compatibility, but operationally it is a maintenance command.

## 7. Maintenance tools

| Tool | Intended purpose | Side effects |
|---|---|---|
| `scripts/maintenance/apply_optimized_goods_image.php` | Applies an approved optimized image to a goods record and storage location. | Changes files and/or database state. |
| `scripts/maintenance/ensure_contacts_information_page.php` | Ensures required managed contacts/information content exists. | May change database content. |
| `scripts/maintenance/optimize_one_uploaded_image.php` | Optimizes one uploaded image through the controlled image pipeline. | Creates or replaces image files. |

## 8. Current home-validation sequence

For documentation and home-surface work, the focused sequence is:

```bash
php scripts/inspection/check_website_frontend_governance_docs.php
php scripts/inspection/check_website_home_functional_contract.php
php scripts/inspection/check_website_home_surface_boundary.php
php scripts/inspection/check_website_home_component_extraction.php
php scripts/inspection/check_website_frontend_profile_resolver.php
```

This sequence validates legacy behavior. It does not replace visual browser review.

## 9. New modern-preview validation direction

The isolated modern preview should eventually receive focused checks for:

- route availability;
- HTTP 200;
- `noindex, nofollow`;
- isolated CSS and JavaScript loading;
- absence of modern assets on legacy routes;
- real data rendering;
- working catalog, product and search links;
- mobile and desktop landmarks.

These checks should be small and purpose-specific. They should not reproduce the previous byte-identical legacy extraction workflow.

## 10. Historical evidence

Detailed implementation history remains under:

- `docs/development/`;
- `docs/launch_readiness/`;
- `coordination/reports/`.

Those documents explain earlier work but do not supersede this current registry.
