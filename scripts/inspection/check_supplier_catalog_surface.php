<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$paths = [
    'controller' => $root . '/base/core/user/controllers/SuppliercatalogController.php',
    'supplier_template' => $root . '/base/templates/default/suppliercatalog.php',
    'supplier_css' => $root . '/base/templates/default/assets/css/forprint-supplier-catalog.css',
    'catalog_template' => $root . '/base/templates/default/catalog.php',
    'catalog_css' => $root . '/base/templates/default/assets/css/forprint-catalog.css',
    'page_structure_css' => $root . '/base/templates/default/assets/css/forprint-page-structure.css',
    'product_detail_css' => $root . '/base/templates/default/assets/css/forprint-product-detail.css',
    'product_communication_css' => $root . '/base/templates/default/assets/css/forprint-product-communication.css',
    'catalog_js' => $root . '/base/templates/default/assets/js/forprint-catalog.js',
    'settings' => $root . '/base/core/base/settings/Settings.php',
    'header' => $root . '/base/templates/default/include/header.php',
];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "[FAIL] missing {$label}: {$path}\n");
        exit(1);
    }
}

$controller = file_get_contents($paths['controller']);
$supplierTemplate = file_get_contents($paths['supplier_template']);
$supplierCss = file_get_contents($paths['supplier_css']);
$catalogTemplate = file_get_contents($paths['catalog_template']);
$catalogCss = file_get_contents($paths['catalog_css']);
$pageStructureCss = file_get_contents($paths['page_structure_css']);
$productDetailCss = file_get_contents($paths['product_detail_css']);
$productCommunicationCss = file_get_contents($paths['product_communication_css']);
$catalogJs = file_get_contents($paths['catalog_js']);
$settings = file_get_contents($paths['settings']);
$header = file_get_contents($paths['header']);

$checks = [
    'route mapping' => str_contains(
        $settings,
        "'supplier-catalog' => 'suppliercatalog/inputData/outputData'"
    ),
    'local/public gate' => str_contains(
        $controller,
        'FP_SUPPLIER_CATALOG_PUBLIC_ENABLED'
    ),
    'robots header' => str_contains(
        $controller,
        'X-Robots-Tag: noindex, nofollow, noarchive'
    ),
    'isolated supplier tables' => str_contains(
        $controller,
        "'supplier_catalog_offers'"
    ),
    'supplier surface marker' => str_contains(
        $supplierTemplate,
        'data-fp-surface="supplier-catalog"'
    ),
    'noindex marker' => str_contains(
        $supplierTemplate,
        'data-fp-indexation="noindex"'
    ),
    'shared catalog UI marker' => (
        str_contains($catalogTemplate, 'data-fp-catalog-ui="1"')
        && str_contains($supplierTemplate, 'data-fp-catalog-ui="1"')
    ),
    'shared progressive catalog navigation' => str_contains(
        $catalogJs,
        ".fp-catalog-page[data-fp-catalog-ui]"
    ),
    'canonical supplier breadcrumbs renderer' => (
        substr_count(
            $supplierTemplate,
            "include __DIR__ . '/include/breadcrumbs.php'"
        ) >= 2
        && str_contains($supplierTemplate, '$breadcrumbItems = [')
    ),
    'canonical catalog CSS shared scope' => (
        str_contains($catalogCss, '[data-fp-catalog-ui]')
        && !str_contains($catalogCss, '[data-fp-surface="catalog"]')
    ),
    'canonical page structure shared scope' => (
        str_contains($pageStructureCss, '[data-fp-catalog-ui]')
        && !str_contains($pageStructureCss, '[data-fp-surface="catalog"]')
    ),
    'canonical aside structure' => (
        str_contains($supplierTemplate, 'class="catalog-aside"')
        && str_contains($supplierTemplate, 'catalog-aside-block__title h2')
        && str_contains($supplierTemplate, 'fp-catalog-filter-section')
        && str_contains($supplierTemplate, 'fp-catalog-category-list')
        && str_contains($supplierTemplate, 'fp-catalog-category-link')
    ),
    'canonical toolbar structure' => (
        str_contains($supplierTemplate, 'catalog-section-top-items')
        && str_contains($supplierTemplate, 'catalog-section-top-items__toggle')
        && str_contains($supplierTemplate, 'fp-catalog-toolbar__arrow')
        && str_contains($supplierTemplate, 'fp-catalog-quantity')
        && str_contains($supplierTemplate, 'class="qtyItems"')
    ),
    'canonical listing wrappers' => (
        str_contains($supplierTemplate, 'catalog-section catalog-section__four')
        && str_contains($supplierTemplate, 'catalog-section-items__wrapper')
        && str_contains($supplierTemplate, 'fp-product-card--catalog')
    ),
    'canonical card contract' => (
        str_contains($supplierTemplate, 'fp-product-card__image')
        && str_contains($supplierTemplate, 'fp-product-card__body')
        && str_contains($supplierTemplate, 'fp-product-card__excerpt')
        && str_contains($supplierTemplate, 'fp-product-card__price')
        && str_contains($supplierTemplate, 'fp-product-card__button')
    ),
    'canonical product-detail shell' => (
        str_contains($supplierTemplate, 'card-main fp-product-detail-page')
        && str_contains($supplierTemplate, 'fp-product-detail__container')
        && str_contains($supplierTemplate, 'class="fp-product-detail"')
        && str_contains($supplierTemplate, 'fp-product-detail__gallery')
        && str_contains($supplierTemplate, 'fp-product-detail__info')
        && str_contains($supplierTemplate, 'fp-product-detail__actions')
    ),
    'canonical product gallery' => (
        str_contains($supplierTemplate, 'card-main-gallery-thumb__container swiper-container')
        && str_contains($supplierTemplate, 'card-main-gallery-slider__container swiper-container')
        && !str_contains($supplierTemplate, 'data-fp-gallery-thumb')
        && !str_contains($supplierTemplate, 'data-fp-gallery-main')
    ),
    'full-width canonical details tabs' => (
        str_contains($supplierTemplate, 'class="fp-product-details-tabs"')
        && str_contains($supplierTemplate, 'fp-product-details-tabs__nav')
        && str_contains($supplierTemplate, 'Характеристики')
        && str_contains($supplierTemplate, 'fp-product-detail-features')
    ),
    'canonical structured detail polish' => (
        str_contains($productDetailCss, 'flex: 0 1 36rem;')
        && str_contains(
            $productDetailCss,
            '.fp-product-details-tabs__features'
        )
        && str_contains(
            $productCommunicationCss,
            '.fp-product-communication__button--text-only'
        )
        && str_contains(
            $supplierTemplate,
            'fp-product-communication__button--text-only'
        )
    ),
    'supplier CSS is adapter only' => (
        str_contains($supplierCss, 'FP_SUPPLIER_SHARED_CATALOG_UI_V1')
        && !str_contains($supplierCss, '.fp-supplier-catalog__layout')
        && !str_contains($supplierCss, '.fp-supplier-catalog__toolbar')
        && !str_contains($supplierCss, '.fp-supplier-catalog__grid')
        && !str_contains($supplierCss, '.fp-supplier-product__layout')
        && !str_contains($supplierCss, '.fp-supplier-product__gallery')
        && !str_contains($supplierCss, '.fp-supplier-product__section-band')
    ),
    'canonical CSS not duplicated by supplier controller' => (
        !str_contains($controller, 'forprint-product-cards.css')
        && !str_contains($controller, 'forprint-product-detail.css')
        && !str_contains($controller, 'forprint-catalog.css')
    ),
    'quote action retained' => (
        str_contains($supplierTemplate, 'data-fp-quote-add')
        && str_contains($supplierTemplate, 'На прорахунок')
        && str_contains($supplierTemplate, 'Рекомендовані методи нанесення')
    ),
    'supplier nav gate retained' => (
        str_contains($header, '$fpSupplierCatalogPreviewEnabled')
        && str_contains($header, 'Товари для брендування')
    ),
];

foreach ($checks as $label => $ok) {
    if (!$ok) {
        fwrite(STDERR, "[FAIL] {$label}\n");
        exit(1);
    }

    echo "[OK] {$label}\n";
}

echo "SUPPLIER_VISUAL_POLISH=PASS\n";
