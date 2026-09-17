<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$paths = [
    'controller' => $root . '/base/core/user/controllers/SuppliercatalogController.php',
    'template' => $root . '/base/templates/default/suppliercatalog.php',
    'css' => $root . '/base/templates/default/assets/css/forprint-supplier-catalog.css',
    'js' => $root . '/base/templates/default/assets/js/forprint-supplier-quote-list.js',
    'header' => $root . '/base/templates/default/include/header.php',
    'shell' => $root . '/base/templates/default/assets/css/forprint-shell.css',
];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "[FAIL] missing {$label}: {$path}\n");
        exit(1);
    }
}

$controller = file_get_contents($paths['controller']);
$template = file_get_contents($paths['template']);
$css = file_get_contents($paths['css']);
$js = file_get_contents($paths['js']);
$header = file_get_contents($paths['header']);
$shell = file_get_contents($paths['shell']);

$checks = [
    'supplier search mode' => str_contains(
        $controller,
        'fp_supplier_search'
    ),
    'supplier search method' => str_contains(
        $controller,
        'fpSupplierSearchOffers'
    ),
    'quote panel retained' => (
        str_contains($template, 'id="fp-quote-list"')
        && str_contains($template, 'data-fp-quote-panel')
        && str_contains($template, 'Підбірка товарів')
    ),
    'quote item metadata retained' => (
        str_contains($template, 'data-fp-quote-supplier="totobi"')
        && str_contains($template, 'data-fp-quote-external-id')
        && str_contains($template, 'data-fp-quote-methods')
    ),
    'canonical listing action' => str_contains(
        $template,
        'fp-product-card__button fp-supplier-catalog__quote-action'
    ),
    'canonical detail action' => str_contains(
        $template,
        'fp-product-communication__button fp-product-communication__button--text-only fp-supplier-catalog__quote-action'
    ),
    'quote list adapter CSS retained' => (
        str_contains($css, '.fp-supplier-quote__item')
        && str_contains($css, '.fp-supplier-quote__request')
    ),
    'supplier search group retained' => (
        str_contains($css, '.fp-supplier-search-group')
        && str_contains($js, 'Товари для брендування')
        && str_contains($js, 'bindSupplierSearchBridge')
    ),
    'quote rail mounts at top of canonical rail' => (
        str_contains($js, ".header__sidebar")
        && str_contains($js, 'rail.insertBefore(')
        && str_contains($js, 'rail.firstElementChild')
        && !str_contains($js, "'.fp-site-sidebar__utility'")
    ),
    'quote rail top shell owner retained' => (
        str_contains(
            $shell,
            'FP_SUPPLIER_QUOTE_RAIL_TOP_V2'
        )
        && str_contains(
            $shell,
            '.header__sidebar > .fp-quote-rail-utility'
        )
    ),
    'feature gate retained' => str_contains(
        $header,
        'fp-supplier-catalog-nav'
    ),
];

foreach ($checks as $label => $ok) {
    if (!$ok) {
        fwrite(STDERR, "[FAIL] {$label}\n");
        exit(1);
    }

    echo "[OK] {$label}\n";
}

echo "SUPPLIER_QUOTE_PARITY=PASS\n";
