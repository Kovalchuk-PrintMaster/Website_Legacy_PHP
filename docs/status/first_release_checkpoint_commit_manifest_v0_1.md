# First Release Checkpoint Commit Manifest v0.1

## Rule

Do not use:

```bash
git add .
```

Stage only files whose current diffs have been reviewed.

## Likely checkpoint implementation paths

```text
base/communication-request.php
base/composer.json
base/composer.lock
base/core/admin/controllers/AddController.php
base/core/base/settings/Settings.php
base/core/user/controllers/CatalogController.php
base/core/user/controllers/IndexController.php
base/core/user/controllers/SearchController.php
base/core/user/models/Model.php
base/libraries/InternationalPhoneValidator.php
base/libraries/ProductSearch.php
base/search-suggestions.php
base/templates/default/assets/css/forprint-product-cards.css
base/templates/default/assets/css/forprint-product-communication.css
base/templates/default/assets/css/forprint-search-suggestions.css
base/templates/default/assets/js/forprint-product-communication.js
base/templates/default/assets/js/forprint-search-submit.js
base/templates/default/catalog.php
base/templates/default/include/goodsGridItem.php
base/templates/default/include/goodsRelatedItem.php
base/templates/default/include/header.php
base/templates/default/include/productCardHelpers.php
base/templates/default/include/productCommunicationButtons.php
base/templates/default/index.php
scripts/inspection/
docs/
```

Include only paths that exist and belong to the accepted staged diff.

## Review separately

These broad files must not be staged automatically:

```text
base/templates/default/assets/css/style.css
base/templates/default/assets/css/forprint-product-detail.css
base/templates/default/assets/js/script.js
```

They may contain valid work, but their diffs need an explicit decision.

## Required review commands

```bash
git diff --check
git status --short
git diff --stat
git diff -- base/templates/default/assets/css/style.css
git diff -- base/templates/default/assets/css/forprint-product-detail.css
git diff -- base/templates/default/assets/js/script.js
```

## Suggested checkpoint commit message

```text
Stabilize first-release product discovery frontend
```

The final staging list and message should be confirmed from the actual diff.
## Current working-tree status at documentation generation

```text
 M base/communication-request.php
 M base/composer.json
 M base/composer.lock
 M base/core/admin/controllers/AddController.php
 M base/core/base/settings/Settings.php
 M base/core/user/controllers/CatalogController.php
 M base/core/user/controllers/IndexController.php
 M base/core/user/controllers/SearchController.php
 M base/core/user/models/Model.php
 M base/templates/default/assets/css/forprint-product-cards.css
 M base/templates/default/assets/css/forprint-product-communication.css
 M base/templates/default/assets/css/forprint-product-detail.css
 M base/templates/default/assets/css/style.css
 M base/templates/default/assets/js/forprint-product-communication.js
 M base/templates/default/assets/js/script.js
 M base/templates/default/catalog.php
 M base/templates/default/include/goodsGridItem.php
 M base/templates/default/include/goodsRelatedItem.php
 M base/templates/default/include/header.php
 M base/templates/default/include/productCardHelpers.php
 M base/templates/default/include/productCommunicationButtons.php
 M base/templates/default/index.php
?? base/libraries/InternationalPhoneValidator.php
?? base/libraries/ProductSearch.php
?? base/search-suggestions.php
?? base/templates/default/assets/css/forprint-search-suggestions.css
?? base/templates/default/assets/js/forprint-search-submit.js
?? docs/README.md
?? docs/architecture/frontend_and_asset_strategy_v0_1.md
?? docs/architecture/legacy_and_modern_boundaries_v0_1.md
?? docs/architecture/runtime_and_request_flows_v0_1.md
?? docs/architecture/system_architecture_overview_v0_1.md
?? docs/decisions/
?? docs/documentation/
?? docs/plans/
?? docs/reference/
?? docs/status/
?? docs/workflow/
?? scripts/inspection/check_website_catalog_sorting.php
?? scripts/inspection/check_website_grid_cards_search_suggestions.php
?? scripts/inspection/check_website_international_phone_validation.php
?? scripts/inspection/check_website_product_card_price.php
?? scripts/inspection/check_website_product_card_rhythm_search_submit.php
?? scripts/inspection/check_website_product_defaults_cards_search.php
?? scripts/inspection/check_website_product_position_order.php
?? scripts/inspection/check_website_search_precision_card_titles.php
?? scripts/inspection/check_website_search_ux_matching.php
```

## Reviewed broad legacy-file decision

The final read-only audit reviewed the complete diffs of three broad files.

Include these three files in the first-release checkpoint:

- `base/templates/default/assets/js/script.js`
  - required so the legacy Ukrainian telephone formatter does not modify the new international telephone input;
- `base/templates/default/assets/css/forprint-product-detail.css`
  - contains the currently accepted product-page spacing and gallery-height baseline;
- `base/templates/default/assets/css/style.css`
  - contains a small compatibility adjustment for current product-page spacing.

The `style.css` adjustment is accepted only as a release-1 compatibility measure. Moving this responsibility into the isolated product surface is part of the next frontend stabilization phase.

Do not include these temporary generated reports:

- `first_release_checkpoint_precommit_report.txt`;
- `checkpoint_final_audit.txt`;
- root `tmp.php`.
