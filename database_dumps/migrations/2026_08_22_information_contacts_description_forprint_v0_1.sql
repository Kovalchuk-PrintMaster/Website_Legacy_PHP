-- ForPrint contacts public-description migration
-- Date: 2026-08-22
-- Scope: information.id=8 description only.
--
-- Important:
-- - goods/news content is already identical between local and production;
-- - keywords is intentionally excluded from this public SEO migration because
--   the public frontend does not render a meta-keywords tag;
-- - production release must still perform an exact preflight on the old value.

UPDATE information
SET description = 'Телефон, email, адреса та графік роботи ForPrint. Зв’яжіться з нами щодо друку, брендування та рекламної продукції.'
WHERE id = 8
  AND description = 'Контакти PrintMaster';
