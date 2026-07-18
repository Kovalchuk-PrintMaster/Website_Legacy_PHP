# Legacy Frontend Current State v0.1

Status: current reference<br>
Repository checkpoint: `9a64a12`<br>
Scope: public user-facing legacy frontend and the controlled home surface<br>
Canonical date: 2026-07-18

## 1. Purpose

This document records what is currently true about the inherited ForPrint Website frontend. It is a factual reference, not a modernization plan and not a completion report.

Historical implementation reports remain in `coordination/reports/`. Future work belongs in `docs/plans/`. Strategy changes belong in `docs/decisions/`.

## 2. Runtime model

The website uses an inherited PHP controller/template structure:

- user controllers: `base/core/user/controllers/`;
- shared user behavior: `base/core/user/controllers/BaseUser.php`;
- user templates: `base/templates/default/`;
- shared page shell: `base/templates/default/include/header.php` and `base/templates/default/include/footer.php`;
- home controller: `base/core/user/controllers/IndexController.php`;
- home template: `base/templates/default/index.php`;
- shared layout entry: `base/templates/default/layout/default.php`.

`BaseUser` provides shared presentation and routing helpers, including profile resolution, aliases, image resolution, product rendering, pagination, cart helpers and shared menu data.

`IndexController::inputData()` prepares the legacy home data and selects the home template. The modern preview must reuse stable data ownership rather than duplicate database queries without a documented reason.

## 3. Controller inventory

The current user controller layer contains 17 controllers:

- `AjaxController.php`;
- `BaseUser.php`;
- `CatalogController.php`;
- `ContactsController.php`;
- `IndexController.php`;
- `InformationController.php`;
- `KnowelegesController.php`;
- `LkController.php`;
- `LoginController.php`;
- `NewsController.php`;
- `OrdersController.php`;
- `ProductController.php`;
- `PromotionsController.php`;
- `SearchController.php`;
- `SendMailController.php`;
- `SpecialoffersController.php`;
- `СartController.php`.

Legacy naming anomalies such as `KnowelegesController.php` and the Cyrillic character in `СartController.php` are inherited compatibility facts. They must not be renamed casually before publication because routes and autoloading may depend on the current names.

## 4. Home composition

The home template currently composes seven extracted presentation components:

1. `base/templates/default/surfaces/home/heroSlider.php`;
2. `base/templates/default/surfaces/home/productGroups.php`;
3. `base/templates/default/surfaces/home/about.php`;
4. `base/templates/default/surfaces/home/advantages.php`;
5. `base/templates/default/surfaces/home/feedback.php`;
6. `base/templates/default/surfaces/home/news.php`;
7. `base/templates/default/surfaces/home/search.php`.

The component extraction sequence was completed through checkpoint `9a64a12`.

The legacy `index.php` still owns:

- the catalog-navigation section based on `$this->menu['catalog']`;
- the trailing horizontal divider;
- the conditional include boundary for `feedback.php`.

These remaining details are accepted legacy composition. They do not block publication and are no longer a reason to continue deep mechanical extraction before the modern frontend is started.

## 5. Home component dependencies

### Hero slider

`heroSlider.php` uses shared helpers such as `alias`, `clearStr`, `img`, `set` and `wordsForCounter`.

### Product groups

`productGroups.php` uses `alias` and `showGoods`. It retains shared product-card rendering.

### About

`about.php` uses `alias`, `img` and inherited settings.

### Advantages

`advantages.php` uses inherited image resolution.

### Feedback

`feedback.php` preserves the inherited presentation contract. Its visibility is controlled at the include boundary by the frontend profile.

### News

`news.php` uses `alias` and `showGoods`. It retains shared rendering behavior.

### Search

`search.php` contains only the home search presentation instance. Search behavior remains shared with the internal header search through:

- `base/core/user/controllers/SearchController.php`;
- `base/templates/default/assets/js/forprint-search-submit.js`;
- `base/templates/default/assets/css/forprint-search-suggestions.css`.

The home and header search forms are separate presentation instances using the same GET payload and controlled suggestion endpoint.

## 6. Frontend profiles

The environment key is:

```text
FP_WEB_FRONTEND_PROFILE
```

Allowed profiles:

- `legacy`;
- `controlled_v1`;
- `future_redesign`.

Fallback behavior is `legacy`.

Current visibility rule:

- `legacy`: inherited feedback remains visible;
- `controlled_v1`: feedback is hidden at the include boundary;
- `future_redesign`: currently preserves inherited feedback until a later decision.

There is no public query-string, cookie or UI selector for switching profiles.

## 7. Templates and assets

Important templates:

- `base/templates/default/index.php`;
- `base/templates/default/catalog.php`;
- `base/templates/default/product.php`;
- `base/templates/default/include/header.php`;
- `base/templates/default/include/footer.php`;
- `base/templates/default/layout/default.php`.

Current asset shape:

- eight CSS files under `base/templates/default/assets/css/`;
- fourteen JavaScript files under `base/templates/default/assets/js/`;
- one home-scoped CSS entry: `assets/css/surfaces/home.css`;
- one home-scoped JavaScript entry: `assets/js/surfaces/home.js`;
- large inherited global stylesheet: `assets/css/style.css`;
- shared product-card, product-detail, communication and search assets.

The scoped home files establish an isolation boundary but currently contain only a small controlled layer. Most visual behavior still comes from inherited global assets.

## 8. Search ownership

`SearchController` owns full search results. The shared search JavaScript owns:

- controlled suggestion requests;
- keyboard navigation;
- form-scoped input handling;
- direct product navigation;
- full-results form submission.

Do not duplicate this behavior in a new home template. A modern search presentation should reuse the stable behavior contract or replace it through an explicit, tested decision.

## 9. Existing safety net

Persistent checks cover:

- frontend governance documents;
- profile resolution;
- home functional contract;
- home surface isolation;
- cumulative home component extraction;
- product cards and search suggestions;
- product detail behavior;
- catalog sorting;
- phone validation;
- local and staging runtime readiness.

The exact tool registry is documented in `inspection_and_maintenance_tools_v0_1.md`.

## 10. Known legacy constraints

The inherited frontend still has:

- large global CSS ownership;
- mixed presentation and data access conventions;
- old naming and spelling anomalies;
- backup files beside active templates;
- shared shell files with broad responsibility;
- multiple historical JavaScript libraries;
- markup whose visual rhythm depends on precise whitespace;
- behavior spread across PHP, CSS and JavaScript rather than explicit component APIs.

These constraints justify conservative publication stabilization and isolated modern development.

## 11. Publication boundary

Before the first public deployment, legacy work should focus on:

- working navigation;
- valid product and catalog routes;
- readable mobile behavior;
- visible contact information;
- reliable search;
- removal or hiding of incomplete capabilities;
- metadata, robots, sitemap and error handling;
- runtime and deployment smoke checks.

It should not expand into a full rewrite of the inherited home template.

## 12. Modernization boundary

The modern frontend must:

- use a separate preview route before replacing `/`;
- use its own HTML composition;
- use isolated CSS and JavaScript;
- reuse real server-side data and routes;
- remain `noindex, nofollow` while unfinished;
- be accepted visually by the project owner block by block;
- avoid copying inherited CSS as its architectural base.

The legacy design may be used as an initial visual reference, but it is not the required DOM or CSS contract.

## 13. Canonical cross-references

- Strategy decision: `docs/decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md`
- Execution plan: `docs/plans/legacy_publication_and_modern_frontend_plan_v0_1.md`
- Home functional contract: `docs/reference/home_frontend_functional_contract_v0_1.md`
- Home block map: `docs/architecture/home_frontend_block_map_v0_1.md`
- Legacy/modern boundary: `docs/architecture/legacy_and_modern_boundaries_v0_1.md`
- Persistent tools: `docs/reference/inspection_and_maintenance_tools_v0_1.md`
