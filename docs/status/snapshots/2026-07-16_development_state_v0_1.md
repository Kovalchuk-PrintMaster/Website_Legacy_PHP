# Стан розробки на 2026-07-16

**ID:** `FP-WEB-STATUS-2026-07-16-001`
**Статус:** historical snapshot

## Загальний стан

Проєкт пройшов первинну інвентаризацію, local DB/runtime підготовку і серію frontend/admin доробок. Поточна стратегія — minimal launch readiness без фундаментального rewrite.

Фізичний webroot: `base/`.

## Підготовлена основа

- repository scaffold, README, Makefile, coordination;
- legacy inventory;
- config/secret review;
- DB dump intake/import;
- local runtime smoke;
- local HTTP route smoke;
- staging requirements;
- webroot exposure hardening;
- preview/restart workflow;
- Python inspection environment.

## Реалізовані або модернізовані блоки

- header navigation;
- contacts/information;
- special offers;
- promotions/delivery;
- news reserve;
- image optimizer;
- goods/gallery upload pipeline;
- full-image gallery storage;
- product gallery UI;
- configurable tabs;
- editor modernization;
- related goods;
- product communication buttons/forms;
- Telegram/email endpoint;
- email hardening;
- honeypot і modal auto-close;
- managed preview restart.

## Останні commits на момент аудиту

```text
0aaf73f Fix communication modal auto close and honeypot handling
a99209a Add managed website preview restart workflow
a8cf019 Polish product communication buttons
6523a4d Harden communication request email delivery
0a6a8df Add product communication request forms
```

## Working tree

Аудит показав незакомічені зміни:

```text
base/communication-request.php
base/templates/default/assets/css/forprint-product-detail.css
base/templates/default/assets/css/style.css
base/templates/default/assets/js/forprint-product-communication.js
base/templates/default/include/header.php
base/templates/default/include/productCommunicationButtons.php
```

Це не означає, що вони завершені або потрапили в production.

## Active block: international phone validation

Виявлено:

- legacy `script.js` обробляє всі tel inputs;
- formatter підтримує лише `380`;
- нова форма має приймати міжнародні номери;
- old `ValidationHelper.php` використовується Login/Orders;
- helper не підходить standalone endpoint-у;
- ручна нова normalization не є достатньою довгостроковою основою.

Погоджено:

- `giggsey/libphonenumber-for-php-lite`;
- server source of truth;
- UA default region для national input;
- E.164 normalization;
- malformed input block;
- unusual number soft warning + друге натискання;
- exclusion від old global formatter;
- success auto-close 1 second;
- old helper не видаляти до migration consumers.

Стан: Composer intake script підготовлений, але ще не запускався. Library і final patch не вважаються встановленими.

## Ризики

1. великий global CSS/JS;
2. collisions old/new handlers;
3. Node був недоступний під час JS audit;
4. змішаний controller/standalone runtime;
5. production secrets і admin/upload потребують final gate;
6. current uncommitted changes треба розділити й перевірити;
7. deployment/monitoring ще не підтверджені.

## Поточна ціль

Завершити communication form, закрити launch blockers, пройти staging/publication checklist і запустити сайт без фундаментальної перебудови.
