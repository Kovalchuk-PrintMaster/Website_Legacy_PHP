# Frontend Surface Isolation Strategy v0.1

## Context

The legacy PHP frontend is distributed across templates, includes, inline fragments, JavaScript files and a large global stylesheet.

Small global changes can affect unrelated pages, especially during viewport resizing and browser zoom.

The chosen strategy is progressive isolation.

## Core rule

`base/templates/default/assets/css/style.css` remains a compatibility layer.

New frontend work must not treat it as the primary development surface.

## Surface ownership

The managed surfaces are:

- home;
- product;
- catalogue and filters;
- search results and interaction.

Each surface should eventually have:

- an explicit page or body marker;
- one clearly named page CSS asset;
- one clearly named page JavaScript asset when required;
- documented shared components;
- documented remaining legacy selectors.

## Naming direction

The final names must follow the read-only inventory, but the intended direction is:

```text
base/templates/default/assets/css/forprint-surface-home.css
base/templates/default/assets/css/forprint-surface-product.css
base/templates/default/assets/css/forprint-surface-catalog.css
base/templates/default/assets/css/forprint-surface-search.css
```

Shared components remain separate, for example:

```text
base/templates/default/assets/css/forprint-product-cards.css
base/templates/default/assets/css/forprint-product-communication.css
base/templates/default/assets/css/forprint-search-suggestions.css
```

## Template rule

Do not force all surfaces into one giant template.

Do not duplicate shared components merely to gain local styling control.

Use:

- surface-specific templates for layout;
- shared includes for reusable components;
- explicit context markers for small local variants;
- documented adapters when legacy controller data is inconvenient.

## CSS ownership

Shared component files own:

- intrinsic component geometry;
- component typography;
- states;
- price and action presentation.

Surface files own:

- page columns and rows;
- section spacing and order;
- sidebar/filter placement;
- responsive breakpoints;
- interaction between components.

## JavaScript ownership

- one behaviour has one owner;
- avoid adding new features to the monolithic `script.js` when an isolated file is practical;
- search behaviour stays in search assets;
- product communication stays in communication assets;
- catalogue behaviour stays in catalogue assets;
- prefer data attributes over layout-dependent selectors.

## Migration workflow

1. read-only inventory;
2. assign ownership;
3. create or confirm isolated assets;
4. move only required rules;
5. add smoke checks;
6. compare all four surfaces;
7. document remaining legacy dependencies;
8. commit the smallest coherent checkpoint.

## Safety rules

- do not rewrite all of `style.css` in one task;
- do not delete legacy selectors without route evidence;
- do not use `git add .`;
- preserve business behaviour;
- prefer explicit page files over anonymous override tails;
- retain rollback-capable commits.
