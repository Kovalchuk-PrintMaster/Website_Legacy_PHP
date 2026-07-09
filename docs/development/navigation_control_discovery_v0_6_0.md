# ForPrint Website — Navigation Control Discovery v0.6.0

## Status

`navigation_control_discovery_v0_6_0_completed`

## Purpose

Inspect how the legacy website builds public navigation and how existing database tables can be reused for admin-controlled menu visibility.

## Current finding

The public header currently uses a mixed navigation approach:

```text
catalog menu from runtime menu data;
information menu links from runtime menu data;
hardcoded news link;
hardcoded contacts link;
cart link;
legacy knoweleges block.

The admin area appears to use a dynamic table-based menu, which means existing generic CRUD behavior may be reused instead of building a new admin module from scratch.

Existing useful table fields
catalog: visible, menu_position, alias, name
information: visible, menu_position, show_top_menu, alias, name, content
news: visible, menu_position, alias, name
sales: visible, menu_position, external_alias, name
delivery: menu_position, name
Initial decision

Do not create a new navigation table yet.

First attempt should reuse the existing information.show_top_menu and visible fields for top-menu control.

Working hypothesis

For first public launch:

Catalog should remain visible.
Information pages should be shown only when visible=1 and show_top_menu=1.
News may be hidden from top navigation until content is reviewed.
Cart should be hidden or separated until cart workflow is fixed.
Contacts may remain as a static link.
Next step

Inspect where $this->menu is formed and then patch the frontend header with a small controlled change.

Next checkpoint

ForPrint Website — Admin Controlled Header Navigation v0.6.1
