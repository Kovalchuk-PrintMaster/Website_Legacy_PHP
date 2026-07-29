# Decision: separate mail service and verified submission contract

**ID:** `FP-WEB-ADR-2026-07-29-001`
**Date:** 2026-07-29
**Status:** accepted
**Scope:** website mail delivery, mailbox access, hosting migration

## Context

The public PHP website runs on external website hosting, while ForPrint mail is
served by a separate ForPrint-operated mail server. During stabilization, SMTP
submission, local delivery, IMAP access and the website communication endpoint
were validated independently and then end to end.

The incident also showed why the web domain and the mail hostname must not be
treated as interchangeable. Outlook failed when configured with
`forprint.net.ua` as the mail server and worked with
`mail.forprint.net.ua`.

## Decision

1. `mail.forprint.net.ua` is the canonical hostname for mail protocols.
2. Website hosting submits through authenticated SMTP on port `587` with
   `STARTTLS`.
3. TLS certificate and hostname verification are mandatory.
4. IMAP clients use `mail.forprint.net.ua:993` with SSL/TLS.
5. The website and mail server remain separate migration units.
6. Runtime mail secrets remain outside the webroot with mode `0600`.
7. `FP_WEB_ENABLE_SMTP` resolves to exact string `1`.
8. `FP_WEB_ENABLE_PHP_MAIL` resolves to exact string `0`; PHP mail fallback
   remains disabled.
9. Rate-limit and idempotency state use a private writable runtime directory.
10. Documentation and reports may contain contracts, key names and sanitized
    evidence, but never passwords, tokens or private keys.
11. Every hosting or mail migration requires inventory, backup, dry run,
    controlled apply, end-to-end validation and rollback readiness.

## Consequences

Positive:

- website hosting can move without moving the mail server;
- the mail server can move without rewriting the website;
- failures are isolated by layer;
- client configuration is unambiguous;
- controlled tests can prove each stage separately;
- secrets stay outside Git and public web paths.

Operational cost:

- DNS, TLS, SMTP, IMAP, mailbox data and website runtime must be inventoried
  separately;
- a full migration requires both protocol tests and a real website-form test;
- DNS authentication and mailbox backup details must be recorded before the
  next migration.

## Rollback

- restore timestamped website file backups;
- restore the previous secure runtime configuration;
- restore the previous website or mail DNS values within the rollback window;
- keep old mail queues and mailbox data until reconciliation is complete;
- never weaken TLS verification as a rollback technique.
