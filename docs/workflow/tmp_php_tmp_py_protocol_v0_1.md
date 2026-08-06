# Протокол tmp/work/tmp.php і tmp/work/tmp.py v0.1

**ID:** `FP-WEB-WF-002`

## Призначення

`tmp/work/tmp.php` і `tmp/work/tmp.py` — одноразові operator entrypoints. Вони не є канонічними modules і не повинні містити єдину копію важливої логіки.

## tmp/work/tmp.php

Використовується для:

- PHP file analysis/patch;
- PHP lint;
- MySQL через чинну runtime config;
- idempotent migration;
- Composer intake;
- PHP library smoke;
- template/runtime inspection.

## tmp/work/tmp.py

Використовується для:

- tree inventory;
- пошуку по багатьох файлах;
- encoding/line endings;
- JSON/YAML/TSV;
- report/package generation;
- filesystem automation;
- запуску наборів checks.

## Режими

### READ ONLY

- друкує mode;
- не записує;
- показує commands і exit codes;
- має явний summary.

### PATCH

- перевіряє required files і markers;
- зупиняється при unexpected state;
- змінює тільки перелічені files;
- за потреби робить temporary backups;
- запускає syntax checks;
- показує change summary.

## Вимоги

1. idempotency або marker «already applied»;
2. точна кількість replacements;
3. no secrets;
4. no unrelated files;
5. no destructive SQL без окремого плану;
6. reusable logic переноситься в `scripts/` або migration;
7. після patch — diff/check.

## Безпечний запуск

```bash
wc -l tmp/work/tmp.php
php -l tmp/work/tmp.php
php tmp/work/tmp.php
```

```bash
wc -l tmp/work/tmp.py
python -m py_compile tmp/work/tmp.py
python tmp/work/tmp.py
```

## Типові помилки

| Помилка | Запобігання |
|---|---|
| Новий PHP дописано після старого | Повна заміна + `php -l` |
| Replace знайшов не той блок | Marker + exact count |
| Patch застосовано двічі | Idempotent check |
| Змінено забагато файлів | status/diff до і після |
| DB change не задокументована | Versioned migration |
| Tmp став permanent tool | Перенести в тематичний `scripts/` |
