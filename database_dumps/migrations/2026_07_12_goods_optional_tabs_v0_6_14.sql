ALTER TABLE goods
  ADD COLUMN tab_details_enabled int(11) NULL DEFAULT 1,
  ADD COLUMN tab_details_title varchar(255) NULL DEFAULT 'Детальніше' AFTER content,
  ADD COLUMN tab_specs_enabled int(11) NULL DEFAULT 0 AFTER tab_details_title,
  ADD COLUMN tab_specs_title varchar(255) NULL DEFAULT 'Характеристики' AFTER tab_specs_enabled,
  ADD COLUMN tab_specs_content text NULL AFTER tab_specs_title,
  ADD COLUMN tab_conditions_enabled int(11) NULL DEFAULT 0 AFTER tab_specs_content,
  ADD COLUMN tab_conditions_title varchar(255) NULL DEFAULT 'Спеціальні умови' AFTER tab_conditions_enabled,
  ADD COLUMN tab_conditions_content text NULL AFTER tab_conditions_title;

UPDATE goods SET tab_details_title = 'Детальніше' WHERE tab_details_title IS NULL OR tab_details_title = '';
UPDATE goods SET tab_specs_title = 'Характеристики' WHERE tab_specs_title IS NULL OR tab_specs_title = '';
UPDATE goods SET tab_conditions_title = 'Спеціальні умови' WHERE tab_conditions_title IS NULL OR tab_conditions_title = '';