# ForPrint Website — Managed Special Offers Product Listing v0.6.3

## Status

`managed_special_offers_product_listing_v0_6_3_completed`

## Purpose

Create a first managed product listing for “Спеціальні пропозиції”.

## Mechanism

The public route is:

```text
/special-offers/

The listing shows products from goods where:

visible = 1 AND (sale = 1 OR hot = 1)
Admin control

Products are controlled through the existing product edit form:

/admin/edit/goods/<id>

A product appears on /special-offers/ when either of these flags is enabled:

Акція = Так
Гарячі пропозиції = Так
Information navigation

The existing information row “Спеціальні пропозиції” is normalized locally to:

alias = special-offers
visible = 1
show_top_menu = 1
menu_position = 3

The managed information route redirects to the product listing:

/information/special-offers/ -> /special-offers/

The previous legacy alias is still redirected:

/information/politika-kodenfintsealnosti/ -> /special-offers/
Boundaries
No production DB migration.
No production deployment.
No new canonical product database.
No discount calculation yet.
No Calculator Engine integration yet.
No Library integration yet.
Next possible improvements
Add a dedicated special_offer field if sale/hot is too broad.
Add badges for sale, hot, hit, new.
Add special offer sorting.
Add admin help text for flags.