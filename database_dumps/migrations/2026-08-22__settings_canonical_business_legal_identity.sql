-- ForPrint canonical brand/legal identity owner.
-- Canonical owner: singleton settings row id=1.
-- Public brand and registered legal entity are intentionally separate concepts.
-- Production application must be guarded by an exact precondition/backup release script.

ALTER TABLE settings
    ADD COLUMN business_name VARCHAR(160) NULL AFTER name,
    ADD COLUMN legal_name VARCHAR(255) NULL AFTER business_name,
    ADD COLUMN edrpou VARCHAR(20) NULL AFTER legal_name,
    ADD COLUMN vat_id VARCHAR(32) NULL AFTER edrpou;

UPDATE settings
SET
    business_name = 'ForPrint',
    legal_name = 'ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ "НАПЕЧАТЬ СОЛЮШН"',
    edrpou = '41470904',
    vat_id = '414709026578'
WHERE id = 1;
