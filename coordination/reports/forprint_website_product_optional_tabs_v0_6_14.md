# ForPrint Website — Product Optional Tabs v0.6.14 Report

## Status

`product_optional_tabs_v0_6_14_ready_for_manual_test`

## Completed

- Added optional tab fields to the local `goods` table.
- Registered optional tab fields in admin settings.
- Replaced hardcoded product tabs with dynamic product tab rendering.
- Details tab remains always visible.
- Specifications and special conditions tabs are hidden by default and enabled per product.

## Manual test required

Open a goods item in admin and confirm:

- `Назва вкладки "Детальніше"` is editable.
- `Показувати вкладку "Характеристики"` defaults to `Ні`.
- `Показувати вкладку "Спеціальні умови"` defaults to `Ні`.
- Enabling optional tabs makes them appear on the product page.
- Disabled optional tabs are not rendered on the product page.

## Admin polish

- Admin ordering was polished so optional tab titles/radio controls are positioned beside their text blocks near the bottom of the goods form.

## Admin content-block placement

- Optional tab controls were moved into the admin `vg-content` block.
- This keeps tab title/radio/content fields together near the text editors.

## Admin render override

- Added a dedicated goods admin render block for optional tab fields.
- Generic admin rendering now skips `tab_*` fields for goods and renders them together after the main content section.

## Admin layout fix

- Dedicated optional tabs block was moved outside `vg-content`.
- This prevents TinyMCE auto-init from overlaying optional tab title/radio controls.

## Details tab admin placement

- Details tab controls were moved directly before the main content editor.
- The main goods `content` editor is now labelled as the Details tab text.
- Specifications and Special Conditions remain grouped in the dedicated optional tabs block.

## Details tab frontend visibility

- The Details tab now respects `tab_details_enabled` on the product page.
- If all three tabs are disabled, the product tabs section is not rendered.
