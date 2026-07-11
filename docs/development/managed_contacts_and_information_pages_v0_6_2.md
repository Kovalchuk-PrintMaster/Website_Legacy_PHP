# ForPrint Website — Managed Contacts and Information Pages v0.6.2

## Status

`managed_contacts_and_information_pages_v0_6_2_completed`

## Purpose

Make Contacts and basic information pages manageable through the existing legacy `information` admin table.

## Completed

- Added `ContactsController`.
- Added public `contacts.php` template.
- Added `InformationController`.
- Added public `information.php` template.
- Added local maintenance script for ensuring the `Контакти` row exists in `information`.
- Added `/contacts/`, `/information/contacts/`, and `/information/oplata-i-dostavka/` to local HTTP smoke.
- Reworked public header navigation to use managed `information` links.
- Added frontend styles for managed contacts and information pages.
- Fixed admin `checkboxlist.php` PHP 8.2 null handling.
- Fixed local HTTP smoke process output handling to avoid hangs from legacy PHP warnings.
- Kept local scratch files ignored.

## Admin control

Contacts and information pages are managed through:

```text
/admin/show/information

Expected Contacts row:

name: Контакти
alias: contacts
visible: 1
show_top_menu: 1
menu_position: 4
Public routes
/contacts/
/information/contacts/
/information/oplata-i-dostavka/

/information/contacts/ redirects to /contacts/.

Admin compatibility

The admin product edit page was stabilized for PHP 8.2:

/admin/edit/goods/110

The page now returns HTTP 200 and loads the admin layout.

Safety boundary
Local-only DB seed.
No production DB migration.
No public deployment.
No admin security hardening yet.
No cart repair yet.
Next checkpoint

v0.6.3 — Managed Special Offers Product Listing
