# ForPrint mail operations and hosting migration runbook v0.1

**ID:** `FP-WEB-WORKFLOW-MAIL-001`
**Date:** 2026-07-29
**Status:** active
**Machine-readable source:** `mail_operations_and_hosting_migration_runbook_v0_1.yaml`

## Standard sequence

```text
inventory
→ read-only validation
→ backup
→ dry run
→ controlled apply
→ runtime validation
→ one controlled end-to-end test
→ report
→ documentation update
→ explicit Git staging and commit
```

## Routine checks

```bash
dig +short MX forprint.net.ua
dig +short A mail.forprint.net.ua
openssl s_client -starttls smtp -connect mail.forprint.net.ua:587 -servername mail.forprint.net.ua
openssl s_client -connect mail.forprint.net.ua:993 -servername mail.forprint.net.ua
php -l base/communication-request.php
```

Authentication checks must use an approved diagnostic that does not expose the
password in command arguments, reports or shell history.

## Website-only migration

1. Inventory webroot, database, PHP, extensions and scheduled tasks.
2. Prepare the new host before changing DNS.
3. Recreate the secret runtime file outside webroot with mode `0600`.
4. Confirm outbound TCP `587`.
5. Validate TLS, SMTP envelope, one controlled DATA message and one real form.
6. Record the new outbound SMTP source address.
7. Change only website DNS.
8. Keep the old website available through the rollback window.

## Mail-only migration

1. Inventory mailboxes, aliases, data, quotas, filters, DNS, TLS and backups.
2. Provision the new server and mailbox before cutover.
3. Migrate mailbox data using a tested reversible procedure.
4. Configure `mail.forprint.net.ua` TLS.
5. Prepare MX, A/AAAA, SPF, DKIM, DMARC and PTR.
6. Validate inbound transport, submission `587`, IMAP `993`, local delivery and
   queue processing.
7. Update website runtime secrets securely when required.
8. Cut over DNS and monitor both old and new queues.

## Acceptance

The migration is complete only after:

- TLS is valid for `mail.forprint.net.ua`;
- SMTP returns `235/250/250/354/250`;
- logs show accepted, routed and completed;
- the office mailbox sends and receives;
- the website endpoint returns HTTP `200`;
- a real form message reaches the mailbox;
- rollback remains possible.

## Secret rule

Never put mailbox passwords, SMTP passwords, DKIM private keys, TLS private
keys, administrator credentials or backup encryption keys in documentation or
Git.
