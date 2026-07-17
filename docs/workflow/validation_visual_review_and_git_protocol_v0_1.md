# Checks, visual review і Git protocol v0.1

**ID:** `FP-WEB-WF-003`

## Рівні перевірки

### Structure

- expected files;
- no secrets;
- no accidental generated files;
- correct webroot paths.

### Syntax/static

- `php -l`;
- `python -m py_compile`;
- `node --check`, якщо доступний;
- Composer validate;
- `git diff --check`.

### Runtime

- DB smoke;
- route smoke;
- preview start/restart;
- targeted POST/curl;
- schema check;
- error log review.

### Browser

- desktop/mobile;
- form/modal;
- cards/gallery;
- header/footer;
- admin, якщо змінений.

### Delivery

- DB insert;
- Telegram;
- email;
- `delivery_status`;
- honeypot без false positive.

## Мінімальна sequence

```bash
git status --short
git diff --check
FP_WEB_LOCAL_HTTP_PORT=8099 make site-smoke
make check
git status --short
```

## Staging

```bash
git add <explicit paths>
git diff --cached --check
git diff --cached --stat
git diff --cached
```

Не використовувати бездумне `git add .`, коли є паралельні changes.

## Commit gate

- syntax clean;
- smoke clean;
- visual accepted;
- no extra files;
- diff understandable;
- docs match reality;
- unfinished feature не описана як completed.

## Publication gate

Окремий commit не означає production readiness. Потрібні secrets, webroot, DB backup, HTTPS, admin, uploads, forms, logs, monitoring і rollback.
