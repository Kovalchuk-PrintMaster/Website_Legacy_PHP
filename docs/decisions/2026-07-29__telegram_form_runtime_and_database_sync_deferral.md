# Telegram form runtime and database-sync deferral

**ID:** `FP-WEB-ADR-2026-07-29-TELEGRAM-001`
**Date:** 2026-07-29
**Status:** accepted

## Decision

The website keeps the existing communication form and uses `mode=telegram`
to route one accepted request through `communication-request.php` to the
Telegram Bot API.

The bot token and actual destination chat ID belong to protected production
runtime configuration outside the webroot. They do not belong in Git,
browser JavaScript, documentation, or the `communication_buttons` table.

The `communication_buttons` row with `alias=telegram` controls the visible
website capability and stores non-secret routing/display metadata. The actual
Bot API destination remains `FP_WEB_TELEGRAM_CHAT_ID`.

## Persistence before delivery

An accepted request is inserted into `communication_requests` before
`sendMessage` is called. Telegram failure therefore produces a stored
request with a `stored_telegram_*` status instead of losing the visitor's
submission.

## Runtime transfer

Secrets are transferred separately from normal application releases:

1. read from a trusted secret source without printing values;
2. validate `getMe`, `getChat` and `getChatMember`;
3. back up the production runtime file;
4. install atomically with mode `0600`;
5. validate from production LiteSpeed PHP;
6. roll back on failure;
7. record only sanitized evidence.

## Local-to-hosting releases

The local Git repository remains the application-code source of truth.
Near-term releases may transfer reviewed code, templates, frontend assets and
approved presentation assets through a manifest, remote staging, backup,
atomic installation, validation and rollback.

Production runtime secrets, logs, temporary files and backups are excluded
from release archives.

## Database and product media

Database synchronization from the local server to hosting is deferred while
the local catalog is being enriched. Product media owned by database records
is deferred with it.

No database replacement, incremental merge, bidirectional sync or bulk
product-media copy is approved under this decision. A later phase must create
a new schema/data/media runbook and rehearse it on staging before production.
