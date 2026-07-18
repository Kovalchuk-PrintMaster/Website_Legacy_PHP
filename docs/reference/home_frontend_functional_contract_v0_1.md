# Home Frontend Functional Contract v0.1

**Document ID:** `FP-WEB-HOME-CONTRACT-001`

**Status:** current behavior baseline

**Surface:** home

**Purpose:** preserve business-visible behavior while the home HTML, CSS and JavaScript are moved into a controlled architecture.

## Contract boundary

This contract describes what the home surface currently receives, renders and exposes to the visitor.

It does not freeze the current visual design, legacy class names, exact spacing or desktop-only geometry.

The controlled rewrite may change markup and presentation only when the behavior described here remains available or is explicitly reclassified in the capability registry.

## Route and render chain

```text
GET /
→ default user route
→ core/user/controllers/IndexController::inputData()
→ core/user/controllers/BaseUser::outputData()
→ templates/default/index.php
→ templates/default/include/header.php
→ templates/default/include/footer.php
→ templates/default/layout/default.php
```

Current template root:

```text
base/templates/default/index.php
```

Current controller:

```text
base/core/user/controllers/IndexController.php
```

Shared rendering owner:

```text
base/core/user/controllers/BaseUser.php
```

## Shared shell contract

The home page remains inside the common public shell.

Required shared capabilities:

- public header;
- primary navigation;
- catalog navigation data;
- information-menu data;
- social links data;
- site search;
- main content landmark;
- public footer;
- globally supported communication and search assets.

The planned cart entry is not part of the supported current home contract and is governed by the disabled/deferred capability registry.

## Controller data contract

### `sales`

Source table:

```text
sales
```

Selection:

- `visible = 1`;
- ordered by `menu_position`;
- no explicit controller limit.

Consumer:

- promotional slider.

Empty behavior:

- slider section is not rendered.

### `advantages`

Source table:

```text
advantages
```

Selection:

- `visible = 1`;
- ordered by `menu_position`;
- maximum six rows.

Consumer:

- “Наші переваги” section.

Empty behavior:

- advantages section is not rendered.

### `news`

Source table:

```text
news
```

Selection:

- `visible = 1`;
- ordered by `menu_position`, then `date`;
- ascending direction;
- maximum three rows.

Consumer:

- home news section through `newsItem`.

Empty behavior:

- news section is not rendered.

### `arrHits`

Controller-owned presentation metadata for four product groups:

| Machine key | Current label | Current icon source |
|---|---|---|
| `hit` | Хіти продажів | `icons.svg#hit` |
| `hot` | Гарячі пропозиції | `icons.svg#hot` |
| `new` | Щось Цікаве | `icons.svg#search` |
| `sale` | Акція | `icons.svg#rocket` |

The labels and icons are currently assembled inside `IndexController`. During modernization they may move into a dedicated presentation configuration, but their visible meaning must not disappear silently.

### `goods`

For each `arrHits` key:

- matching promotion flag equals `1`;
- `visible = 1`;
- ordered by `menu_position`, then `id`;
- both directions ascending;
- maximum six products.

Consumer:

```text
templates/default/include/goodsGridItem.php
```

Empty group behavior:

- the corresponding tab and content panel are not rendered.

Shared card behavior:

- product link remains available;
- product image remains available when configured;
- product name remains available;
- short description remains available;
- canonical price and discount presentation remains available;
- current compact card contract remains shared with catalog and search.

### `set`

The shared public controller supplies the current site/settings record as `$this->set`.

Home currently consumes at least:

- `name`;
- `short_content`;
- `img`;
- `promo_img`.

These values support the company/about area and adjacent presentation content.

## Home block contract

### 1. Promotional slider

Current root:

```text
section.slider
```

Behavior:

- renders only when `sales` is not empty;
- each slide links through `external_alias`;
- slide content may include subtitle, name, text, years and image;
- Swiper pagination and previous/next controls are present.

Modernization rule:

- retain navigable promotional slides;
- keep empty-state omission;
- do not require the current internal class structure.

### 2. Product offer groups

Current root:

```text
section.offers
```

Behavior:

- renders when product-group data exists;
- exposes up to four logical groups: `hit`, `hot`, `new`, `sale`;
- only non-empty groups receive visible controls and content;
- first available group is active;
- each group displays up to six shared product cards;
- each panel links to the catalog.

Modernization rule:

- preserve group visibility rules and product-card links;
- group navigation may be rebuilt with semantic tabs;
- keyboard and mobile behavior must improve.

### 3. Company/about block

Current roots:

```text
div.horizontal
section.about
```

Behavior:

- displays the shared site name;
- displays shared short company content;
- consumes shared settings imagery where configured.

Modernization rule:

- preserve the information;
- exact horizontal legacy layout is not contractual;
- content must remain manageable and responsive.

### 4. Advantages block

Current root:

```text
section.advantages
```

Behavior:

- title: “Наші переваги”;
- maximum six visible database-managed entries;
- each entry currently exposes a name and image;
- images remain owned by the `advantages` domain when tied to database records.

Modernization rule:

- preserve visible records and their ordering;
- decorative home-only media must use the frontend/home media policy;
- meaningful alternative text must replace empty `alt` values.

### 5. Feedback form

Current root:

```text
section.feedback
```

Current component:

```text
base/templates/default/surfaces/home/feedback.php
```

Governed capability:

```text
home_feedback_form
```

Current status:

```text
approved_to_hide
```

Technical assessment:

```text
legacy_presentation_only
```

The committed legacy component remains visible in the `legacy` profile, but it is not a supported communication channel. Its form uses the placeholder action `index.html`, implicit GET, five controls, zero named controls and no demonstrated JavaScript or PHP delivery handler.

Profile rule:

- `legacy`: keep the existing presentation visible and recoverable until configuration-driven profile gating is introduced;
- `controlled_v1`: hide the form from the public interface;
- `future_redesign`: no visibility decision is made by this contract.

Modernization rule:

- do not silently present the form as functional;
- do not delete the legacy component as a side effect of hiding;
- retain managed Email and Telegram request flows as the supported customer alternatives;
- restoration requires a supported endpoint, named payload, validation, privacy processing, delivery ownership and explicit success/error states.

### 6. News block

Current root:

```text
section.news
```

Behavior:

- renders only when `news` is not empty;
- displays up to three entries through `newsItem`;
- includes a link to the full news page.

Modernization rule:

- preserve entry links and “view all” navigation;
- exact card markup is not contractual.

### 7. Home search

Current root:

```text
form.search
```

Behavior:

- submits to the public search route;
- uses the controlled search-suggestion endpoint;
- supports direct navigation to products and full result submission.

Modernization rule:

- preserve keyboard submission;
- preserve controlled suggestions;
- retain accessible labeling and mobile usability.

## Shared asset dependencies

Current home rendering loads a broad shared asset set including:

- global `style.css`;
- shared product-card CSS;
- search-suggestion CSS;
- product-detail and product-communication CSS even outside the product surface;
- Swiper;
- Fancybox;
- jQuery and legacy/global JavaScript;
- controlled search JavaScript.

This broad loading pattern is observational, not contractual.

Target rule:

- home must receive a surface-owned CSS entrypoint;
- home must receive a surface-owned JavaScript entrypoint;
- unrelated product assets must not remain mandatory for home;
- shared components must be loaded through explicit shared ownership.

## Current observational baseline

At the architecture-audit snapshot, the rendered home returned HTTP `200` and approximately:

- 645 elements;
- 7 sections;
- 47 images;
- 73 links;
- 22 buttons;
- 11 CSS links;
- 20 scripts;
- 10 inline style attributes.

These numbers are diagnostic only. They may decrease during modernization and are not acceptance thresholds.

## Accessibility and responsive baseline

Already observed:

- viewport meta present;
- `lang` present;
- header, navigation, main and footer landmarks present;
- some ARIA usage;
- lazy loading used by product cards.

Observed gaps:

- no skip link;
- no `picture` or responsive `source`;
- no `srcset`;
- several images use empty alternative text;
- responsive behavior depends on accumulated legacy breakpoints;
- visible feedback form behavior is not fully defined.

## Hidden and deferred boundaries

Not part of the supported home contract:

- cart icon and cart entry;
- add-to-cart controls;
- checkout and order placement;
- legacy order popup;
- home feedback form in `controlled_v1`.

These remain discoverable in the capability registry and must not be deleted as a side effect of presentation work.

## Controlled rewrite invariants

The first controlled home rewrite must preserve:

1. HTTP success for `/`;
2. common header, navigation and footer;
3. site search and controlled suggestions;
4. conditional promotional slider;
5. conditional product groups;
6. stable product ordering;
7. product links, images, names, descriptions and pricing;
8. company/about content;
9. conditional advantages;
10. conditional news and full-news navigation;
11. explicit treatment of the legacy feedback form;
12. absence of supported cart/checkout claims;
13. current database ownership and data sources.

## Non-contractual legacy details

The following may change without a business-function change:

- class names;
- nested `div` count;
- exact section order after owner review;
- current fixed dimensions;
- current color literals;
- current font declarations;
- inline styling;
- Swiper-specific markup;
- tab implementation details;
- global stylesheet ownership;
- global JavaScript selectors.

## Next implementation gate

Before any major home HTML replacement:

- focused smoke must pass;
- this contract and YAML must remain synchronized;
- each planned block must have a target semantic component;
- feedback-form disposition must be explicit;
- rollback profile behavior must be defined;
- no data query may change without separate review.
