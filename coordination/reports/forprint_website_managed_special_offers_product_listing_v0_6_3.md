
ForPrint Website — Managed Special Offers Product Listing v0.6.3 Report
Status

managed_special_offers_product_listing_v0_6_3_completed

Completed
Added /special-offers/ public route.
Added SpecialoffersController.
Added specialoffers.php template.
Reused existing product cards.
Used existing goods.sale and goods.hot admin flags.
Redirected managed information alias to /special-offers/.
Normalized local information alias for “Спеціальні пропозиції”.
Added route smoke coverage.
Public routes
/special-offers/
/information/special-offers/
/information/politika-kodenfintsealnosti/
Admin control
/admin/edit/goods/<id>

Flags:

sale = Акція
hot = Гарячі пропозиції
Checks
php -l base/core/user/controllers/SpecialoffersController.php
php -l base/templates/default/specialoffers.php
php -l base/core/user/controllers/InformationController.php
php -l base/core/base/settings/Settings.php
python -m py_compile scripts/inspection/run_website_local_http_smoke.py
FP_WEB_LOCAL_HTTP_PORT=8099 make site-smoke
make check
git diff --check
