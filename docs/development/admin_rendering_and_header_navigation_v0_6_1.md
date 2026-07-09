# ForPrint Website — Admin Rendering and Header Navigation v0.6.1

## Status

`admin_rendering_and_header_navigation_v0_6_1_completed`

## Purpose

Restore local admin rendering under PHP 8.2 and connect public header navigation to existing admin-controlled fields.

## Completed

- Fixed admin footer TinyMCE block rendering for PHP 8.2.
- Fixed admin show controller warnings around missing optional columns/settings.
- Fixed admin header active menu check.
- Fixed admin show item alias fallback.
- Fixed public `information` menu filter typo from `were` to `where`.
- Fixed public `knoweleges` header condition.
- Reused existing `information.show_top_menu` as first top-menu control mechanism.

## Current behavior

Admin login works locally:

```text
/admin/login

Admin show screen renders locally:

/admin/show
/admin/show/settings
/admin/show/catalog

Public header information menu should now be controlled by:

information.visible = 1
information.show_top_menu = 1
information.menu_position
Deferred
Public deployment.
Admin security hardening.
Full admin UI redesign.
New navigation table.
Cart repair.
Full header visual redesign.
Safety boundary
Local-only admin review.
No production DB.
No public admin exposure.
No SQL dump commit.
No local config commit.
Next checkpoint

ForPrint Website — Header Visual Simplification v0.6.2
