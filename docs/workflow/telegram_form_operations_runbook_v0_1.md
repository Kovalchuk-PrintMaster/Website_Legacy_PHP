# Операційна процедура Telegram-форми v0.1

**ID:** `FP-WEB-WF-TELEGRAM-001`
**Дата:** 2026-07-29
**Статус:** active

## Початкове налаштування

1. Підготувати приватний чат і додати бота.
2. Перевірити `getMe`, `getChat`, `getChatMember` без `sendMessage`.
3. Зробити backup production runtime.
4. Додати token і chat ID поза webroot.
5. Зберегти режим `0600`.
6. Перевірити runtime з LiteSpeed PHP.
7. Надіслати одну контрольовану форму.
8. Підтвердити HTTP `200`, `sent_telegram` і одне повідомлення.

## Керування кнопкою

- увімкнення: `communication_buttons.alias=telegram`, `visible=1`;
- вимкнення: `visible=0`;
- token ніколи не записується в БД;
- фактичний chat ID змінюється тільки у protected runtime.

## Інциденти

HTTP `200` не завжди означає успішну зовнішню доставку. Треба перевірити
`delivery_completed` і `delivery_status`.

`stored_telegram_not_configured` означає відсутній runtime-контракт.
`stored_telegram_failed` означає, що заявка збережена, але Telegram API не
підтвердив доставку.

Машинна повна процедура міститься у
`telegram_form_operations_runbook_v0_1.yaml`.
