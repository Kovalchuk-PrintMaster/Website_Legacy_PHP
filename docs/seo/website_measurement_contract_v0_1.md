# ForPrint website measurement contract v0.1

**ID:** `FP-SEO-MEASURE-2026-07-30-001`
**Version:** `0.1`
**Date:** `2026-07-30`
**Status:** `implementation-ready`
**Checkpoint:** `SEO.MEASURE.01C`

## 1. Objective

Measure real business enquiries without blocking the current Stage 1 launch.

The website must distinguish:

- a confirmed accepted enquiry;
- a contact-link click;
- a form open;
- a failed submission.

Only the first item is a primary lead conversion.

## 2. Verified integration points

The current implementation already provides:

- shared Telegram and Email forms;
- a single frontend submit handler;
- a JSON response with `ok`, `request_id`, `duplicate`,
  `delivery_status` and `delivery_completed`;
- product ID and product name in hidden form fields;
- idempotency protection;
- server-side persistence before external delivery.

The primary integration points are:

```text
base/templates/default/include/header.php
base/templates/default/include/productCommunicationButtons.php
base/templates/default/assets/js/forprint-measurement.js
base/templates/default/assets/js/forprint-product-communication.js
base/communication-request.php
```

The endpoint does not require a change for this version.

## 3. Runtime activation model

Google Tag Manager is optional and disabled by default.

Activation requires both runtime values:

```text
FP_WEB_MEASUREMENT_ENABLED=1
FP_WEB_GTM_CONTAINER_ID=GTM-...
```

The container identifier is not committed to the repository.

If either value is absent or invalid:

- the GTM loader is not rendered;
- the website communication workflow remains fully functional;
- safe `dataLayer` events may queue locally without sending data anywhere.

## 4. Event contract

### `generate_lead`

Classification: primary conversion candidate.

Fire only when all conditions are true:

```text
payload.ok is true
payload.request_id is greater than zero
payload.duplicate is not true
```

A request counts even when it is stored successfully but external Telegram or
Email delivery is temporarily unavailable.

Allowed parameters:

```text
lead_channel
content_type
item_id
item_name
page_path
delivery_state
```

### `contact_click`

Classification: secondary signal.

Fire on public links using:

```text
tel:
mailto:
https://t.me/
tg://
```

Allowed parameters:

```text
contact_method
content_type
item_id
item_name
page_path
```

The phone number, email address or Telegram target must not be sent.

### `lead_form_open`

Classification: diagnostic.

Fire once per form per page load when:

- a product communication modal opens;
- a shared communication form receives focus;
- the header callback form opens.

### `lead_submit_error`

Classification: diagnostic.

Allowed error categories:

```text
missing_contact
phone_confirmation_required
server_rejected
network_or_response
```

Do not send the server message or validation text.

## 5. Privacy boundary

Never send to `dataLayer`, Google Tag Manager, Google Analytics or Google Ads:

- customer name;
- telephone number;
- email address;
- Telegram username;
- free-text message;
- quantity text entered by the customer;
- CSRF token;
- idempotency key;
- database request ID;
- full form payload;
- server exception details.

Product ID and product name are public catalog context and may be sent.

## 6. Duplicate and bot protection

A duplicate idempotent response must not generate another `generate_lead`
event.

The honeypot response does not contain a positive request ID, therefore it
must not generate `generate_lead`.

## 7. Google Tag Manager and GA4 setup

After the code release:

1. create or confirm one business-owned GTM web container;
2. create or confirm the GA4 web stream;
3. add the Google tag through GTM;
4. create Custom Event triggers for the four event names;
5. mark only `generate_lead` as the primary lead event after validation;
6. keep `contact_click` secondary until lead quality is known;
7. verify Tag Assistant and GA4 DebugView;
8. review privacy notice and consent requirements before activation;
9. link Google Ads only after the real success event is proven.

Do not invent or commit GTM, GA4 or Ads identifiers.

## 8. Validation requirements

Local and production validation must confirm:

- enquiry forms work when measurement is disabled;
- no event fires on a failed response;
- one successful non-duplicate request creates exactly one `generate_lead`;
- duplicate retry creates no second `generate_lead`;
- Telegram and Email channels are distinguishable;
- contact clicks never include their destination value;
- no personal data appears in `dataLayer`;
- a JavaScript error in measurement code cannot block form submission;
- GTM loader appears only when explicitly enabled;
- production forms are not submitted by automated release checks.

## 9. Release path

```text
local implementation on s01
→ persistent contract validator
→ local browser test with stub dataLayer
→ exact Git commit
→ controlled production backup and deployment
→ production HTML validation
→ manual Tag Assistant and DebugView validation
→ Google Ads conversion linkage
```

## 10. Current status

This version prepares a dormant, privacy-bounded measurement layer.

It does not:

- create Google accounts;
- activate a GTM container;
- modify production runtime values;
- launch Google Ads;
- send test enquiries.
