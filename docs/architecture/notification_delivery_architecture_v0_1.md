# Notification Delivery Architecture v0.1

**Document ID:** `FP-WEB-ARCH-NOTIFY-001`
**Version:** `0.1`
**Date:** `2026-07-27`
**Status:** accepted design baseline; implementation pending
**Scope:** public communication buttons, email delivery, Telegram delivery and local safe testing

## Context

The current project already contains communication-related frontend components, POST forms, PHPMailer dependencies and legacy email handling.

The read-only `06D.20` audit found:

- `13` forms;
- `5` POST forms;
- existing PHPMailer code;
- `base/core/user/controllers/SendMailController.php`;
- order-email behavior in `OrdersController.php`;
- communication UI in:
  - `base/templates/default/include/productCommunicationButtons.php`;
  - `base/templates/default/include/communicationRequestForm.php`;
- no confirmed production Telegram transport implementation.

The repository architecture already reserves a standalone communication endpoint:

```text
base/communication-request.php
```

Before implementation, the actual endpoint content and all frontend callers must be inspected directly. This document defines the target architecture, not a claim that every component is already implemented.

## Goals

- preserve working buttons during repeat synchronization;
- use one stable server-side request contract;
- keep SMTP and Telegram secrets outside the webroot and Git;
- support safe local testing without real delivery;
- isolate email and Telegram failures;
- prevent duplicate submissions;
- keep the existing order-email path stable until compatibility is proven;
- provide structured, non-sensitive observability.

## Non-goals for v0.1

- replacing the complete legacy order workflow;
- adding a database queue;
- changing DNS, MX or mail hosting;
- introducing a strict Content Security Policy;
- exposing delivery-provider details to the browser;
- placing Telegram Bot API calls in frontend JavaScript.

## Component model

```text
communication button or form
        ↓
frontend interaction module
        ↓
stable POST endpoint
        ↓
request parser and validator
        ↓
idempotency and abuse controls
        ↓
NotificationDispatcher
        ├── EmailTransport
        ├── TelegramTransport
        └── Null/SpoolTransport
        ↓
structured result without secrets
```

## Ownership boundaries

### Frontend templates

Templates render labels, fields, accessibility attributes and non-secret context.

They must not contain:

- SMTP credentials;
- Telegram bot tokens;
- Telegram chat IDs;
- provider URLs assembled with secrets;
- business logic for delivery.

### Frontend JavaScript

JavaScript owns:

- opening and closing the communication interface;
- client-side usability validation;
- disabling repeated clicks during an active request;
- sending a request to the stable endpoint;
- rendering success, partial-success and error states;
- generating or forwarding an idempotency key.

Client validation improves usability but never replaces server validation.

### Stable endpoint

Canonical target:

```text
/communication-request.php
```

Physical owner:

```text
base/communication-request.php
```

The public URL should remain stable even if internal service classes change.

The endpoint owns:

- method and content-type checks;
- request-size limits;
- CSRF validation;
- honeypot and rate-limit checks;
- server-side normalization;
- channel allowlisting;
- dispatch;
- safe JSON responses;
- request ID generation;
- redacted logging.

### NotificationDispatcher

The dispatcher receives a validated request object and invokes configured transports.

It must not read raw `$_POST` values directly.

Conceptual interface:

```php
interface NotificationTransport
{
    public function isEnabled(): bool;

    public function send(
        NotificationRequest $request
    ): NotificationResult;
}
```

The exact class and namespace names require source inspection before implementation.

## Request contract

The endpoint accepts `POST` only.

Preferred content type:

```text
application/json
```

A temporary compatibility layer may accept standard form encoding while legacy forms are migrated.

Allowed fields may include:

```text
channel
name
phone
email
message
product_id
product_name
source_url
locale
csrf_token
idempotency_key
```

Rules:

- `channel` uses a fixed allowlist such as `email`, `telegram` or an approved combined mode;
- unknown fields are ignored or rejected according to the endpoint version;
- server-side limits apply to every text field;
- `source_url` is treated as untrusted input;
- product information is revalidated or enriched server-side where possible;
- secrets are never accepted from the browser.

Legacy form values such as `mode=email` or `mode=telegram` may be mapped to the typed channel field during migration.

## Response contract

Responses use JSON and do not expose stack traces, SMTP errors, tokens or provider responses.

Example success shape:

```json
{
  "ok": true,
  "request_id": "public-safe-id",
  "status": "sent",
  "channels": {
    "email": "sent",
    "telegram": "disabled"
  },
  "message": "Повідомлення прийнято."
}
```

Example partial result:

```json
{
  "ok": true,
  "request_id": "public-safe-id",
  "status": "partial",
  "channels": {
    "email": "sent",
    "telegram": "failed"
  },
  "message": "Запит прийнято частково."
}
```

Recommended HTTP semantics:

| Status | Meaning |
|---|---|
| `200` | synchronous result returned |
| `202` | accepted for deferred delivery if a queue is introduced |
| `400` | malformed request |
| `403` | CSRF or policy rejection |
| `405` | unsupported method |
| `415` | unsupported content type |
| `422` | validation failed |
| `429` | rate limit reached |
| `500` | internal application failure |
| `502` | configured external delivery provider failed |

The first implementation may simplify provider failure mapping, but the public response must remain safe and deterministic.

## Validation

Server-side validation includes:

- required-field rules;
- maximum lengths;
- Unicode-safe normalization;
- phone classification according to the accepted phone policy;
- email validation only when the email channel requires it;
- message sanitization for logs and output;
- channel allowlisting;
- product identifier validation;
- locale allowlisting.

User text must be escaped for the target transport:

- HTML-escaped for HTML email;
- Telegram-safe formatting or plain text for Telegram;
- log-safe single-line summaries for operational logs.

## Duplicate-submission protection

The browser disables the submit control while a request is active, but server-side idempotency is authoritative.

The endpoint accepts an `idempotency_key` and stores a short-lived result or request fingerprint.

A first implementation may use session-backed or cache-backed storage and does not require a database schema change.

Repeated delivery with the same valid key returns the previous safe result instead of sending again.

## Abuse controls

Minimum controls:

- POST-only endpoint;
- CSRF token for same-site forms;
- honeypot field;
- request body limit;
- per-session and per-address rate limiting;
- delivery timeout;
- channel allowlist;
- no arbitrary recipient input;
- no user-controlled SMTP headers;
- no user-controlled Telegram destination;
- controlled CORS policy, normally same-origin only.

CAPTCHA is deferred unless measured abuse justifies it.

## Email transport

Email delivery uses the installed PHPMailer dependency through one project-owned adapter.

The adapter owns:

- SMTP configuration lookup;
- sender and recipient allowlists;
- subject construction;
- UTF-8 handling;
- HTML/plain-text body generation;
- timeout;
- exception translation into `NotificationResult`.

Do not call PHPMailer directly from templates or frontend scripts.

The existing `SendMailController.php` and order-email flow remain unchanged until targeted compatibility tests prove that consolidation is safe.

## Telegram transport

Telegram delivery is server-side only and uses the Telegram Bot API over HTTPS.

The adapter owns:

- bot token lookup;
- fixed chat destination lookup;
- message formatting;
- connection and response timeout;
- safe handling of non-2xx responses;
- redaction of bot token and provider payloads in logs.

The browser never receives the bot token or a token-containing URL.

Telegram availability is controlled by:

```text
FORPRINT_TELEGRAM_ENABLED
```

When disabled, the endpoint returns a controlled channel status and does not attempt network delivery.

## Null and spool transports

Local development uses a transport compatible with the production interface.

### NullTransport

Validates and records only a minimal success/failure result without writing message content to an external provider.

### SpoolTransport

Writes a restricted local artifact containing:

- request ID;
- timestamp;
- selected channel;
- safe recipient alias;
- redacted request summary;
- rendered message preview when explicitly allowed.

The spool directory must be outside the public webroot or blocked from HTTP access.

## Configuration contract

Transport configuration is environment-owned.

Recommended variables:

```text
FORPRINT_EMAIL_ENABLED
FORPRINT_TELEGRAM_ENABLED
FORPRINT_NOTIFICATION_MODE
FORPRINT_SMTP_HOST
FORPRINT_SMTP_PORT
FORPRINT_SMTP_USER
FORPRINT_SMTP_PASSWORD
FORPRINT_SMTP_ENCRYPTION
FORPRINT_MAIL_FROM
FORPRINT_MAIL_TO
FORPRINT_TELEGRAM_BOT_TOKEN
FORPRINT_TELEGRAM_CHAT_ID
FORPRINT_NOTIFICATION_TIMEOUT_SECONDS
```

Required behavior:

- missing optional transport configuration disables that transport safely;
- missing required configuration never appears in the public response;
- configuration validation runs before delivery;
- secret values are not written to checkpoint reports.

## Logging and observability

Operational logs include:

```text
timestamp
request_id
route
selected channel
transport result
duration
safe error category
```

Operational logs exclude:

```text
SMTP password
Telegram bot token
full provider URL containing a token
full message body by default
session ID
CSRF token
raw provider response with sensitive data
```

Provider-specific errors are mapped to stable categories such as:

```text
disabled
configuration_error
validation_error
timeout
connection_error
provider_rejected
internal_error
```

## Deployment compatibility

The following files form one release unit when changed together:

```text
communication form template
product communication buttons
frontend communication JavaScript
communication-request.php
notification service and transports
non-secret configuration schema
```

A release must not update only the button or only the endpoint.

The deployment manifest described in `docs/workflow/deployment_and_mirroring_policy_v0_1.md` keeps this unit synchronized.

Environment secrets remain outside that release and therefore survive repeated code synchronization.

## Test strategy

### Read-only discovery

- inspect `base/communication-request.php`;
- trace all callers;
- inspect current `SendMailController.php`;
- inspect current order-email usage;
- confirm existing route and response assumptions;
- confirm secret storage without printing values.

### Local tests

- valid email-mode request through `NullTransport`;
- valid Telegram-mode request through `NullTransport`;
- malformed phone;
- malformed email;
- unsupported channel;
- missing CSRF;
- duplicate idempotency key;
- rate limit;
- disabled transport;
- message-size limit;
- safe JSON response;
- no secret in logs or HTML.

### Technical-domain tests

- use controlled test recipients only;
- keep temporary `robots.txt` blocking active;
- submit one deliberate request per channel;
- verify timeout and partial-failure behavior;
- verify buttons recover after an error;
- verify no duplicate send after repeated click.

### Regression tests

- homepage and product pages render;
- product communication interface opens;
- cart behavior remains valid;
- existing order-email behavior is unchanged;
- 404 and security headers remain valid;
- deployment rollback restores the full communication release unit.

## Implementation stages

1. inspect the actual endpoint and caller graph;
2. freeze the public request/response contract;
3. add environment configuration interface without secrets;
4. add `NullTransport` or `SpoolTransport`;
5. route one existing communication form through the dispatcher;
6. integrate PHPMailer through `EmailTransport`;
7. add `TelegramTransport`;
8. run controlled technical-domain delivery tests;
9. document operational activation;
10. enable production transports only after the responsible person supplies and verifies credentials.

## Acceptance criteria

The architecture is implemented when:

- repeat deployment cannot separate the button from its endpoint;
- local testing sends no real message by default;
- production secrets are environment-owned;
- email and Telegram can be enabled independently;
- duplicate submissions do not produce duplicate deliveries;
- all public errors are safe;
- transport failures are logged with a request ID;
- existing order-email behavior is preserved or migrated through an explicitly tested change;
- release and rollback evidence are retained.

## Deferred decisions

A future version will decide:

- whether delivery becomes asynchronous;
- whether idempotency storage moves to Redis or the database;
- whether order emails use the same dispatcher;
- whether delivery events require an administrative audit view;
- whether provider health checks become part of release readiness.
