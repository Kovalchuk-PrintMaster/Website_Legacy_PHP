# ForPrint homepage structure and slider architecture v0.1

**Status:** active working reference
**Date:** 2026-07-20
**Scope:** current homepage runtime and accepted modernization direction

## 1. Purpose

This document records how the homepage is divided into controlled components, where its data comes from, which stylesheet owns each visible block, and why the hero slider was rebuilt as a bounded project-owned component instead of extending inherited slider CSS.

## 2. Surface root

The homepage is identified by:

```html
<main
    class="main"
    data-fp-surface="home"
    data-fp-frontend-profile="..."
>
```

Home-specific rules must remain inside the home surface boundary.

## 3. Current component map

The extracted homepage component files are:

```text
base/templates/default/surfaces/home/
├── about.php
├── advantages.php
├── feedback.php
├── heroSlider.php
├── news.php
├── productGroups.php
└── search.php
```

Responsibilities:

| Component | Responsibility | Main presentation owner |
|---|---|---|
| `heroSlider.php` | promotional slides, links, text, image, controls | `forprint-home.css` |
| `productGroups.php` | home product-group navigation and grids | `forprint-home.css` + `forprint-product-cards.css` |
| `about.php` | company/about presentation | `forprint-home.css` |
| `advantages.php` | conditional advantages list | `forprint-home.css` |
| `feedback.php` | legacy feedback block controlled by frontend profile | `forprint-home.css` / legacy fallback |
| `news.php` | conditional news presentation | `forprint-home.css` |
| `search.php` | fixed home search strip and suggestion hook | home/search component styles |

## 4. Hero data contract

Hero content is read from the `sales` table.

Current fields used by the slider:

```text
id
visible
name
sub_title
menu_position
external_alias
short_content
img
```

Current working dataset:

- four rows;
- all four are visible;
- all four have images;
- order is controlled by `menu_position`;
- no database migration is required for the current hero presentation.

Presentation changes must not change the query or the meaning of these fields without a separate data review.

## 5. Hero markup responsibilities

The hero contains:

- one Swiper container;
- one wrapper;
- one linked slide per visible `sales` row;
- one text/content area;
- one media area;
- one fraction-pagination node;
- one previous control;
- one next control.

The markup may retain inherited classes for runtime compatibility, but the accepted visual behavior is owned by `fp-home-hero*` selectors.

## 6. Canonical hero geometry

Desktop composition:

- outer section aligned to `.fp-layout-container`;
- left column for text;
- right column for media;
- text aligned to the top of its column;
- media stretched to the full column height;
- no internal empty strip at the right edge;
- a small explicit separation below the hero before the next block.

Image behavior:

```css
object-fit: cover;
object-position: center center;
```

This deliberately allows cropping while keeping the visual subject balanced vertically and horizontally.

## 7. Controls and pagination

Project-owned controls use CSS-drawn arrows.

Policy:

- inherited SVG child icons are hidden;
- Swiper default `::after` glyphs are hidden;
- only one arrow shape is visible per control;
- previous and next controls are vertically centered;
- controls are inset from the component edges;
- controls use a shared soft grey, semi-transparent visual language;
- pagination uses Swiper fraction mode;
- pagination appears in the lower-right area over the image;
- pagination is inset from the bottom/right edges.

## 8. Runtime behavior

Current intended slider behavior:

- animation speed: approximately 650 ms;
- automatic transition when more than one slide exists;
- delay: approximately 6000 ms;
- manual interaction does not permanently disable autoplay;
- hover pauses autoplay;
- `prefers-reduced-motion` disables autoplay;
- loop is enabled only when more than one slide exists;
- overflow is observed safely.

## 9. Header relationship

The hero starts after the shared header.

Header and hero are separate owners:

- `forprint-shell.css` owns the header height and internal composition;
- `forprint-home.css` owns hero spacing;
- `forprint-layout.css` owns the common horizontal geometry.

The hero must not compensate for header problems with large negative margins. The header must not define homepage-specific spacing.

## 10. Media storage direction

Existing rows currently reference images under the inherited `sales/` namespace.

Target namespace for new homepage media:

```text
base/userfiles/frontend/home/slider/
```

Intended generated filename:

```text
slide-<id>-<normalized-title>.jpg
```

Example:

```text
slide-2-druk-na-futbolkakh-i-rehlanakh.jpg
```

Planned upload behavior:

1. accept supported raster input;
2. normalize orientation;
3. generate a safe logical filename;
4. convert to an approved web format;
5. compress to the hero profile;
6. save in the homepage slider namespace;
7. update the database only after successful file creation;
8. remove the superseded file only after the replacement is valid.

This pipeline is planned but not yet complete.

## 11. Functional boundaries

The homepage modernization must preserve:

- promotional slide links;
- product-group links;
- product links and price meaning;
- about, advantages, news, and search data;
- frontend profile behavior;
- hidden/deferred capability governance.

It must not introduce supported cart/checkout claims or new canonical business data.

## 12. Known debt and next checks

- finish homepage slider media upload pipeline;
- complete responsive review at baseline widths;
- verify text overflow for long slider copy;
- confirm accessibility labels for controls;
- add a focused hero runtime inspection;
- remove remaining bounded legacy dependence after acceptance;
- keep the functional contract and presentation architecture synchronized.
