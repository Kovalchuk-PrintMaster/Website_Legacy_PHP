# План підготовки до публікації v0.1

**ID:** `FP-WEB-PLAN-001`
**Дата:** 2026-07-16
**Статус:** active
**Стратегія:** publish safely, then improve

## Мета

Запустити наявний сайт без повного rewrite, закривши critical risks і забезпечивши мінімальну операційну керованість.

## Не входить

- новий framework;
- повна декомпозиція MVC;
- повна заміна admin;
- rewrite всього CSS/JS;
- повна зміна DB;
- інтеграція всіх майбутніх ForPrint modules.

## A. Завершити communication block

1. Запустити Composer intake для phone library.
2. Перевірити package/API smoke.
3. Додати library-backed server validation.
4. Додати soft second-submit confirmation.
5. Додати делікатний warning state.
6. Виключити form із legacy `phoneValidate()`.
7. Змінити auto-close на 1 second.
8. Перевірити UA national/E.164, international, `00`, malformed plus, short, unusual.
9. Перевірити Telegram, email, DB і delivery status.
10. Відокремити unrelated CSS.
11. Commit/push.

## B. Launch blocker audit

### Security

- secrets не в Git;
- production env;
- закрити dump/log/temp/config backups;
- admin authentication;
- upload endpoints;
- session/cookies;
- debug disabled;
- HTTPS.

### Runtime

- PHP/extensions;
- Composer production install;
- webroot `base/`;
- writable dirs;
- DB/charset/timezone;
- SMTP/Telegram;
- logs/rotation;
- backup.

### Data

- control DB dump;
- migration inventory;
- charset/collation;
- critical tables;
- rollback;
- `communication_requests`.

## C. Functional smoke

Home, catalog, product, search, contacts, delivery/payment, promotions, special offers, news, admin login, product edit, image upload, tabs, related goods, communication forms, redirects і 404.

## D. Visual/content pass

Header/footer, mobile menu, cards, product, gallery, tabs, modal, typography, buttons, broken links/images, placeholders, contacts і publication-required information.

## E. Staging/publication

1. target;
2. code/dependencies;
3. DB/migrations;
4. environment;
5. smoke;
6. domain/HTTPS;
7. delivery;
8. logs;
9. release commit/tag;
10. release snapshot + rollback data.

## Launch gate

- no critical security blocker;
- main routes work;
- admin protected;
- forms work;
- uploads controlled;
- DB backup exists;
- HTTPS works;
- logs available;
- rollback known;
- deferred issues recorded.
