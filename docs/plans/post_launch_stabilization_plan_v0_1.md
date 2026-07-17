# План стабілізації після запуску v0.1

**ID:** `FP-WEB-PLAN-002`
**Статус:** planned

## Перші 72 години

- availability;
- logs;
- Telegram/email;
- `communication_requests`;
- disk usage;
- 404/500;
- без великих refactor-ів.

## Перші два тижні

- фактичні user issues;
- mobile regressions;
- real uploads;
- performance key pages;
- debug cleanup;
- backup/restore;
- post-launch snapshot.

## Наступна modernization queue

1. admin/upload/session security;
2. forms/delivery;
3. decomposition `script.js`;
4. reduction `style.css` overrides;
5. validation helper unification;
6. testable service boundaries;
7. API/integration boundaries.

## Метрики

Availability, 5xx, PHP fatals, requests count, failed delivery, upload failures, disk space, DB backup, load time.
