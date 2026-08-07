# ForPrint communication release safety and recovery v0.1

**Status:** active operational workflow
**Date:** 2026-08-07
**Scope:** public Telegram/email enquiry forms, production communication runtime, deployment acceptance and recovery

## Purpose

Prevent hosting updates from silently breaking public enquiry forms and provide one short diagnostic path when a regression appears.

```text
form renderer / CSRF issuer
→ CommunicationRuntimeBootstrap.php
→ protected hosting communication_runtime.php
→ CommunicationRequestSecurity.php
→ communication-request.php
→ communication_buttons + communication_requests
→ Telegram or email transport
```

Every participant must use the same runtime context.

## Hosting-owned state

The application payload must not overwrite the production communication runtime addressed by:

```text
FP_DEPLOY_COMMUNICATION_RUNTIME_PATH
```

The protected runtime includes Telegram/SMTP configuration plus:

```text
FP_WEB_COMMUNICATION_SECURITY_SECRET
FP_WEB_COMMUNICATION_SECURITY_DIR
```

The security directory is private writable state outside the public webroot.

Secrets, target addresses and private paths are never committed or printed in normal reports.

## Canonical owners

```text
base/libraries/CommunicationRuntimeBootstrap.php
    runtime allowlist + boolean normalization

base/libraries/CommunicationRequestSecurity.php
    CSRF, rate-limit and idempotency

base/templates/default/include/communicationRequestForm.php
base/templates/default/include/productCommunicationButtons.php
    form token issuers

base/communication-request.php
    POST verifier, storage and delivery-status owner

scripts/inspection/check_website_communication_runtime.py
    low-level protected production runtime readiness

scripts/inspection/check_website_communication_acceptance.py
    full non-sending application/runtime acceptance
```

## Boolean contract

```text
true / yes / on / 1 → 1
false / no / off / 0 / empty → 0
```

This permits strict consumers such as:

```php
getenv('FP_WEB_ENABLE_SMTP') === '1'
```

## Canonical commands

```text
make hosting-deploy-help
make hosting-deploy-frontend-dry-run
make hosting-deploy-frontend
make hosting-deploy-code-dry-run
make hosting-deploy-code
make hosting-deploy-backend-dry-run
make hosting-deploy-backend
make hosting-deploy-dependencies-dry-run
make hosting-deploy-dependencies
make hosting-deploy-database-dry-run
make hosting-deploy-database
make hosting-deploy-media-dry-run
make hosting-deploy-media
make hosting-deploy-manifest-dry-run MANIFEST=...
make hosting-deploy-manifest MANIFEST=...
make hosting-reset-from-local
make hosting-parity-check
make hosting-communication-check
```

Normal release Make targets grant `FP_DEPLOY_ALLOWED=1` only for the active deploy command and restore `0` afterwards.

File deployment uses full communication acceptance before and after install. Full reset performs the same check after webroot/database installation and before final receipt.

## Non-sending acceptance

`make hosting-communication-check` sends no email or Telegram message and does not POST a real enquiry.

It verifies:

- protected runtime readiness;
- security secret and private writable security directory;
- canonical boolean flags;
- SMTP and Telegram readiness predicates;
- PHPMailer/autoload;
- communication button/target readiness;
- endpoint runtime-before-verifier ordering;
- issuer runtime-before-CSRF ordering;
- production product-page CSRF tokens validate against the canonical verifier.

## Fast diagnosis

### HTTP 403

Primary suspicion:

```text
CSRF issuer runtime != verifier runtime
```

Inspect:

```text
communicationRequestForm.php
productCommunicationButtons.php
CommunicationRuntimeBootstrap.php
CommunicationRequestSecurity.php
communication-request.php
```

Run `make hosting-communication-check`.

### HTTP 500

Inspect:

```text
check_website_communication_runtime.py
check_website_communication_acceptance.py
CommunicationRequestSecurity.php
communication-request.php
production PHP error log
database schema readiness
```

Typical causes: security secret missing, security directory unavailable, runtime key hidden by bootstrap, DB/schema failure.

### HTTP 200 + stored_email_not_configured

The request is stored but email delivery was not attempted.

Inspect:

```text
FP_WEB_ENABLE_SMTP after bootstrap
boolean normalization
SMTP required fields
communication_buttons email target
```

For the current endpoint SMTP enablement must become canonical string `1`.

### HTTP 200 + stored_smtp_failed

SMTP was configured and attempted. Investigate network/auth/TLS/SMTP server behavior.

### HTTP 200 + sent_smtp_email / sent_email

Application delivery succeeded. Investigate downstream mailbox/spam handling only if the mailbox still lacks the message.

### Telegram statuses

```text
sent_telegram
stored_telegram_not_configured
stored_telegram_failed
```

## Evidence order

Browser:

```text
DevTools → Network → Fetch/XHR
communication-request.php
HTTP status
Response JSON/text
Timing
```

Database, status-only:

```text
communication_requests.id
communication_requests.mode
communication_requests.delivery_status
communication_requests.created_at
```

Do not paste cookies, CSRF values, contact data, message text or credentials.

## Recovery order

```text
protected runtime problem → runtime-only repair
bootstrap problem → exact bootstrap manifest
issuer problem → exact issuer-template manifest
endpoint problem → exact endpoint manifest
DB target/data problem → database-only operation
large parity loss → full hosting reset
```

Do not use a full reset for a one-file regression.
