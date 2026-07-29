# ForPrint mail delivery and hosting architecture v0.1

**ID:** `FP-WEB-ARCH-MAIL-001`
**Date:** 2026-07-29
**Status:** active
**Machine-readable source:** `mail_delivery_and_hosting_architecture_v0_1.yaml`

## Purpose

This document records the working mail-delivery architecture after the website
form, SMTP submission, local delivery and mailbox access were validated end to
end.

## Current topology

```text
Browser form
  → website hosting / LiteSpeed PHP
  → base/communication-request.php
  → validation + rate limit + idempotency
  → communication_requests database row
  → authenticated SMTP submission
  → mail.forprint.net.ua:587 STARTTLS
  → Exim queue
  → local office mailbox
  → IMAP 993 SSL/TLS
  → Outlook or another mail client
```

Website hosting and mail hosting are separate components. The website control
address is `185.86.76.182`; the SMTP log observed outbound website traffic from
`185.86.77.163`. The mail server is `mail.forprint.net.ua` at
`176.36.207.12`.

## Canonical contracts

- SMTP submission: `mail.forprint.net.ua:587`, `STARTTLS`, authentication,
  certificate verification.
- IMAP: `mail.forprint.net.ua:993`, SSL/TLS.
- Operational mailbox: `office@forprint.net.ua`.
- Outlook SPA: disabled.
- PHP mail fallback: disabled.
- Runtime secrets:
  `/var/www/825163-nikolay.k/data/.forprint-secrets/communication_runtime.php`,
  outside webroot, mode `0600`.

## Application owners

```text
base/communication-request.php
base/libraries/CommunicationRequestSecurity.php
```

The endpoint owns request validation, database persistence and delivery
orchestration. The security component owns CSRF-related helpers, rate limiting,
idempotency and its private runtime state.

## Migration boundary

A website-hosting move normally keeps MX and the mail server unchanged. A
mail-server move normally keeps the website code unchanged and alters runtime
SMTP values only when the mail endpoint or credentials change. Moving both at
once should be split into two controlled cutovers where possible.

## Unknowns to close before the next migration

- exact MX inventory;
- SPF;
- DKIM selectors and rotation;
- DMARC;
- PTR ownership;
- mailbox backup and tested restore procedure.
