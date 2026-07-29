# ForPrint mail client and application configuration reference v0.1

**ID:** `FP-WEB-REF-MAIL-001`
**Date:** 2026-07-29
**Status:** active
**Machine-readable source:** `mail_client_and_application_configuration_v0_1.yaml`

## Outlook or compatible client

```text
Email: office@forprint.net.ua

Incoming:
  Protocol: IMAP
  Server: mail.forprint.net.ua
  Port: 993
  Encryption: SSL/TLS
  Username: office@forprint.net.ua
  SPA: disabled

Outgoing:
  Protocol: SMTP submission
  Server: mail.forprint.net.ua
  Port: 587
  Encryption: STARTTLS
  Authentication: required
  Username: office@forprint.net.ua
```

`forprint.net.ua` is the website hostname and must not be used as the mail
server. The wrong value causes IMAP login failure and repeated password prompts.

## Website runtime

The production runtime configuration is outside webroot:

```text
/var/www/825163-nikolay.k/data/.forprint-secrets/communication_runtime.php
mode 0600
```

Only key names and contracts belong in documentation. Passwords and other
secret values do not.
