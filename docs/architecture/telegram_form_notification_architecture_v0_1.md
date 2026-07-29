# Архітектура передачі заявки через Telegram v0.1

**ID:** `FP-WEB-ARCH-NOTIFY-001`
**Дата:** 2026-07-29
**Статус:** accepted

## Підтверджений потік

```text
Telegram button
→ browser form / mode=telegram
→ fetch communication-request.php
→ security, validation and idempotency
→ communication_buttons lookup
→ INSERT communication_requests
→ Telegram Bot API sendMessage
→ UPDATE delivery_status
→ JSON response
```

Заявка зберігається в БД до зовнішньої доставки. Успішний статус —
`sent_telegram`. При недоступній або неналаштованій доставці запис
залишається у БД зі статусом `stored_telegram_failed` або
`stored_telegram_not_configured`.

## Керування

`communication_buttons.alias=telegram` і `visible=1` керують видимістю
можливості на сайті. `communication_buttons.target` є несекретною
метаінформацією.

Фактична адреса Telegram-доставки береться з
`FP_WEB_TELEGRAM_CHAT_ID`, а bot identity — з
`FP_WEB_TELEGRAM_BOT_TOKEN`.

Обидва значення зберігаються поза webroot у
`/var/www/825163-nikolay.k/data/.forprint-secrets/communication_runtime.php`
з режимом `0600`.

## Перевірка

Перед контрольним повідомленням використовуються `getMe`, `getChat` і
`getChatMember`. Після зміни runtime виконується одна контрольована форма,
HTTP `200` і фактичне отримання одного повідомлення.

Машинний контракт міститься у
`telegram_form_notification_architecture_v0_1.yaml`.
