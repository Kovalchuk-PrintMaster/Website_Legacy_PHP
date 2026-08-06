# ForPrint foundation refinement phase 1.1

**Date:** 2026-08-04
**Status:** local implementation for browser review
**Deployment:** not performed

## Scope

- one underline interaction for header and footer links;
- shared shell navigation/meta typography;
- subtle reduced-motion-aware SVG logo tone cycle;
- homepage product-group controls constrained to the page container;
- compact canonical internal-page title scale;
- aligned catalog breadcrumbs and page title;
- shared page-shell rhythm for services and contacts;
- equal top/bottom breadcrumb rhythm for managed-product listings and search;
- standard minimum desktop width for primary services/contact actions;
- server-owned absolute request URLs;
- escaped Telegram HTML formatting with a clickable full product URL;
- formatter theme profiles prepared for future seasonal presentation.

## Ownership

- primitives: `forprint-tokens.css`;
- shell: `forprint-shell.css`;
- home band: `forprint-home.css`;
- page entry rhythm: `forprint-page-structure.css`;
- services: `forprint-services.css`;
- contacts: `forprint-contacts.css`;
- managed listing/search: `forprint-managed-products.css`;
- request presentation:
  `CommunicationRequestMessageFormatter.php`;
- request transport:
  `communication-request.php`.

## Safety boundaries

- legacy `style.css` is unchanged;
- no new `!important` declarations;
- no database mutation;
- no request was submitted by the implementation;
- no Telegram message was sent by the implementation;
- no deployment, staging, commit or push;
- user-entered Telegram fields are HTML-escaped;
- only validated HTTP/HTTPS URLs become Telegram links.

## Deferred browser-specific work

The numbered blocks on the payment/delivery page come from managed content
whose runtime class contract was not present in the audit. Their exact color
migration is deferred rather than introducing a broad selector or a new
`!important`.

The current logo animation is a subtle whole-logo tone cycle. A true travelling
light mask requires a separate inline-SVG ownership decision and remains
deferred until both header and footer SVG sources are accepted.
