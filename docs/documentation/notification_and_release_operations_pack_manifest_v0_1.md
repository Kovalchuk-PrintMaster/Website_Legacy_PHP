# Notification and release operations documentation pack v0.1

**ID:** `FP-WEB-DOC-PACK-NOTIFY-RELEASE-001`
**Дата:** 2026-07-29
**Статус:** working architecture package

## Містить

- machine-readable Telegram architecture;
- Telegram setup, rotation, troubleshooting and rollback runbook;
- planned local-to-hosting application release runbook;
- explicit deferred database/product-media plan;
- historical Telegram working-state snapshot;
- accepted architecture decision;
- read-only validator.

## Безпека

Пакет не містить bot token, chat ID, паролів, private keys або даних
відвідувачів. Він не змінює сайт, hosting, БД, Telegram, пошту або Git.

## Встановлення

Розпакувати з кореня репозиторію через `unzip -n`, перевірити SHA-256,
скомпілювати validator і виконати його. Git staging/commit виконується
тільки окремою явною дією.
