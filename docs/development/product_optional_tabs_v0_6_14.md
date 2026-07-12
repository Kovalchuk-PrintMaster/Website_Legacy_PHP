# ForPrint Website — Product Optional Tabs v0.6.14

## Status

`product_optional_tabs_v0_6_14_ready_for_manual_test`

## Purpose

Make product page tabs controllable from the goods admin card.

## Behavior

- The details tab is always shown.
- The details tab title is editable through `tab_details_title`.
- The specifications tab is hidden by default.
- The specifications tab can be enabled through `tab_specs_enabled`.
- The specifications tab title and HTML content are editable.
- If specifications content is empty but filters exist, the frontend falls back to the product filter table.
- The special conditions tab is hidden by default.
- The special conditions tab title and HTML content are editable.

## Database

Local DB was updated by the v0.6.14 patch script.

Reusable SQL is stored in:

`database_dumps/migrations/2026_07_12_goods_optional_tabs_v0_6_14.sql`

## Admin order polish

Optional tab controls are ordered directly after the main product content:

- details tab title;
- specifications enabled/title/content;
- special conditions enabled/title/content.

## Admin content-block placement

Optional tab title/radio/content fields are rendered inside the `vg-content` block so they stay near the main product content editor instead of appearing in the upper standard fields column.

## Admin custom render block

The goods admin form now skips `tab_*` fields in the generic block loop and renders them in a dedicated custom admin render block after the main content section. This keeps optional tab controls and text editors together.

## TinyMCE auto-init layout fix

Optional tab controls are rendered in a dedicated full-width admin block outside `vg-content`. This prevents TinyMCE auto-initialization from overlaying title/radio controls. Optional textareas can still be edited as plain text or manually switched to the editor.

## Details tab controls placement

The existing `content` field remains the text body for the Details tab. The goods admin form now renders `tab_details_enabled` and `tab_details_title` directly before the main content editor, while Specifications and Special Conditions stay in the dedicated optional tabs block.

## Details tab visibility

The Details tab now respects `tab_details_enabled`. When all product tabs are disabled, the frontend does not render the product tabs section.
