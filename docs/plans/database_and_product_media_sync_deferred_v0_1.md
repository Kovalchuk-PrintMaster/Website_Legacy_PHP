# Відкладена синхронізація БД і товарних медіа v0.1

**ID:** `FP-WEB-PLAN-DB-SYNC-001`
**Дата:** 2026-07-29
**Статус:** deferred

## Рішення

Передача оновлень основної БД з локального сервера на hosting призупинена,
поки локальний сайт доповнюється товарами.

До явного відновлення задачі заборонені:

- production import або full replacement;
- incremental merge;
- bidirectional sync;
- bulk product-media sync;
- destructive cleanup.

## Умова відновлення

Оператор окремо підтверджує завершення поточного наповнення каталогу.
Після цього створюється нова процедура зі schema comparison, ownership map,
media references, staging rehearsal, cutover і rollback.

Машинний план міститься у
`database_and_product_media_sync_deferred_v0_1.yaml`.
