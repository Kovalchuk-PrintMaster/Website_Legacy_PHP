# Disabled and Deferred Interface Capabilities v0.1

**Document ID:** `FP-WEB-UI-CAP-001`
**Status:** active baseline
**Scope:** public ForPrint Website frontend
**Surfaces:** home, catalog, search, product

## Purpose

This registry records functionality that exists or previously existed in the legacy website but is intentionally hidden, scheduled for hiding, or deferred.

Hidden functionality is not considered deleted. Its code, database structures and historical intent must remain discoverable until a separate cleanup decision is approved.

## Status vocabulary

| Status | Meaning |
|---|---|
| `active` | Visible and supported in the current interface |
| `hidden` | Intentionally not visible, but retained for possible restoration |
| `approved_to_hide` | Still discoverable in the current interface and approved for removal from view |
| `deferred` | Outside the current implementation scope |
| `discovered_not_assessed` | Found in legacy code or rendered HTML, but not yet fully analyzed |
| `retired` | Permanently removed after a separate approved cleanup |

## Current registry

### `product_online_order_controls`

- **Human name:** Product online-order and add-to-cart controls
- **Status:** `hidden`
- **Surface:** product
- **Decision:** do not restore during the current frontend modernization
- **Current replacement:** managed Email and Telegram request buttons
- **Reason:** the cart and online checkout are outside the current publication scope
- **Preservation rule:** do not delete related order/cart code or database structures during presentation work
- **Restoration condition:** separate functional review, current architecture adaptation, tests and owner approval

### `cart_header_entry`

- **Human name:** Header cart icon and entry point
- **Status:** `approved_to_hide`
- **Surface:** shared header
- **Known evidence:** legacy selectors such as `cart-btn-wrap` and `svg-basket`
- **Decision:** hide from the public interface
- **Reason:** it points to a workflow that is not currently supported
- **Preservation rule:** hiding must not delete the underlying implementation
- **Restoration condition:** cart workflow is formally returned to scope

### `cart_and_checkout_flow`

- **Human name:** Cart, checkout and order placement flow
- **Status:** `deferred`
- **Surfaces:** shared, product, possible account/order pages
- **Known evidence:** cart cookie handling, order controller logic and legacy order controls
- **Decision:** do not spend modernization time on this flow now
- **Current customer path:** Email or Telegram request
- **Restoration condition:** separate project decision and end-to-end workflow design

### `legacy_order_popup`

- **Human name:** Legacy order popup
- **Status:** `discovered_not_assessed`
- **Known evidence:** selectors such as `order-popup__inner`, `send-order`, `execute-order_btn`
- **Decision:** inventory only; do not revive automatically
- **Required next action before restoration:** source-path audit and runtime behavior audit

### `home_feedback_form`

- **Human name:** Home-page feedback form
- **Status:** `discovered_not_assessed`
- **Surface:** home
- **Visible fields:** name, email, phone, question, privacy checkbox and submit button
- **Known evidence:** `section.feedback` in `base/templates/default/index.php`
- **Current decision:** do not claim this form as a supported communication channel until its endpoint and delivery behavior are verified
- **Current supported alternatives:** managed Email and Telegram request flows on product pages
- **Required next action:** verify endpoint, validation, privacy handling, success/error states and message delivery owner
- **Possible outcomes:** implement as a controlled form or hide through an explicit capability decision

## Rules for future entries

Every hidden or deferred capability must record:

1. stable machine ID;
2. human-readable name;
3. current status;
4. affected surface;
5. reason for the decision;
6. current replacement, when one exists;
7. known source or selector evidence;
8. preservation boundary;
9. restoration conditions;
10. date and decision owner.

## Non-destructive hiding rule

Presentation modernization may:

- remove a control from rendered HTML;
- disable its asset loading;
- block navigation to an unsupported flow;
- replace it with a supported customer action.

Presentation modernization must not, without a separate task:

- drop database tables or columns;
- delete controllers, models or domain logic;
- remove historical migrations or documentation;
- silently redefine business ownership;
- claim that hidden functionality never existed.

## Restoration workflow

Before restoring a hidden capability:

1. inspect current code and database dependencies;
2. define the supported user journey;
3. decide canonical data ownership;
4. update the machine registry status;
5. implement with surface-owned HTML, CSS and JavaScript;
6. add focused and end-to-end tests;
7. perform mobile and accessibility review;
8. obtain owner acceptance.
