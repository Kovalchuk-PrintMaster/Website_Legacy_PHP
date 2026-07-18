# Home Frontend Block Map v0.1

**Document ID:** `FP-WEB-HOME-MAP-001`

**Status:** architecture input

## Current composition

```text
public layout
├── shared header
│   ├── logo and contacts
│   ├── navigation
│   ├── catalog menu
│   ├── shared search behavior
│   └── legacy cart trace — approved to hide
├── main
│   ├── promotional slider
│   ├── product offer groups
│   │   ├── hit
│   │   ├── hot
│   │   ├── new
│   │   └── sale
│   ├── horizontal legacy wrapper
│   │   ├── company/about
│   │   └── advantages
│   ├── feedback form — discovered, not assessed
│   ├── news
│   └── home search
└── shared footer
```

## Current-to-controlled component mapping

| Current block | Controlled component | Ownership |
|---|---|---|
| `section.slider` | `HomeHeroSlider` | home surface |
| `section.offers` | `HomeProductGroups` | home + shared product card |
| offers group controls | `ProductGroupTabs` | home surface |
| `goodsGridItem` | `ProductCard` | shared component |
| `section.about` | `HomeAbout` | home surface |
| `section.advantages` | `HomeAdvantages` | home + advantages domain data |
| `section.feedback` | `HomeFeedback` or hidden capability | decision pending |
| `section.news` | `HomeNews` | home + news domain data |
| `form.search` | `SiteSearch` | shared component |
| shared header | `SiteHeader` | shared shell |
| shared footer | `SiteFooter` | shared shell |

## Target template structure

Proposed structure only:

```text
templates/controlled_v1/
├── layout/default.php
├── include/
│   ├── siteHeader.php
│   ├── siteFooter.php
│   ├── siteSearch.php
│   └── productCard.php
└── surfaces/
    └── home/
        ├── index.php
        ├── heroSlider.php
        ├── productGroups.php
        ├── about.php
        ├── advantages.php
        ├── feedback.php
        └── news.php
```

Final directories may be adjusted after implementation review. The important rule is one explicit owner per block.

## Target style ownership

```text
assets/css/shared/tokens.css
assets/css/shared/base.css
assets/css/shared/components/site-header.css
assets/css/shared/components/site-footer.css
assets/css/shared/components/site-search.css
assets/css/shared/components/product-card.css
assets/css/surfaces/home.css
```

Home-specific selectors must live below:

```text
[data-fp-surface="home"]
```

## Target JavaScript ownership

```text
assets/js/shared/bootstrap.js
assets/js/shared/site-search.js
assets/js/shared/product-card.js
assets/js/surfaces/home.js
```

Suggested home behavior modules:

- hero slider initialization;
- product-group tab switching;
- optional feedback initialization;
- home-only progressive enhancements.

## Block migration order

1. Add a semantic home surface root.
2. Freeze current source and HTTP behavior with the focused smoke.
3. Extract shared search and card dependencies.
4. Rebuild product-group navigation semantically.
5. Rebuild the company/about and advantages wrapper.
6. Decide the feedback-form disposition.
7. Rebuild news.
8. Rebuild slider markup while preserving navigation.
9. Move home-only CSS to `surfaces/home.css`.
10. Move home-only behavior to `surfaces/home.js`.
11. Add responsive and accessibility acceptance checks.
12. Add the `controlled_v1` profile switch only after both profiles render safely.

## Explicit non-goals for the structural pass

- visual redesign;
- new marketing copy;
- cart restoration;
- checkout implementation;
- database schema changes;
- changing product selection rules;
- replacing the image optimizer;
- deleting legacy templates;
- moving all media in one operation.

<!-- FP_HOME_BLOCK_MAP_CURRENT_STATE_V0_1 -->
## Current legacy composition checkpoint — `9a64a12`

Extracted components:

1. `heroSlider.php`
2. `productGroups.php`
3. `about.php`
4. `advantages.php`
5. conditional `feedback.php`
6. `news.php`
7. `search.php`

`base/templates/default/index.php` still owns the catalog-navigation section, its trailing horizontal divider and the feedback include boundary.

This remaining ownership is accepted legacy composition. Further mechanical extraction is paused in favor of the isolated modern preview strategy.
