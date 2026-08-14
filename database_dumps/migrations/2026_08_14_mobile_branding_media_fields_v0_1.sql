-- ForPrint MOBILE.02B
-- Managed favicon and mobile branding media fields.
-- Local canonical schema migration; production application is a separate
-- controlled database-release step.
--
-- Preconditions:
--   settings.favicon_img does not exist
--   settings.mobile_header_img does not exist
--   footer_settings.mobile_logo_img does not exist

ALTER TABLE `settings`
    ADD COLUMN `favicon_img` varchar(255) NULL AFTER `img`,
    ADD COLUMN `mobile_header_img` varchar(255) NULL AFTER `favicon_img`;

ALTER TABLE `footer_settings`
    ADD COLUMN `mobile_logo_img` varchar(255) NULL AFTER `logo_img`;
