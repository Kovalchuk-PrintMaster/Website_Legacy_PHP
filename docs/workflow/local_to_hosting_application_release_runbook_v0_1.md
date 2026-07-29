# Передача застосунку з локального сервера на хостинг v0.1

**ID:** `FP-WEB-WF-RELEASE-001`
**Дата:** 2026-07-29
**Статус:** planned

## Межа найближчого етапу

Передаємо контрольовано:

- PHP-код;
- templates;
- CSS і JavaScript;
- libraries;
- погоджені static presentation assets.

Не передаємо звичайним release-пакетом:

- production `config.php`;
- protected runtime secrets;
- logs, temp, sessions і backups;
- основну БД;
- DB-owned product media.

## Планований механізм

```text
accepted local Git state
→ exact file inventory
→ PHP/Python/HTTP checks
→ release manifest + SHA256SUMS
→ upload to staging outside webroot
→ verify and PHP-lint
→ timestamped production backup
→ atomic per-file install
→ hashes and production smoke tests
→ accept or rollback
→ sanitized report
```

Секрети передаються окремим installer-потоком: trusted source, non-sending
validation, backup, atomic `0600` install, production validation, rollback.

## База даних

Синхронізація БД і product userfiles поставлена на паузу, поки локальний
каталог доповнюється товарами. Кодова передача не має вимагати нової
production-схеми без окремо погодженої migration.

Повний машинний runbook міститься у
`local_to_hosting_application_release_runbook_v0_1.yaml`.
