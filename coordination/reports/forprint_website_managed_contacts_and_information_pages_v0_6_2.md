
ForPrint Website — Managed Contacts and Information Pages v0.6.2 Report
Status

managed_contacts_and_information_pages_v0_6_2_completed

Completed
Contacts are now a managed public page.
Generic information pages now have a controller and template.
Public header navigation uses managed information records.
Admin product edit no longer fails on checkbox list null values.
Local HTTP smoke includes managed information routes.
Local check passes.
Verified routes
/                                      -> 200
/contacts/                             -> 200
/information/contacts/                 -> 200 after redirect to /contacts/
/information/oplata-i-dostavka/        -> 200
/admin/edit/goods/110                  -> 200
Required checks
FP_WEB_LOCAL_HTTP_PORT=8099 make site-smoke
make check
git diff --check
Known notes
Legacy PHP warnings remain and should be cleaned gradually.
Special offers are not implemented yet.
Contact form submission remains separate.
Cart remains separate.
Next checkpoint

v0.6.3 — Managed Special Offers Product Listing
