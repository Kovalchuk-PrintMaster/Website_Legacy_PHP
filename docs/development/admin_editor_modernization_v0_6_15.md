# ForPrint Website — Admin Editor Modernization v0.6.15

## Status

`admin_editor_modernization_v0_6_15_ready_for_manual_test`

## Purpose

Modernize the admin rich-text editor behavior without replacing the local TinyMCE package.

## Changes

- Cleaned duplicated `textarea.php` wrapper markup.
- Renamed the editor checkbox label to Ukrainian.
- Auto-enables TinyMCE for goods:
  - `content`
  - `tab_specs_content`
  - `tab_conditions_content`
- Rewrote `tinymce.init.js` with a stable local TinyMCE configuration.
- Added useful plugins: lists, links, images by URL, media, tables, code, fullscreen, preview, wordcount.
- Added editor content CSS to reduce excessive frontend-like spacing during editing.
- Ensures TinyMCE saves content back into textareas before form submit.